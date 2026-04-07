<?php

namespace App\Jobs;

use App\Models\Membre;
use App\Models\Engagement;
use App\Models\NotificationLog;
use App\Services\EmailService;
use App\Notifications\EngagementDueNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendEngagementReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $membre;
    public $engagement;
    public $joursRestants;

    /**
     * Create a new job instance.
     */
    public function __construct(Membre $membre, Engagement $engagement, $joursRestants)
    {
        $this->membre = $membre;
        $this->engagement = $engagement;
        $this->joursRestants = $joursRestants;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        try {
            $dateFin = Carbon::parse($this->engagement->periode_fin);
            $cotisation = $this->engagement->cotisation;

            $subject = "Rappel - Engagement arrivant à échéance";
            $message = "Bonjour {$this->membre->prenom} {$this->membre->nom},\n\n";
            $message .= "Votre engagement pour la cotisation \"{$cotisation->nom}\" arrive à échéance dans {$this->joursRestants} jour(s).\n\n";
            $message .= "Date d'échéance : {$dateFin->format('d/m/Y')}\n";
            $message .= "Montant engagé : " . number_format($this->engagement->montant_engage, 0, ',', ' ') . " XOF\n";
            $message .= "Périodicité : " . ucfirst($this->engagement->periodicite) . "\n\n";
            $message .= "Merci de prévoir le paiement de votre engagement.\n\n";
            $message .= "Cordialement,\nL'équipe Serenity";

            // Créer le log de notification
            $log = NotificationLog::createLog(
                NotificationLog::TYPE_ENGAGEMENT_DUE,
                'membre',
                $this->membre->id,
                $this->membre->email,
                $subject,
                $message,
                [
                    'engagement_id' => $this->engagement->id,
                    'cotisation_id' => $cotisation->id,
                    'cotisation_nom' => $cotisation->nom,
                    'date_fin' => $dateFin->format('Y-m-d'),
                    'jours' => $this->joursRestants,
                ]
            );

            // Envoyer l'email
            $emailService->configureSMTP();
            \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($subject) {
                $mail->to($this->membre->email)
                     ->subject($subject);
            });

            // Marquer comme envoyé
            $log->markAsSent();

            // Envoyer notification interne au membre
            $this->membre->notify(new EngagementDueNotification($this->engagement, $this->joursRestants));

            Log::info("Rappel d'engagement envoyé au membre {$this->membre->id} pour l'engagement {$this->engagement->id}");
        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
            Log::error("Erreur lors de l'envoi du rappel d'engagement: " . $e->getMessage());
            throw $e;
        }
    }
}
