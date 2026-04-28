<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Membre;
use App\Models\Cotisation;
use App\Models\Caisse;
use App\Models\Engagement;
use App\Models\MouvementCaisse;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaiementController extends Controller
{
    /**
     * Afficher la liste des paiements
     */
    public function index(Request $request)
    {
        $query = Paiement::with(['membre', 'cotisation', 'caisse']);
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('membre', function($q) use ($search) {
                      $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('cotisation', function($q) use ($search) {
                      $q->where('nom', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filtre par mode de paiement
        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', $request->mode_paiement);
        }
        
        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('date_paiement', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_paiement', '<=', $request->date_fin);
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $paiements = $query->orderBy('date_paiement', 'desc')->paginate($perPage);
        
        return view('paiements.index', compact('paiements'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $membres = Membre::where('statut', 'actif')->orderBy('nom')->get();
        $cotisations = Cotisation::where('actif', true)->orderBy('nom')->get();
        
        return view('paiements.create', compact('membres', 'cotisations'));
    }

    /**
     * Générer un numéro de paiement unique
     */
    private function generateNumeroPaiement(): string
    {
        try {
            return app(\App\Services\AutoNumberingService::class)->generate('transaction');
        } catch (\Exception $e) {
            do {
                $numero = 'PAY-' . strtoupper(Str::random(8));
            } while (Paiement::where('numero', $numero)->exists());

            return $numero;
        }
    }

    /**
     * Récupérer toutes les caisses pour le formulaire
     */
    public function getCaisses()
    {
        $caisses = Caisse::where('statut', 'active')
            ->orderBy('nom')
            ->get()
            ->map(function($caisse) {
                return [
                    'id' => $caisse->id,
                    'nom' => $caisse->nom,
                    'solde_actuel' => $caisse->solde_actuel,
                ];
            });
        
        return response()->json($caisses);
    }

    /**
     * Enregistrer un nouveau paiement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'cotisation_id' => 'required|exists:cotisations,id',
            'montant' => 'required|numeric|min:1',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money,autre',
            'notes' => 'nullable|string',
        ]);

        // Imposer la caisse selon la cotisation choisie (évite les erreurs côté admin)
        $cotisation = Cotisation::with('caisse')->findOrFail($validated['cotisation_id']);
        $validated['caisse_id'] = $cotisation->caisse_id;

        // Générer un numéro de paiement unique
        $validated['numero'] = $this->generateNumeroPaiement();

        // Créer le paiement
        $paiement = Paiement::create($validated);

        // Mettre à jour le solde du compte
        $caisse = Caisse::findOrFail($validated['caisse_id']);
        $caisse->solde_initial = $caisse->solde_initial + $validated['montant'];
        $caisse->save();

        // Journaliser le mouvement (entrée)
        MouvementCaisse::create([
            'caisse_id' => $caisse->id,
            'type' => 'paiement',
            'sens' => 'entree',
            'montant' => $validated['montant'],
            'date_operation' => $validated['date_paiement'],
            'libelle' => 'Paiement: ' . ($cotisation->nom ?? 'Cotisation'),
            'notes' => $validated['notes'] ?? null,
            'reference_type' => Paiement::class,
            'reference_id' => $paiement->id,
        ]);

        // Envoyer un email au membre
        try {
            $emailService = new EmailService();
            $emailService->sendPaymentEmail($paiement);
        } catch (\Exception $e) {
            // Ne pas bloquer le processus si l'email échoue
            \Log::error('Erreur lors de l\'envoi de l\'email de paiement: ' . $e->getMessage());
        }

        return redirect()->route('paiements.index')
            ->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Afficher les détails d'un paiement
     */
    public function show(Paiement $paiement)
    {
        $paiement->load(['membre', 'cotisation', 'caisse']);
        
        return view('paiements.show', compact('paiement'));
    }

    /**
     * Générer et afficher le PDF d'un paiement
     */
    public function pdf(Paiement $paiement)
    {
        // Vérifier si c'est un membre qui essaie d'accéder au PDF
        // Les membres ne peuvent télécharger que leurs propres reçus
        if (auth()->guard('membre')->check()) {
            $membre = auth()->guard('membre')->user();
            if ($paiement->membre_id !== $membre->id) {
                abort(403, 'Vous n\'avez pas accès à ce reçu de paiement.');
            }
        }
        
        $paiement->load(['membre', 'cotisation']);
        
        try {
            // Générer le PDF avec Snappy (meilleur support CSS) si disponible
            if (class_exists('\Barryvdh\Snappy\Facades\SnappyPdf')) {
                try {
                    $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadView('email-templates.pdf-paiement', compact('paiement'));
                    $pdf->setOption('page-size', 'A4');
                    $pdf->setOption('orientation', 'portrait');
                    $pdf->setOption('margin-left', '0mm');
                    $pdf->setOption('margin-right', '0mm');
                    $pdf->setOption('margin-top', '0mm');
                    $pdf->setOption('margin-bottom', '0mm');
                    $pdf->setOption('enable-local-file-access', true);
                    return $pdf->inline('recu_paiement_' . $paiement->numero . '.pdf');
                } catch (\Exception $snappyError) {
                    // Si Snappy échoue, utiliser DomPDF comme fallback
                    \Log::warning('Erreur Snappy, utilisation DomPDF: ' . $snappyError->getMessage());
                }
            }
            
            // Générer le PDF avec DomPDF (fallback)
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('email-templates.pdf-paiement', compact('paiement'));
                $pdf->setPaper('A4', 'portrait');
                $pdf->setOption('enable-html5-parser', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isFontSubsettingEnabled', true);
                $response = response($pdf->output(), 200);
                $response->header('Content-Type', 'application/pdf');
                $response->header('Content-Disposition', 'inline; filename="recu_paiement_' . $paiement->numero . '_' . time() . '.pdf"');
                $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
                $response->header('Pragma', 'no-cache');
                $response->header('Expires', '0');
                $response->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
                return $response;
            } elseif (class_exists('\Dompdf\Dompdf')) {
                // Utilisation directe de DomPDF si disponible
                $dompdf = new \Dompdf\Dompdf();
                $html = view('email-templates.pdf-paiement', compact('paiement'))->render();
                
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                return response($dompdf->output(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="recu_paiement_' . $paiement->numero . '.pdf"');
            } else {
                return redirect()->route('paiements.show', $paiement)
                    ->with('error', 'DomPDF n\'est pas installé. Installez avec: composer require barryvdh/laravel-dompdf');
            }
        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF paiement: ' . $e->getMessage());
            return redirect()->route('paiements.show', $paiement)
                ->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un paiement
     */
    public function destroy(Paiement $paiement)
    {
        // Retirer le montant du compte
        $caisse = $paiement->caisse;
        if ($caisse) {
            $caisse->solde_initial = $caisse->solde_initial - $paiement->montant;
            $caisse->save();
        }

        $paiement->delete();

        return redirect()->route('paiements.index')
            ->with('success', 'Paiement supprimé avec succès.');
    }

    /**
     * Afficher la liste des paiements d'engagements
     */
    public function indexEngagement(Request $request)
    {
        // Récupérer tous les engagements en cours
        $query = Engagement::with(['membre', 'cotisation', 'cotisation.caisse'])
            ->where('statut', 'en_cours');
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('membre', function($q) use ($search) {
                      $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('cotisation', function($q) use ($search) {
                      $q->where('nom', 'like', "%{$search}%");
                  });
            });
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $engagements = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('paiements.engagement-index', compact('engagements'));
    }

    /**
     * Afficher le formulaire de paiement pour un engagement
     */
    public function createEngagement(Engagement $engagement)
    {
        $engagement->load(['membre', 'cotisation', 'cotisation.caisse']);
        
        // Les accessors du modèle calculent automatiquement montant_total, montant_paye et reste_a_payer
        
        return view('paiements.engagement-create', compact('engagement'));
    }

    /**
     * Enregistrer un paiement d'engagement
     */
    public function storeEngagement(Request $request, Engagement $engagement)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|in:especes,cheque,virement,mobile_money,autre',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que le montant ne dépasse pas le reste à payer
        // Utiliser les accessors du modèle pour calculer le reste à payer
        $resteAPayer = $engagement->reste_a_payer;
        
        if ($validated['montant'] > $resteAPayer) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['montant' => 'Le montant dépasse le reste à payer (' . number_format($resteAPayer, 0, ',', ' ') . ' XOF).']);
        }

        // Récupérer la caisse de la cotisation
        $cotisation = $engagement->cotisation;
        $caisseId = $cotisation->caisse_id;

        // Générer un numéro de paiement unique
        $validated['numero'] = $this->generateNumeroPaiement();
        $validated['membre_id'] = $engagement->membre_id;
        $validated['cotisation_id'] = $engagement->cotisation_id;
        $validated['caisse_id'] = $caisseId;

        // Créer le paiement
        $paiement = Paiement::create($validated);

        // Mettre à jour le solde du compte
        $caisse = Caisse::findOrFail($caisseId);
        $caisse->solde_initial = $caisse->solde_initial + $validated['montant'];
        $caisse->save();

        // Journaliser le mouvement
        MouvementCaisse::create([
            'caisse_id' => $caisseId,
            'type' => 'paiement_engagement',
            'sens' => 'entree',
            'montant' => $validated['montant'],
            'date_operation' => $validated['date_paiement'],
            'libelle' => 'Paiement engagement - ' . $engagement->numero,
            'notes' => $validated['notes'] ?? null,
            'reference_type' => Paiement::class,
            'reference_id' => $paiement->id,
        ]);

        // Envoyer un email au membre
        try {
            $emailService = new EmailService();
            $emailService->sendPaymentEmail($paiement);
        } catch (\Exception $e) {
            // Ne pas bloquer le processus si l'email échoue
            \Log::error('Erreur lors de l\'envoi de l\'email de paiement: ' . $e->getMessage());
        }

        return redirect()->route('paiements.engagement.index')
            ->with('success', 'Paiement d\'engagement enregistré avec succès.');
    }
}
