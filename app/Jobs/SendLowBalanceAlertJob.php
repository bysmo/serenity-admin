<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Caisse;
use App\Models\NotificationLog;
use App\Services\EmailService;
use App\Notifications\LowBalanceAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLowBalanceAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $caisse;
    public $soldeActuel;
    public $seuil;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Caisse $caisse, $soldeActuel, $seuil)
    {
        $this->user = $user;
        $this->caisse = $caisse;
        $this->soldeActuel = $soldeActuel;
        $this->seuil = $seuil;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        try {
            $subject = "Alerte - Solde faible pour la caisse {$this->caisse->nom}";
            $message = "Bonjour {$this->user->name},\n\n";
            $message .= "La caisse \"{$this->caisse->nom}\" (Numéro: {$this->caisse->numero}) a un solde faible.\n\n";
            $message .= "Solde actuel : " . number_format($this->soldeActuel, 0, ',', ' ') . " XOF\n";
            $message .= "Seuil d'alerte : " . number_format($this->seuil, 0, ',', ' ') . " XOF\n\n";
            $message .= "Merci de prendre les mesures nécessaires pour approvisionner cette caisse.\n\n";
            $message .= "Cordialement,\nL'équipe Serenity";

            // Créer le log de notification
            $log = NotificationLog::createLog(
                NotificationLog::TYPE_LOW_BALANCE,
                'user',
                $this->user->id,
                $this->user->email,
                $subject,
                $message,
                [
                    'caisse_id' => $this->caisse->id,
                    'caisse_nom' => $this->caisse->nom,
                    'solde_actuel' => $this->soldeActuel,
                    'seuil' => $this->seuil,
                ]
            );

            // Envoyer l'email
            $emailService->configureSMTP();
            \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($subject) {
                $mail->to($this->user->email)
                     ->subject($subject);
            });

            // Marquer comme envoyé
            $log->markAsSent();

            // Envoyer notification interne à l'admin
            $this->user->notify(new LowBalanceAlertNotification($this->caisse, $this->soldeActuel, $this->seuil));

            Log::info("Alerte de solde faible envoyée à l'utilisateur {$this->user->id} pour la caisse {$this->caisse->id}");
        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
            Log::error("Erreur lors de l'envoi de l'alerte de solde faible: " . $e->getMessage());
            throw $e;
        }
    }
}
