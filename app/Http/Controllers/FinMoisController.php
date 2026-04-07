<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use App\Models\FinMoisLog;
use App\Models\Paiement;
use App\Models\Engagement;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class FinMoisController extends Controller
{
    /**
     * Afficher la page de traitement de fin de mois
     */
    public function index()
    {
        return view('fin-mois.index');
    }

    /**
     * Traiter les membres pour la fin de mois
     */
    public function process(Request $request, EmailService $emailService)
    {
        $request->validate([
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after_or_equal:periode_debut',
            'membre_id' => 'nullable|exists:membres,id', // Optionnel: traiter un seul membre
        ]);

        $periodeDebut = Carbon::parse($request->periode_debut);
        $periodeFin = Carbon::parse($request->periode_fin);
        
        // Générer un token pour suivre la progression
        $processToken = $request->input('process_token', 'process_' . time() . '_' . uniqid());
        
        // Stocker les informations de progression dans le cache
        \Illuminate\Support\Facades\Cache::put('fin_mois_progress_' . $processToken, [
            'total' => 0,
            'current' => 0,
            'progress' => 0,
            'message' => 'Initialisation...',
            'completed' => false,
        ], now()->addMinutes(10));

        // Récupérer les membres à traiter
        $query = Membre::where('statut', 'actif');
        
        if ($request->filled('membre_id')) {
            $query->where('id', $request->membre_id);
        }

        $membres = $query->get();
        
        if ($membres->isEmpty()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Aucun membre actif trouvé.'], 400);
            }
            return redirect()->route('fin-mois.index')
                ->with('error', 'Aucun membre actif trouvé.');
        }

        // Filtrer les membres qui n'ont pas déjà été traités pour cette période
        $membresATraiter = $membres->filter(function($membre) use ($periodeDebut, $periodeFin) {
            return !FinMoisLog::dejaEnvoye($membre->id, $periodeDebut, $periodeFin);
        });
        
        $totalMembres = $membresATraiter->count();
        
        if ($totalMembres === 0) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Tous les membres ont déjà été traités pour cette période.'], 400);
            }
            return redirect()->route('fin-mois.index')
                ->with('error', 'Tous les membres ont déjà été traités pour cette période.');
        }

        // Mettre à jour le total dans le cache
        \Illuminate\Support\Facades\Cache::put('fin_mois_progress_' . $processToken, [
            'total' => $totalMembres,
            'current' => 0,
            'progress' => 0,
            'message' => 'Démarrage du traitement...',
            'completed' => false,
        ], now()->addMinutes(10));

        $succes = 0;
        $echecs = 0;
        $currentIndex = 0;

        DB::beginTransaction();
        
        try {
            foreach ($membresATraiter as $membre) {
                $currentIndex++;
                
                // Mettre à jour la progression
                $progress = ($currentIndex / $totalMembres) * 100;
                \Illuminate\Support\Facades\Cache::put('fin_mois_progress_' . $processToken, [
                    'total' => $totalMembres,
                    'current' => $currentIndex,
                    'progress' => $progress,
                    'message' => "Traitement du membre {$currentIndex}/{$totalMembres}: {$membre->prenom} {$membre->nom}",
                    'completed' => false,
                ], now()->addMinutes(10));
                // Vérifier si un email a déjà été envoyé pour cette période
                if (FinMoisLog::dejaEnvoye($membre->id, $periodeDebut, $periodeFin)) {
                    continue; // Passer ce membre
                }

                // Récupérer les paiements de la période
                $paiements = Paiement::where('membre_id', $membre->id)
                    ->whereBetween('date_paiement', [$periodeDebut, $periodeFin])
                    ->with(['cotisation', 'caisse'])
                    ->orderBy('date_paiement', 'asc')
                    ->get();

                // Récupérer les engagements actifs du membre
                $engagements = Engagement::where('membre_id', $membre->id)
                    ->where('statut', 'actif')
                    ->with(['cotisation'])
                    ->get();

                // Calculer les statistiques
                $montantTotal = $paiements->sum('montant');
                $nombrePaiements = $paiements->count();

                // Préparer le résumé
                $resumePaiements = [
                    'paiements' => $paiements->map(function($p) {
                        return [
                            'numero' => $p->numero,
                            'date' => $p->date_paiement->format('d/m/Y'),
                            'cotisation' => $p->cotisation->nom ?? '',
                            'montant' => number_format($p->montant, 0, ',', ' ') . ' XOF',
                            'mode_paiement' => ucfirst(str_replace('_', ' ', $p->mode_paiement ?? '')),
                        ];
                    })->toArray(),
                    'engagements' => $engagements->map(function($e) {
                        return [
                            'numero' => $e->numero,
                            'cotisation' => $e->cotisation->nom ?? '',
                            'montant_engage' => number_format($e->montant_engage, 0, ',', ' ') . ' XOF',
                            'montant_paye' => number_format($e->montant_paye ?? 0, 0, ',', ' ') . ' XOF',
                            'reste_a_payer' => number_format(($e->montant_engage - ($e->montant_paye ?? 0)), 0, ',', ' ') . ' XOF',
                            'periodicite' => ucfirst($e->periodicite ?? ''),
                        ];
                    })->toArray(),
                ];

                // Générer le contenu de l'email
                $emailContent = $this->generateEmailContent($membre, $periodeDebut, $periodeFin, $paiements, $engagements, $montantTotal, $nombrePaiements);

                // Générer le PDF
                $pdfPath = $this->generatePDF($membre, $periodeDebut, $periodeFin, $paiements, $engagements, $montantTotal, $nombrePaiements);

                // Créer le log
                $log = FinMoisLog::create([
                    'membre_id' => $membre->id,
                    'periode_debut' => $periodeDebut,
                    'periode_fin' => $periodeFin,
                    'email_destinataire' => $membre->email ?? '',
                    'sujet_email' => $emailContent['sujet'],
                    'corps_email' => $emailContent['corps'],
                    'statut' => 'en_attente',
                    'envoye_par' => auth()->id(),
                    'resume_paiements' => $resumePaiements,
                    'nombre_paiements' => $nombrePaiements,
                    'montant_total' => $montantTotal,
                ]);

                // Envoyer l'email avec le PDF en pièce jointe si le membre a un email
                if ($membre->email) {
                    try {
                        $emailService->configureSMTP();
                        
                        $messageText = "Bonjour {$membre->prenom} {$membre->nom},\n\n";
                        $messageText .= "Vous trouverez ci-joint votre récapitulatif des paiements pour la période du ";
                        $messageText .= $periodeDebut->format('d/m/Y') . " au " . $periodeFin->format('d/m/Y') . ".\n\n";
                        $messageText .= "Cordialement,\nL'équipe Serenity";
                        
                        \Illuminate\Support\Facades\Mail::raw($messageText, function ($message) use ($membre, $emailContent, $pdfPath) {
                            $message->to($membre->email)
                                    ->subject($emailContent['sujet'])
                                    ->attach($pdfPath, [
                                        'as' => 'recapitulatif_paiements_' . now()->format('Y-m') . '.pdf',
                                        'mime' => 'application/pdf',
                                    ]);
                        });

                        // Supprimer le fichier PDF temporaire après l'envoi
                        if (File::exists($pdfPath)) {
                            File::delete($pdfPath);
                        }

                        $log->update([
                            'statut' => 'envoye',
                            'envoye_at' => now(),
                        ]);
                        
                        $succes++;
                    } catch (\Exception $e) {
                        // Supprimer le fichier PDF en cas d'erreur
                        if (isset($pdfPath) && File::exists($pdfPath)) {
                            File::delete($pdfPath);
                        }
                        
                        $log->update([
                            'statut' => 'echec',
                            'erreur' => $e->getMessage(),
                        ]);
                        
                        Log::error('Erreur envoi email fin de mois: ' . $e->getMessage());
                        $echecs++;
                    }
                } else {
                    // Supprimer le PDF si pas d'email
                    if (isset($pdfPath) && File::exists($pdfPath)) {
                        File::delete($pdfPath);
                    }
                    
                    $log->update([
                        'statut' => 'echec',
                        'erreur' => 'Le membre n\'a pas d\'email configuré',
                    ]);
                    $echecs++;
                }
            }

            DB::commit();

            // Marquer comme terminé
            \Illuminate\Support\Facades\Cache::put('fin_mois_progress_' . $processToken, [
                'total' => $totalMembres,
                'current' => $totalMembres,
                'progress' => 100,
                'message' => "Traitement terminé. {$succes} email(s) envoyé(s) avec succès." . ($echecs > 0 ? " {$echecs} échec(s)." : ''),
                'completed' => true,
                'succes' => $succes,
                'echecs' => $echecs,
            ], now()->addMinutes(10));

            $message = "Traitement terminé. {$succes} email(s) envoyé(s) avec succès.";
            if ($echecs > 0) {
                $message .= " {$echecs} échec(s).";
            }

            // Si c'est une requête AJAX, retourner JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'token' => $processToken,
                ]);
            }

            return redirect()->route('fin-mois.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur traitement fin de mois: ' . $e->getMessage());

            // Marquer comme terminé avec erreur
            \Illuminate\Support\Facades\Cache::put('fin_mois_progress_' . $processToken, [
                'total' => $totalMembres ?? 0,
                'current' => $currentIndex ?? 0,
                'progress' => 0,
                'message' => 'Erreur lors du traitement: ' . $e->getMessage(),
                'completed' => true,
                'error' => true,
            ], now()->addMinutes(10));

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du traitement: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('fin-mois.index')
                ->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    /**
     * Générer le PDF du récapitulatif
     */
    private function generatePDF($membre, $periodeDebut, $periodeFin, $paiements, $engagements, $montantTotal, $nombrePaiements)
    {
        // Créer le dossier pour les PDFs si nécessaire
        $pdfDir = storage_path('app/temp_pdfs');
        if (!File::exists($pdfDir)) {
            File::makeDirectory($pdfDir, 0755, true);
        }
        
        // Nom du fichier PDF
        $filename = 'recapitulatif_' . $membre->id . '_' . now()->format('Y-m-d_His') . '.pdf';
        $pdfPath = $pdfDir . '/' . $filename;
        
        try {
            // Générer le PDF avec DomPDF
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('fin-mois.pdf-recapitulatif', compact(
                    'membre', 'periodeDebut', 'periodeFin', 'paiements', 
                    'engagements', 'montantTotal', 'nombrePaiements'
                ));
                $pdf->setPaper('A4', 'portrait');
                $pdf->save($pdfPath);
            } elseif (class_exists('\Dompdf\Dompdf')) {
                // Utilisation directe de DomPDF si disponible
                $dompdf = new \Dompdf\Dompdf();
                $html = view('fin-mois.pdf-recapitulatif', compact(
                    'membre', 'periodeDebut', 'periodeFin', 'paiements', 
                    'engagements', 'montantTotal', 'nombrePaiements'
                ))->render();
                
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                file_put_contents($pdfPath, $dompdf->output());
            } else {
                throw new \Exception('DomPDF n\'est pas installé. Installez avec: composer require barryvdh/laravel-dompdf');
            }
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF: ' . $e->getMessage());
            throw $e;
        }
        
        return $pdfPath;
    }

    /**
     * Générer le contenu de l'email
     */
    private function generateEmailContent($membre, $periodeDebut, $periodeFin, $paiements, $engagements, $montantTotal, $nombrePaiements)
    {
        $sujet = "Récapitulatif des paiements - " . $periodeDebut->format('F Y');
        
        $corps = "Bonjour {$membre->prenom} {$membre->nom},\n\n";
        $corps .= "Vous trouverez ci-dessous le récapitulatif de vos paiements pour la période du ";
        $corps .= $periodeDebut->format('d/m/Y') . " au " . $periodeFin->format('d/m/Y') . ".\n\n";
        
        $corps .= "=== RÉSUMÉ ===\n";
        $corps .= "Nombre de paiements : {$nombrePaiements}\n";
        $corps .= "Montant total payé : " . number_format($montantTotal, 0, ',', ' ') . " XOF\n\n";
        
        if ($paiements->count() > 0) {
            $corps .= "=== DÉTAIL DES PAIEMENTS ===\n";
            foreach ($paiements as $paiement) {
                $corps .= "• Date : " . $paiement->date_paiement->format('d/m/Y') . "\n";
                $corps .= "  Cotisation : " . ($paiement->cotisation->nom ?? 'N/A') . "\n";
                $corps .= "  Montant : " . number_format($paiement->montant, 0, ',', ' ') . " XOF\n";
                $corps .= "  Mode de paiement : " . ucfirst(str_replace('_', ' ', $paiement->mode_paiement ?? '')) . "\n";
                $corps .= "  Numéro : " . $paiement->numero . "\n\n";
            }
        }
        
        if ($engagements->count() > 0) {
            $corps .= "=== VOS ENGAGEMENTS EN COURS ===\n";
            foreach ($engagements as $engagement) {
                $montantPaye = $engagement->montant_paye ?? 0;
                $resteAPayer = $engagement->montant_engage - $montantPaye;
                
                $corps .= "• Cotisation : " . ($engagement->cotisation->nom ?? 'N/A') . "\n";
                $corps .= "  Montant engagé : " . number_format($engagement->montant_engage, 0, ',', ' ') . " XOF\n";
                $corps .= "  Montant payé : " . number_format($montantPaye, 0, ',', ' ') . " XOF\n";
                $corps .= "  Reste à payer : " . number_format($resteAPayer, 0, ',', ' ') . " XOF\n";
                $corps .= "  Périodicité : " . ucfirst($engagement->periodicite ?? '') . "\n\n";
            }
        }
        
        $corps .= "Cordialement,\nL'équipe Serenity";
        
        return [
            'sujet' => $sujet,
            'corps' => $corps,
        ];
    }

    /**
     * Afficher le journal d'envoi
     */
    public function journal(Request $request)
    {
        $query = FinMoisLog::with(['membre', 'envoyePar']);

        // Filtre par membre
        if ($request->filled('membre_id')) {
            $query->where('membre_id', $request->membre_id);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par période
        if ($request->filled('periode')) {
            $mois = Carbon::createFromFormat('Y-m', $request->periode);
            $query->where('periode_debut', '>=', $mois->copy()->startOfMonth())
                  ->where('periode_fin', '<=', $mois->copy()->endOfMonth());
        }

        $perPage = \App\Models\AppSetting::get('pagination_par_page', 15);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        $membres = Membre::where('statut', 'actif')->orderBy('nom')->get();

        return view('fin-mois.journal', compact('logs', 'membres'));
    }

    /**
     * Aperçu du PDF qui sera envoyé
     */
    public function preview(Request $request)
    {
        $request->validate([
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after_or_equal:periode_debut',
            'membre_id' => 'required|exists:membres,id',
        ]);

        $periodeDebut = Carbon::parse($request->periode_debut);
        $periodeFin = Carbon::parse($request->periode_fin);
        $membre = Membre::findOrFail($request->membre_id);

        // Récupérer les paiements de la période
        $paiements = Paiement::where('membre_id', $membre->id)
            ->whereBetween('date_paiement', [$periodeDebut, $periodeFin])
            ->with(['cotisation', 'caisse'])
            ->orderBy('date_paiement', 'asc')
            ->get();

        // Récupérer les engagements actifs du membre
        $engagements = Engagement::where('membre_id', $membre->id)
            ->where('statut', 'actif')
            ->with(['cotisation'])
            ->get();

        // Calculer les statistiques
        $montantTotal = $paiements->sum('montant');
        $nombrePaiements = $paiements->count();

        try {
            // Générer le PDF avec Snappy (meilleur support CSS) si disponible
            if (class_exists('\Barryvdh\Snappy\Facades\SnappyPdf')) {
                try {
                    $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadView('fin-mois.pdf-recapitulatif', compact(
                        'membre', 'periodeDebut', 'periodeFin', 'paiements', 
                        'engagements', 'montantTotal', 'nombrePaiements'
                    ));
                    $pdf->setOption('page-size', 'A4');
                    $pdf->setOption('orientation', 'portrait');
                    $pdf->setOption('margin-left', '0mm');
                    $pdf->setOption('margin-right', '0mm');
                    $pdf->setOption('margin-top', '0mm');
                    $pdf->setOption('margin-bottom', '0mm');
                    $pdf->setOption('enable-local-file-access', true);
                    $filename = 'apercu_recapitulatif_' . $membre->id . '_' . time() . '.pdf';
                    return $pdf->inline($filename);
                } catch (\Exception $snappyError) {
                    // Si Snappy échoue, utiliser DomPDF comme fallback
                    \Log::warning('Erreur Snappy, utilisation DomPDF: ' . $snappyError->getMessage());
                }
            }
            
            // Générer le PDF avec DomPDF (fallback)
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('fin-mois.pdf-recapitulatif', compact(
                    'membre', 'periodeDebut', 'periodeFin', 'paiements', 
                    'engagements', 'montantTotal', 'nombrePaiements'
                ));
                $pdf->setPaper('A4', 'portrait');
                $pdf->setOption('enable-html5-parser', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isFontSubsettingEnabled', true);
                $filename = 'apercu_recapitulatif_' . $membre->id . '_' . time() . '.pdf';
                $response = response($pdf->output(), 200);
                $response->header('Content-Type', 'application/pdf');
                $response->header('Content-Disposition', 'inline; filename="' . $filename . '"');
                $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
                $response->header('Pragma', 'no-cache');
                $response->header('Expires', '0');
                $response->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
                return $response;
            } elseif (class_exists('\Dompdf\Dompdf')) {
                // Utilisation directe de DomPDF si disponible
                $dompdf = new \Dompdf\Dompdf();
                $html = view('fin-mois.pdf-recapitulatif', compact(
                    'membre', 'periodeDebut', 'periodeFin', 'paiements', 
                    'engagements', 'montantTotal', 'nombrePaiements'
                ))->render();
                
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                $filename = 'apercu_recapitulatif_' . $membre->id . '_' . time() . '.pdf';
                return response($dompdf->output(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0')
                    ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
                    ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            } else {
                return redirect()->route('fin-mois.index')
                    ->with('error', 'DomPDF n\'est pas installé. Installez avec: composer require barryvdh/laravel-dompdf');
            }
        } catch (\Exception $e) {
            Log::error('Erreur génération aperçu PDF: ' . $e->getMessage());
            return redirect()->route('fin-mois.index')
                ->with('error', 'Erreur lors de la génération de l\'aperçu: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer la progression du traitement
     */
    public function progress(Request $request)
    {
        $token = $request->input('token');
        
        if (!$token) {
            return response()->json([
                'progress' => 0,
                'message' => 'Token manquant',
                'completed' => false,
            ], 400);
        }
        
        $progressData = \Illuminate\Support\Facades\Cache::get('fin_mois_progress_' . $token);
        
        if (!$progressData) {
            return response()->json([
                'progress' => 0,
                'message' => 'Progression non trouvée',
                'completed' => false,
            ], 404);
        }
        
        return response()->json($progressData);
    }

    /**
     * Réenvoyer un email
     */
    public function resend(FinMoisLog $log, EmailService $emailService)
    {
        if (!$log->membre->email) {
            return redirect()->route('fin-mois.journal')
                ->with('error', 'Le membre n\'a pas d\'email configuré.');
        }

        try {
            // Récupérer les données pour régénérer le PDF
            $membre = $log->membre;
            $periodeDebut = Carbon::parse($log->periode_debut);
            $periodeFin = Carbon::parse($log->periode_fin);
            
            $paiements = Paiement::where('membre_id', $membre->id)
                ->whereBetween('date_paiement', [$periodeDebut, $periodeFin])
                ->with(['cotisation', 'caisse'])
                ->orderBy('date_paiement', 'asc')
                ->get();
            
            $engagements = Engagement::where('membre_id', $membre->id)
                ->where('statut', 'actif')
                ->with(['cotisation'])
                ->get();
            
            $montantTotal = $paiements->sum('montant');
            $nombrePaiements = $paiements->count();
            
            // Générer le PDF
            $pdfPath = $this->generatePDF($membre, $periodeDebut, $periodeFin, $paiements, $engagements, $montantTotal, $nombrePaiements);
            
            $emailService->configureSMTP();
            
            $messageText = "Bonjour {$membre->prenom} {$membre->nom},\n\n";
            $messageText .= "Vous trouverez ci-joint votre récapitulatif des paiements pour la période du ";
            $messageText .= $periodeDebut->format('d/m/Y') . " au " . $periodeFin->format('d/m/Y') . ".\n\n";
            $messageText .= "Cordialement,\nL'équipe Serenity";
            
            \Illuminate\Support\Facades\Mail::raw($messageText, function ($message) use ($log, $pdfPath) {
                $message->to($log->email_destinataire)
                        ->subject($log->sujet_email)
                        ->attach($pdfPath, [
                            'as' => 'recapitulatif_paiements_' . now()->format('Y-m') . '.pdf',
                            'mime' => 'application/pdf',
                        ]);
            });

            // Supprimer le fichier PDF temporaire après l'envoi
            if (File::exists($pdfPath)) {
                File::delete($pdfPath);
            }

            $log->update([
                'statut' => 'envoye',
                'envoye_at' => now(),
                'envoye_par' => auth()->id(),
            ]);

            return redirect()->route('fin-mois.journal')
                ->with('success', 'Email renvoyé avec succès.');

        } catch (\Exception $e) {
            // Supprimer le PDF en cas d'erreur
            if (isset($pdfPath) && File::exists($pdfPath)) {
                File::delete($pdfPath);
            }
            
            Log::error('Erreur renvoi email fin de mois: ' . $e->getMessage());
            
            $log->update([
                'statut' => 'echec',
                'erreur' => $e->getMessage(),
            ]);

            return redirect()->route('fin-mois.journal')
                ->with('error', 'Erreur lors du renvoi: ' . $e->getMessage());
        }
    }
}
