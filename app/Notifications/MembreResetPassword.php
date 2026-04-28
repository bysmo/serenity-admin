<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class MembreResetPassword extends ResetPassword
{
    /**
     * Get the reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        return route('membre.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Configurer le SMTP dynamique depuis la base de données
        try {
            if (class_exists(\App\Services\EmailService::class)) {
                app(\App\Services\EmailService::class)->configureSMTP();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('SMTP non configuré pour ResetPassword : ' . $e->getMessage());
        }

        $appNom = \App\Models\AppSetting::get('app_nom', 'Serenity');
        $url = $this->resetUrl($notifiable);
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        // Tentative de récupération du template depuis la base de données
        $template = \App\Models\EmailTemplate::where('type', 'forgot_password')
            ->where('actif', true)
            ->first();

        if ($template) {
            $data = [
                'prenom' => $notifiable->prenom ?? 'Membre',
                'nom' => $notifiable->nom ?? '',
                'nom_site' => $appNom,
                'url_reset' => $url,
                'expire' => $expireMinutes,
                'annee' => date('Y'),
            ];
            $rendered = $template->remplacerVariables($data);
            
            // Si le template contient du HTML, on l'utilise directement via une vue brute
            if (str_contains($rendered['corps'], '<html') || str_contains($rendered['corps'], '<body')) {
                return (new MailMessage)
                    ->subject($rendered['sujet'])
                    ->view('emails.raw', ['content' => $rendered['corps']]);
            }

            $mail = (new MailMessage)
                ->subject($rendered['sujet']);
            
            // Sinon on utilise le format standard par lignes
            $lines = explode("\n", $rendered['corps']);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '{{action_button}}') {
                    $mail->action(Lang::get('Réinitialiser le mot de passe'), $url);
                } elseif (!empty($line)) {
                    $mail->line($line);
                }
            }
            return $mail;
        }

        // Fallback si le template n'existe pas
        return (new MailMessage)
            ->subject(Lang::get('Réinitialisation de mot de passe - ' . $appNom))
            ->greeting('Bonjour ' . ($notifiable->prenom ?? 'Membre') . ',')
            ->line(Lang::get('Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.'))
            ->action(Lang::get('Réinitialiser le mot de passe'), $url)
            ->line(Lang::get('Ce lien de réinitialisation expirera dans :count minutes.', ['count' => $expireMinutes]))
            ->line(Lang::get('Si vous n\'avez pas demandé de réinitialisation de mot de passe, aucune action supplémentaire n\'est requise.'));
    }
}
