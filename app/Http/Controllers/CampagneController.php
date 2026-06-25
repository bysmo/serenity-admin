<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\Membre;
use App\Models\Cotisation;
use App\Services\EmailService;
use App\Helpers\SecurityHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampagneController extends Controller
{
    /**
     * Afficher la liste des campagnes
     */
    public function index(Request $request)
    {
        $query = EmailCampaign::with('creePar');

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', SecurityHelper::likeSearch($search))
                  ->orWhere('sujet', 'like', SecurityHelper::likeSearch($search));
            });
        }

        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $campagnes = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Statistiques
        $stats = [
            'total' => EmailCampaign::count(),
            'brouillon' => EmailCampaign::where('statut', 'brouillon')->count(),
            'en_cours' => EmailCampaign::where('statut', 'en_cours')->count(),
            'terminee' => EmailCampaign::where('statut', 'terminee')->count(),
        ];

        return view('campagnes.index', compact('campagnes', 'stats'));
    }

    /**
     * Afficher le formulaire de création d'une campagne
     */
    public function create()
    {
        $cotisations = Cotisation::where('actif', true)->orderBy('nom')->get();
        
        // Le champ segment a été retiré de la table membres
        $segments = [];
        return view('campagnes.create', compact('cotisations', 'segments'));
    }

    /**
     * Prévisualiser les membres qui recevront l'email (AJAX)
     */
    public function preview(Request $request)
    {
        // Validation stricte des filtres au lieu de $request->all()
        $validated = $request->validate([
            'cotisation_id'  => 'nullable|integer|exists:cotisations,id',
            'statut'         => 'nullable|string|in:actif,inactif,suspendu',
            'segment_id'     => 'nullable|integer|exists:segments,id',
        ]);

        $membres = $this->getMembresFromFilters($validated);
        
        return response()->json([
            'count' => $membres->count(),
            'membres' => $membres->take(10)->get()->map(function($membre) {
                return [
                    'id' => $membre->id,
                    'nom_complet' => $membre->nom_complet,
                    'email' => $membre->email,
                    'statut' => $membre->statut,
                ];
            }),
        ]);
    }

    /**
     * Enregistrer et envoyer une campagne
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'sujet' => 'required|string|max:255',
            'message' => 'required|string',
            'statut_membre' => 'nullable|array',
            'statut_membre.*' => 'in:actif,inactif,suspendu',
            'cotisation_id' => 'nullable|exists:cotisations,id',
            'date_adhesion_debut' => 'nullable|date',
            'date_adhesion_fin' => 'nullable|date',
        ]);

        // Récupérer les membres selon les filtres
        $membres = $this->getMembresFromFilters($validated);
        $membresAvecEmail = $membres->whereNotNull('email')->where('email', '!=', '');

        if ($membresAvecEmail->count() == 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Aucun membre avec une adresse email valide ne correspond aux critères sélectionnés.');
        }

        DB::beginTransaction();
        try {
            // Créer la campagne
            $campagne = EmailCampaign::create([
                'nom' => $validated['nom'],
                'sujet' => $validated['sujet'],
                'message' => $validated['message'],
                'statut' => 'en_cours',
                'filtres' => [
                    'statut_membre' => $request->statut_membre ?? null,
                    'cotisation_id' => $request->cotisation_id ?? null,
                    'date_adhesion_debut' => $request->date_adhesion_debut ?? null,
                    'date_adhesion_fin' => $request->date_adhesion_fin ?? null,
                ],
                'total_destinataires' => $membresAvecEmail->count(),
                'cree_par' => auth()->id(),
            ]);

            // Envoyer les emails
            $emailService = new EmailService();
            $emailService->configureSMTP();

            $envoyes = 0;
            $echecs = 0;

            foreach ($membresAvecEmail->get() as $membre) {
                try {
                    // Remplacer les variables dans le message et le sujet
                    $sujet = $this->remplacerVariables($validated['sujet'], $membre);
                    $message = $this->remplacerVariables($validated['message'], $membre);

                    // Créer le log avant l'envoi
                    $log = EmailLog::create([
                        'type' => EmailLog::TYPE_CAMPAGNE,
                        'campagne_id' => $campagne->id,
                        'membre_id' => $membre->id,
                        'destinataire_email' => $membre->email,
                        'sujet' => $sujet,
                        'message' => $message,
                        'statut' => EmailLog::STATUT_EN_ATTENTE,
                    ]);

                    // Envoyer l'email
                    \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($sujet, $membre) {
                        $mail->to($membre->email)
                             ->subject($sujet);
                    });

                    $log->markAsSent();
                    $envoyes++;
                } catch (\Exception $e) {
                    if (isset($log)) {
                        $log->markAsFailed($e->getMessage());
                    }
                    Log::error('Erreur envoi email campagne: ' . $e->getMessage());
                    $echecs++;
                }
            }

            // Mettre à jour la campagne
            $campagne->update([
                'statut' => 'terminee',
                'envoyes' => $envoyes,
                'echecs' => $echecs,
                'envoyee_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('campagnes.index')
                ->with('success', "Campagne envoyée avec succès ! {$envoyes} email(s) envoyé(s), {$echecs} échec(s).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création campagne: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'envoi de la campagne: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les membres selon les filtres
     */
    private function getMembresFromFilters(array $filtres)
    {
        $query = Membre::query();

        // Filtre par statut
        if (!empty($filtres['statut_membre']) && is_array($filtres['statut_membre'])) {
            $query->whereIn('statut', $filtres['statut_membre']);
        }

        // Filtre par cotisation
        if (!empty($filtres['cotisation_id'])) {
            $cotisationId = $filtres['cotisation_id'];
            $query->whereHas('paiements', function($q) use ($cotisationId) {
                $q->where('cotisation_id', $cotisationId);
            });
        }

        // Filtre par date d'adhésion
        if (!empty($filtres['date_adhesion_debut'])) {
            $query->whereDate('date_adhesion', '>=', $filtres['date_adhesion_debut']);
        }
        if (!empty($filtres['date_adhesion_fin'])) {
            $query->whereDate('date_adhesion', '<=', $filtres['date_adhesion_fin']);
        }

        return $query;
    }

    /**
     * Remplacer les variables dans le texte
     */
    private function remplacerVariables($texte, $membre)
    {
        $variables = [
            '{{nom}}' => $membre->nom ?? '',
            '{{prenom}}' => $membre->prenom ?? '',
            '{{nom_complet}}' => $membre->nom_complet ?? '',
            '{{email}}' => $membre->email ?? '',
            '{{telephone}}' => $membre->telephone ?? '',
            '{{adresse}}' => $membre->adresse ?? '',
            '{{date_adhesion}}' => $membre->date_adhesion ? $membre->date_adhesion->format('d/m/Y') : '',
        ];

        foreach ($variables as $key => $value) {
            $texte = str_replace($key, $value, $texte);
        }

        return $texte;
    }

    /**
     * Afficher les détails d'une campagne
     */
    public function show(EmailCampaign $campagne)
    {
        $campagne->load('creePar');
        $logs = EmailLog::where('campagne_id', $campagne->id)
            ->with('membre')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('campagnes.show', compact('campagne', 'logs'));
    }
}
