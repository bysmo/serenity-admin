<?php

namespace App\Services;

use App\Models\SMTPConfiguration;
use App\Models\EmailTemplate;
use App\Models\Paiement;
use App\Models\Engagement;
use App\Models\EmailLog;
use App\Models\Membre;
use App\Models\NanoCredit;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class EmailService
{
    /**
     * Configurer Laravel Mail avec une configuration SMTP active
     */
    public function configureSMTP()
    {
        $smtp = SMTPConfiguration::where('actif', true)->first();
        
        if (!$smtp) {
            throw new \Exception('Aucune configuration SMTP active trouvée.');
        }

        try {
            $password = $smtp->password;
            
            // Configurer Laravel Mail avec la configuration SMTP
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $smtp->host,
                'mail.mailers.smtp.port' => $smtp->port,
                'mail.mailers.smtp.encryption' => $smtp->encryption !== 'none' ? $smtp->encryption : null,
                'mail.mailers.smtp.username' => $smtp->username,
                'mail.mailers.smtp.password' => $password,
                'mail.from.address' => $smtp->from_address,
                'mail.from.name' => $smtp->from_name,
            ]);

            // Forcer la réinitialisation du mailer
            // Forcer la réinitialisation du mailer pour prendre en compte la nouvelle config
            app()->forgetInstance('swift.mailer');
            app()->forgetInstance('mailer');
            app()->forgetInstance('mail.manager');

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la configuration SMTP: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la configuration SMTP: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer l'email de vérification de compte membre (utilise la config SMTP admin).
     * Si un template actif de type "membre_inscrit" existe, il est utilisé (avec {{lien_validation}}).
     * Sinon, message codé en dur avec le lien de validation.
     */
    public function sendVerificationEmail(Membre $membre): bool
    {
        try {
            $smtp = SMTPConfiguration::where('actif', true)->first();
            if (!$smtp) {
                Log::warning('Aucune configuration SMTP active. Email de vérification non envoyé pour le membre: ' . $membre->id);
                return false;
            }

            $this->configureSMTP();

            $appNom = \App\Models\AppSetting::get('app_nom', 'Serenity');
            $lienValidation = URL::temporarySignedRoute(
                'membre.verification.verify',
                Carbon::now()->addMinutes(60),
                [
                    'id' => $membre->getKey(),
                    'hash' => sha1($membre->getEmailForVerification()),
                ]
            );

            $template = EmailTemplate::where('type', 'membre_inscrit')
                ->where('actif', true)
                ->first();

            $emailLog = null;
            if ($template) {
                $variables = [
                    'nom' => $membre->nom ?? '',
                    'prenom' => $membre->prenom ?? '',
                    'email' => $membre->email ?? '',
                    'lien_validation' => $lienValidation,
                    'app_nom' => $appNom,
                ];
                $emailContent = $template->remplacerVariables($variables);
                $sujet = $emailContent['sujet'];
                $corps = $emailContent['corps'];

                $emailLog = EmailLog::create([
                    'type' => 'membre_inscrit',
                    'membre_id' => $membre->id,
                    'destinataire_email' => $membre->email,
                    'sujet' => $sujet,
                    'message' => $corps,
                    'statut' => EmailLog::STATUT_EN_ATTENTE,
                ]);
            } else {
                $sujet = "Vérifiez votre adresse email - {$appNom}";
                $corps = "Bonjour {$membre->prenom},\n\n"
                    . "Merci de vous être inscrit sur {$appNom}. Veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email.\n\n"
                    . $lienValidation . "\n\n"
                    . "Ce lien expirera dans 60 minutes. Si vous n'êtes pas à l'origine de cette inscription, vous pouvez ignorer cet email.";
            }

            Mail::raw($corps, function ($message) use ($membre, $sujet) {
                $message->to($membre->email)
                    ->subject($sujet);
            });

            if ($emailLog) {
                $emailLog->markAsSent();
            }

            Log::info('Email de vérification envoyé au membre: ' . $membre->email);
            return true;
        } catch (\Exception $e) {
            if (isset($emailLog) && $emailLog) {
                $emailLog->markAsFailed($e->getMessage());
            }
            Log::error('Erreur envoi email de vérification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoyer un email de test
     */
    public function sendTestEmail($to)
    {
        try {
            $this->configureSMTP();
            
            Mail::raw('Ceci est un email de test pour vérifier la configuration SMTP.', function ($message) use ($to) {
                $message->to($to)
                        ->subject('Test de configuration SMTP');
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de test: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoyer un email de paiement à un membre
     */
    public function sendPaymentEmail($paiement)
    {
        try {
            // Vérifier qu'il y a une configuration SMTP active
            $smtp = SMTPConfiguration::where('actif', true)->first();
            if (!$smtp) {
                Log::warning('Aucune configuration SMTP active. Email non envoyé pour le paiement: ' . $paiement->id);
                return false;
            }

            // Récupérer le template actif pour les paiements
            $template = EmailTemplate::where('type', 'paiement')
                ->where('actif', true)
                ->first();

            if (!$template) {
                Log::warning('Aucun template d\'email actif pour les paiements. Email non envoyé pour le paiement: ' . $paiement->id);
                return false;
            }

            // Charger les relations nécessaires
            $paiement->load(['membre', 'cotisation']);

            if (!$paiement->membre || !$paiement->membre->email) {
                Log::warning('Le membre n\'a pas d\'email. Email non envoyé pour le paiement: ' . $paiement->id);
                return false;
            }

            // Configurer SMTP
            $this->configureSMTP();

            // Préparer les variables
            $variables = [
                'nom' => $paiement->membre->nom ?? '',
                'prenom' => $paiement->membre->prenom ?? '',
                'date_paiement' => $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '',
                'montant' => number_format($paiement->montant, 0, ',', ' ') . ' XOF',
                'cotisation' => $paiement->cotisation->nom ?? '',
                'numero_paiement' => $paiement->numero ?? '',
                'mode_paiement' => ucfirst(str_replace('_', ' ', $paiement->mode_paiement ?? '')),
            ];

            // Remplacer les variables dans le template
            $emailContent = $template->remplacerVariables($variables);

            // Générer le PDF (optionnel, en cas d'erreur on envoie quand même l'email)
            $pdfPath = null;
            try {
                $pdfPath = $this->generatePaymentPDF($paiement);
            } catch (\Exception $pdfError) {
                Log::warning('Erreur génération PDF paiement: ' . $pdfError->getMessage() . '. Email envoyé sans PDF.');
            }

            // Créer le log avant l'envoi
            $emailLog = EmailLog::create([
                'type' => EmailLog::TYPE_PAIEMENT,
                'paiement_id' => $paiement->id,
                'membre_id' => $paiement->membre->id,
                'destinataire_email' => $paiement->membre->email,
                'sujet' => $emailContent['sujet'],
                'message' => $emailContent['corps'],
                'statut' => EmailLog::STATUT_EN_ATTENTE,
            ]);

            // Envoyer l'email avec le PDF attaché (si disponible)
            Mail::raw($emailContent['corps'], function ($message) use ($paiement, $emailContent, $pdfPath) {
                $message->to($paiement->membre->email)
                        ->subject($emailContent['sujet']);
                
                // Attacher le PDF si disponible
                if ($pdfPath && File::exists($pdfPath)) {
                    $message->attach($pdfPath, [
                        'as' => 'recu_paiement_' . $paiement->numero . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            // Supprimer le fichier PDF temporaire après l'envoi
            if ($pdfPath && File::exists($pdfPath)) {
                File::delete($pdfPath);
            }

            // Marquer le log comme envoyé
            $emailLog->markAsSent();

            Log::info('Email de paiement envoyé avec succès au membre: ' . $paiement->membre->email);
            return true;
        } catch (\Exception $e) {
            // Marquer le log comme échoué si il existe
            if (isset($emailLog)) {
                $emailLog->markAsFailed($e->getMessage());
            }
            Log::error('Erreur lors de l\'envoi de l\'email de paiement: ' . $e->getMessage());
            // Ne pas bloquer le processus si l'email échoue
            return false;
        }
    }

    /**
     * Générer le PDF pour un paiement
     */
    private function generatePaymentPDF(Paiement $paiement)
    {
        // Créer le dossier pour les PDFs si nécessaire
        $pdfDir = storage_path('app/temp_pdfs');
        if (!File::exists($pdfDir)) {
            File::makeDirectory($pdfDir, 0755, true);
        }
        
        // Nom du fichier PDF
        $filename = 'paiement_' . $paiement->id . '_' . now()->format('Y-m-d_His') . '.pdf';
        $pdfPath = $pdfDir . '/' . $filename;
        
        try {
            // Générer le PDF avec DomPDF
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('email-templates.pdf-paiement', compact('paiement'));
                $pdf->setPaper('A4', 'portrait');
                $pdf->setOption('enable-html5-parser', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isFontSubsettingEnabled', true);
                $pdf->save($pdfPath);
            } elseif (class_exists('\Dompdf\Dompdf')) {
                // Utilisation directe de DomPDF si disponible
                $dompdf = new \Dompdf\Dompdf();
                $html = view('email-templates.pdf-paiement', compact('paiement'))->render();
                
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                file_put_contents($pdfPath, $dompdf->output());
            } else {
                throw new \Exception('DomPDF n\'est pas installé. Installez avec: composer require barryvdh/laravel-dompdf');
            }
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF paiement: ' . $e->getMessage());
            throw $e;
        }
        
        return $pdfPath;
    }

    /**
     * Envoyer un email d'engagement à un membre
     */
    public function sendEngagementEmail(Engagement $engagement)
    {
        try {
            // Vérifier qu'il y a une configuration SMTP active
            $smtp = SMTPConfiguration::where('actif', true)->first();
            if (!$smtp) {
                Log::warning('Aucune configuration SMTP active. Email non envoyé pour l\'engagement: ' . $engagement->id);
                return false;
            }

            // Récupérer le template actif pour les engagements
            $template = EmailTemplate::where('type', 'engagement')
                ->where('actif', true)
                ->first();

            if (!$template) {
                Log::warning('Aucun template d\'email actif pour les engagements. Email non envoyé pour l\'engagement: ' . $engagement->id);
                return false;
            }

            // Charger les relations nécessaires
            $engagement->load(['membre', 'cotisation']);

            if (!$engagement->membre || !$engagement->membre->email) {
                Log::warning('Le membre n\'a pas d\'email. Email non envoyé pour l\'engagement: ' . $engagement->id);
                return false;
            }

            // Configurer SMTP
            $this->configureSMTP();

            // Préparer les variables
            $montantPaye = $engagement->montant_paye ?? 0;
            $resteAPayer = $engagement->montant_engage - $montantPaye;

            $variables = [
                'nom' => $engagement->membre->nom ?? '',
                'prenom' => $engagement->membre->prenom ?? '',
                'numero_engagement' => $engagement->numero ?? '',
                'cotisation' => $engagement->cotisation->nom ?? '',
                'montant_engage' => number_format($engagement->montant_engage, 0, ',', ' ') . ' XOF',
                'montant_paye' => number_format($montantPaye, 0, ',', ' ') . ' XOF',
                'reste_a_payer' => number_format($resteAPayer, 0, ',', ' ') . ' XOF',
                'periodicite' => ucfirst($engagement->periodicite ?? ''),
                'periode_debut' => $engagement->periode_debut ? $engagement->periode_debut->format('d/m/Y') : '',
                'periode_fin' => $engagement->periode_fin ? $engagement->periode_fin->format('d/m/Y') : '',
                'statut' => ucfirst(str_replace('_', ' ', $engagement->statut ?? '')),
            ];

            // Remplacer les variables dans le template
            $emailContent = $template->remplacerVariables($variables);

            // Générer le PDF (optionnel, en cas d'erreur on envoie quand même l'email)
            $pdfPath = null;
            try {
                $pdfPath = $this->generateEngagementPDF($engagement);
            } catch (\Exception $pdfError) {
                Log::warning('Erreur génération PDF engagement: ' . $pdfError->getMessage() . '. Email envoyé sans PDF.');
            }

            // Créer le log avant l'envoi
            $emailLog = EmailLog::create([
                'type' => EmailLog::TYPE_ENGAGEMENT,
                'engagement_id' => $engagement->id,
                'membre_id' => $engagement->membre->id,
                'destinataire_email' => $engagement->membre->email,
                'sujet' => $emailContent['sujet'],
                'message' => $emailContent['corps'],
                'statut' => EmailLog::STATUT_EN_ATTENTE,
            ]);

            // Envoyer l'email avec le PDF attaché (si disponible)
            Mail::raw($emailContent['corps'], function ($message) use ($engagement, $emailContent, $pdfPath) {
                $message->to($engagement->membre->email)
                        ->subject($emailContent['sujet']);
                
                // Attacher le PDF si disponible
                if ($pdfPath && File::exists($pdfPath)) {
                    $message->attach($pdfPath, [
                        'as' => 'details_engagement_' . $engagement->numero . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            // Supprimer le fichier PDF temporaire après l'envoi
            if ($pdfPath && File::exists($pdfPath)) {
                File::delete($pdfPath);
            }

            // Marquer le log comme envoyé
            $emailLog->markAsSent();

            Log::info('Email d\'engagement envoyé avec succès au membre: ' . $engagement->membre->email);
            return true;
        } catch (\Exception $e) {
            // Marquer le log comme échoué si il existe
            if (isset($emailLog)) {
                $emailLog->markAsFailed($e->getMessage());
            }
            Log::error('Erreur lors de l\'envoi de l\'email d\'engagement: ' . $e->getMessage());
            // Ne pas bloquer le processus si l'email échoue
            return false;
        }
    }

    /**
     * Envoyer un email au membre lorsque son nano crédit est octroyé.
     * Utilise le template actif de type "nano_credit_octroye" s'il existe, sinon aucun email n'est envoyé.
     */
    public function sendNanoCreditOctroyeEmail(NanoCredit $nanoCredit): bool
    {
        try {
            $smtp = SMTPConfiguration::where('actif', true)->first();
            if (!$smtp) {
                Log::warning('Aucune configuration SMTP active. Email nano crédit octroyé non envoyé pour le membre: ' . $nanoCredit->membre_id);
                return false;
            }

            $template = EmailTemplate::where('type', 'nano_credit_octroye')
                ->where('actif', true)
                ->first();

            if (!$template) {
                Log::debug('Aucun template actif pour nano_credit_octroye. Email non envoyé.');
                return false;
            }

            $nanoCredit->load(['membre', 'palier']);
            $membre = $nanoCredit->membre;
            if (!$membre || !$membre->email) {
                Log::warning('Le membre n\'a pas d\'email. Email nano crédit octroyé non envoyé.');
                return false;
            }

            $this->configureSMTP();

            $variables = [
                'nom' => $membre->nom ?? '',
                'prenom' => $membre->prenom ?? '',
                'email' => $membre->email ?? '',
                'montant' => number_format($nanoCredit->montant, 0, ',', ' ') . ' XOF',
                'type_nano' => $nanoCredit->palier ? $nanoCredit->palier->nom : '',
                'date_octroi' => $nanoCredit->date_octroi ? \Carbon\Carbon::parse($nanoCredit->date_octroi)->format('d/m/Y') : '',
            ];

            $emailContent = $template->remplacerVariables($variables);

            $emailLog = EmailLog::create([
                'type' => 'nano_credit_octroye',
                'membre_id' => $membre->id,
                'destinataire_email' => $membre->email,
                'sujet' => $emailContent['sujet'],
                'message' => $emailContent['corps'],
                'statut' => EmailLog::STATUT_EN_ATTENTE,
            ]);

            Mail::raw($emailContent['corps'], function ($message) use ($membre, $emailContent) {
                $message->to($membre->email)
                    ->subject($emailContent['sujet']);
            });

            $emailLog->markAsSent();
            Log::info('Email nano crédit octroyé envoyé au membre: ' . $membre->email);
            return true;
        } catch (\Exception $e) {
            if (isset($emailLog)) {
                $emailLog->markAsFailed($e->getMessage());
            }
            Log::error('Erreur envoi email nano crédit octroyé: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer le code OTP par email au membre (lors de l'inscription ou du renvoi OTP).
     * Si aucun SMTP n'est configuré, retourne false silencieusement.
     */
    public function sendOtpEmail(Membre $membre, string $code): bool
    {
        if (!$membre->email) {
            return false;
        }

        try {
            $smtp = SMTPConfiguration::where('actif', true)->first();
            if (!$smtp) {
                Log::info('sendOtpEmail: Aucune config SMTP active, OTP email non envoyé.', [
                    'membre_id' => $membre->id,
                ]);
                return false;
            }

            $this->configureSMTP();

            $appNom = \App\Models\AppSetting::get('app_nom', 'Serenity');
            $expireMinutes = (int) round(\App\Services\OtpService::TTL / 60);

            // Template HTML soigné pour l'email OTP
            $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Code de vérification – {$appNom}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
          <!-- En-tête -->
          <tr>
            <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:36px 40px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.5px;">{$appNom}</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Vérification de votre compte</p>
            </td>
          </tr>
          <!-- Corps -->
          <tr>
            <td style="padding:40px 40px 32px;">
              <p style="margin:0 0 16px;color:#374151;font-size:16px;">Bonjour <strong>{$membre->prenom}</strong>,</p>
              <p style="margin:0 0 28px;color:#6b7280;font-size:15px;line-height:1.6;">
                Voici votre code de vérification. Ce code est valable <strong>{$expireMinutes} minutes</strong> et ne doit être partagé avec personne.
              </p>
              <!-- Code OTP -->
              <div style="background:#f8f7ff;border:2px solid #4f46e5;border-radius:10px;padding:28px;text-align:center;margin:0 0 28px;">
                <p style="margin:0 0 8px;color:#6b7280;font-size:13px;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Votre code OTP</p>
                <p style="margin:0;color:#4f46e5;font-size:42px;font-weight:800;letter-spacing:12px;font-family:monospace;">{$code}</p>
              </div>
              <p style="margin:0 0 8px;color:#9ca3af;font-size:13px;">
                ⚠️ Si vous n'avez pas demandé ce code, vous pouvez ignorer cet email en toute sécurité.
              </p>
            </td>
          </tr>
          <!-- Pied de page -->
          <tr>
            <td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;text-align:center;">
              <p style="margin:0;color:#9ca3af;font-size:12px;">
                © {$appNom} — Ne jamais partager ce code.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

            $sujet = "[$appNom] Votre code de vérification : {$code}";

            Mail::html($html, function ($message) use ($membre, $sujet) {
                $message->to($membre->email)
                        ->subject($sujet);
            });

            Log::info('OTP email envoyé au membre.', [
                'membre_id' => $membre->id,
                'email'     => $membre->email,
            ]);

            return true;
        } catch (\Exception $e) {
            // L'OTP SMS reste valide même si l'email échoue
            Log::warning('sendOtpEmail: Erreur lors de l\'envoi de l\'email OTP.', [
                'membre_id' => $membre->id,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Générer le PDF pour un engagement
     */
    private function generateEngagementPDF(Engagement $engagement)
    {
        // Créer le dossier pour les PDFs si nécessaire
        $pdfDir = storage_path('app/temp_pdfs');
        if (!File::exists($pdfDir)) {
            File::makeDirectory($pdfDir, 0755, true);
        }
        
        // Nom du fichier PDF
        $filename = 'engagement_' . $engagement->id . '_' . now()->format('Y-m-d_His') . '.pdf';
        $pdfPath = $pdfDir . '/' . $filename;
        
        try {
            // Générer le PDF avec DomPDF
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('email-templates.pdf-engagement', compact('engagement'));
                $pdf->setPaper('A4', 'portrait');
                $pdf->setOption('enable-html5-parser', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isFontSubsettingEnabled', true);
                $pdf->save($pdfPath);
            } elseif (class_exists('\Dompdf\Dompdf')) {
                // Utilisation directe de DomPDF si disponible
                $dompdf = new \Dompdf\Dompdf();
                $html = view('email-templates.pdf-engagement', compact('engagement'))->render();
                
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                file_put_contents($pdfPath, $dompdf->output());
            } else {
                throw new \Exception('DomPDF n\'est pas installé. Installez avec: composer require barryvdh/laravel-dompdf');
            }
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF engagement: ' . $e->getMessage());
            throw $e;
        }
        
        return $pdfPath;
    }
}
