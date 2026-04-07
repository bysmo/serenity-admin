<?php

namespace App\Jobs;

use App\Models\Membre;
use App\Models\Cotisation;
use App\Models\NotificationLog;
use App\Services\EmailService;
use App\Notifications\UpcomingPaymentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendUpcomingPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $membre;
    public $cotisation;
    public $dateEcheance;
    public $joursAvant;

    /**
     * Create a new job instance.
     */
    public function __construct(Membre $membre, Cotisation $cotisation, Carbon $dateEcheance, $joursAvant)
    {
        $this->membre = $membre;
        $this->cotisation = $cotisation;
        $this->dateEcheance = $dateEcheance;
        $this->joursAvant = $joursAvant;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        try {
            $subject = "Rappel de paiement à venir - {$this->cotisation->nom}";
            $message = "Bonjour {$this->membre->prenom} {$this->membre->nom},\n\n";
            $message .= "Nous vous rappelons que votre prochain paiement pour la cotisation \"{$this->cotisation->nom}\" est prévu le {$this->dateEcheance->format('d/m/Y')}.\n\n";
            $message .= "Il vous reste {$this->joursAvant} jour(s) pour effectuer ce paiement.\n\n";
            
            if ($this->cotisation->type_montant === 'fixe' && $this->cotisation->montant) {
                $message .= "Montant à payer : " . number_format($this->cotisation->montant, 0, ',', ' ') . " XOF\n\n";
            }
            
            $message .= "Merci de prévoir le paiement de votre cotisation.\n\n";
            $message .= "Cordialement,\nL'équipe Serenity";

            // Créer le log de notification
            $log = NotificationLog::createLog(
                NotificationLog::TYPE_UPCOMING_PAYMENT,
                'membre',
                $this->membre->id,
                $this->membre->email,
                $subject,
                $message,
                [
                    'cotisation_id' => $this->cotisation->id,
                    'cotisation_nom' => $this->cotisation->nom,
                    'date_echeance' => $this->dateEcheance->format('Y-m-d'),
                    'jours_avant' => $this->joursAvant,
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
            $this->membre->notify(new UpcomingPaymentNotification($this->cotisation, $this->dateEcheance, $this->joursAvant));

            Log::info("Rappel de paiement à venir envoyé au membre {$this->membre->id} pour la cotisation {$this->cotisation->id}");
        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
            Log::error("Erreur lors de l'envoi du rappel de paiement à venir: " . $e->getMessage());
            throw $e;
        }
    }
}
