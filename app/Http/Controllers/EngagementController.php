<?php

namespace App\Http\Controllers;

use App\Models\Engagement;
use App\Models\Membre;
use App\Models\Cotisation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\SecurityHelper;

class EngagementController extends Controller
{
    /**
     * Afficher la liste des engagements
     */
    public function index(Request $request)
    {
        $query = Engagement::with(['membre', 'cotisation', 'cotisation.caisse']);
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero', 'like', SecurityHelper::likeSearch($search))
                  ->orWhereHas('membre', function($q) use ($search) {
                      $q->where('nom', 'like', SecurityHelper::likeSearch($search))
                        ->orWhere('prenom', 'like', SecurityHelper::likeSearch($search))
                        ->orWhere('email', 'like', SecurityHelper::likeSearch($search));
                  })
                  ->orWhereHas('cotisation', function($q) use ($search) {
                      $q->where('nom', 'like', SecurityHelper::likeSearch($search));
                  });
            });
        }
        
        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        
        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('periode_debut', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('periode_fin', '<=', $request->date_fin);
        }
        
        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $engagements = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return view('engagements.index', compact('engagements'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $membres = Membre::where('statut', 'actif')->orderBy('nom')->get();
        $cotisations = Cotisation::where('actif', true)->orderBy('nom')->get();
        
        // Récupérer tous les tags depuis la table tags
        $tags = \App\Models\Tag::where('type', 'engagement')
            ->orderBy('nom')
            ->pluck('nom')
            ->toArray();
        
        return view('engagements.create', compact('membres', 'cotisations', 'tags'));
    }

    /**
     * Générer un numéro d'engagement unique
     */
    private function generateNumeroEngagement(): string
    {
        do {
            $numero = 'ENG-' . strtoupper(Str::random(8));
        } while (Engagement::where('numero', $numero)->exists());

        return $numero;
    }

    /**
     * Enregistrer un nouvel engagement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'cotisation_id' => 'required|exists:cotisations,id',
            'montant_engage' => 'required|numeric|min:1',
            'periodicite' => 'required|in:mensuelle,trimestrielle,semestrielle,annuelle,unique',
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after:periode_debut',
            'statut' => 'required|in:en_cours,termine,annule',
            'tag' => 'nullable|string|max:255|exists:tags,nom',
            'notes' => 'nullable|string',
        ]);

        // Générer un numéro d'engagement unique
        $validated['numero'] = $this->generateNumeroEngagement();

        $engagement = Engagement::create($validated);

        // Envoyer un email au membre
        try {
            $emailService = new \App\Services\EmailService();
            $emailService->sendEngagementEmail($engagement);
        } catch (\Exception $e) {
            // Ne pas bloquer le processus si l'email échoue
            \Log::error('Erreur lors de l\'envoi de l\'email d\'engagement: ' . $e->getMessage());
        }

        return redirect()->route('engagements.index')
            ->with('success', 'Engagement créé avec succès.');
    }

    /**
     * Afficher les détails d'un engagement
     */
    public function show(Engagement $engagement)
    {
        $engagement->load(['membre', 'cotisation', 'cotisation.caisse']);
        
        // Calculer le montant payé
        $montantPaye = $engagement->montant_paye;
        $resteAPayer = $engagement->montant_engage - $montantPaye;
        
        return view('engagements.show', compact('engagement', 'montantPaye', 'resteAPayer'));
    }

    /**
     * Générer et afficher le PDF d'un engagement
     */
    public function pdf(Engagement $engagement)
    {
        $engagement->load(['membre', 'cotisation']);
        
        try {
            // Générer le PDF avec Snappy (meilleur support CSS) si disponible
            if (class_exists('\Barryvdh\Snappy\Facades\SnappyPdf')) {
                try {
                    $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadView('email-templates.pdf-engagement', compact('engagement'));
                    $pdf->setOption('page-size', 'A4');
                    $pdf->setOption('orientation', 'portrait');
                    $pdf->setOption('margin-left', '0mm');
                    $pdf->setOption('margin-right', '0mm');
                    $pdf->setOption('margin-top', '0mm');
                    $pdf->setOption('margin-bottom', '0mm');
                    $pdf->setOption('enable-local-file-access', true);
                    return $pdf->inline('details_engagement_' . $engagement->numero . '.pdf');
                } catch (\Exception $snappyError) {
                    // Si Snappy échoue, utiliser DomPDF comme fallback
                    \Log::warning('Erreur Snappy, utilisation DomPDF: ' . $snappyError->getMessage());
                }
            }
            
            // Générer le PDF avec DomPDF (fallback)
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('email-templates.pdf-engagement', compact('engagement'));
                $pdf->setPaper('A4', 'portrait');
                $response = response($pdf->output(), 200);
                $response->header('Content-Type', 'application/pdf');
                $response->header('Content-Disposition', 'inline; filename="details_engagement_' . $engagement->numero . '.pdf"');
                $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->header('Pragma', 'no-cache');
                $response->header('Expires', '0');
                return $response;
            } elseif (class_exists('\Dompdf\Dompdf')) {
                // Utilisation directe de DomPDF si disponible
                $dompdf = new \Dompdf\Dompdf();
                $html = view('email-templates.pdf-engagement', compact('engagement'))->render();
                
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                return response($dompdf->output(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="details_engagement_' . $engagement->numero . '.pdf"');
            } else {
                return redirect()->route('engagements.show', $engagement)
                    ->with('error', 'DomPDF n\'est pas installé. Installez avec: composer require barryvdh/laravel-dompdf');
            }
        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF engagement: ' . $e->getMessage());
            return redirect()->route('engagements.show', $engagement)
                ->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Engagement $engagement)
    {
        $membres = Membre::where('statut', 'actif')->orderBy('nom')->get();
        $cotisations = Cotisation::where('actif', true)->orderBy('nom')->get();
        
        // Récupérer tous les tags depuis la table tags
        $tags = \App\Models\Tag::where('type', 'engagement')
            ->orderBy('nom')
            ->pluck('nom')
            ->toArray();
        
        return view('engagements.edit', compact('engagement', 'membres', 'cotisations', 'tags'));
    }

    /**
     * Mettre à jour un engagement
     */
    public function update(Request $request, Engagement $engagement)
    {
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'cotisation_id' => 'required|exists:cotisations,id',
            'montant_engage' => 'required|numeric|min:1',
            'periodicite' => 'required|in:mensuelle,trimestrielle,semestrielle,annuelle,unique',
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after:periode_debut',
            'statut' => 'required|in:en_cours,termine,annule',
            'tag' => 'nullable|string|max:255|exists:tags,nom',
            'notes' => 'nullable|string',
        ]);

        $engagement->update($validated);

        return redirect()->route('engagements.index')
            ->with('success', 'Engagement mis à jour avec succès.');
    }

    /**
     * Supprimer un engagement
     */
    public function destroy(Engagement $engagement)
    {
        $engagement->delete();

        return redirect()->route('engagements.index')
            ->with('success', 'Engagement supprimé avec succès.');
    }
}
