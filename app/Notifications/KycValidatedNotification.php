<?php

namespace App\Notifications;

use App\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycValidatedNotification extends Notification
{
    use Queueable;

    public function __construct(public KycVerification $kyc)
    {
    }

    public function via(object $notifiable): array
    {
        // Vérifier si SMTP est configuré avant d'inclure le canal mail
        try {
            $smtp = \App\Models\SMTPConfiguration::where('actif', true)->first();
            if (!$smtp) {
                return ['database'];
            }
            return ['mail', 'database'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('KycValidatedNotification: SMTP check failed, falling back to database only.', ['error' => $e->getMessage()]);
            return ['database'];
        }
    }

    public function toMail(object $notifiable): MailMessage
    {
        try {
            (new \App\Services\EmailService())->configureSMTP();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('KycValidatedNotification: SMTP config failed, sending default mail.', ['error' => $e->getMessage()]);
            // Continuer sans reconfigurer — utilise la config mail par défaut du .env
        }

        $appNom = \App\Models\AppSetting::get('app_nom', 'Serenity');

        return (new MailMessage)
            ->subject("Votre KYC a été validé - {$appNom}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Nous vous informons que votre dossier KYC a été validé par l'administration.")
            ->line("Vous pouvez désormais effectuer une demande de nano crédit depuis votre espace membre.")
            ->action('Accéder à mon espace', route('membre.dashboard'))
            ->line('Merci de votre confiance.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kyc_validated',
            'title' => 'KYC validé',
            'message' => 'Votre dossier KYC a été validé. Vous pouvez désormais faire une demande de nano crédit.',
        ];
    }
}
