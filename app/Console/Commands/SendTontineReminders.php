<?php

namespace App\Console\Commands;

use App\Models\EpargneEcheance;
use App\Models\NotificationLog;
use App\Services\EmailService;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTontineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tontine:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie les rappels de paiement pour les tontines selon la configuration des plans.';

    /**
     * Execute the console command.
     */
    public function handle(PushNotificationService $pushService, EmailService $emailService)
    {
        $this->info('Début de l\'envoi des rappels de tontine...');

        // 1. Récupérer les échéances non payées (a_venir ou en_retard)
        $echeances = EpargneEcheance::whereIn('statut', ['a_venir', 'en_retard'])
            ->where('date_echeance', '<=', now()->toDateString())
            ->with(['souscription.plan', 'souscription.membre'])
            ->get();

        $count = 0;

        foreach ($echeances as $echeance) {
            $plan = $echeance->souscription->plan;
            $membre = $echeance->souscription->membre;

            if (!$plan || !$membre) continue;

            // 2. Calculer la deadline précise (Date Échéance + Heure Limite)
            $deadline = Carbon::parse($echeance->date_echeance->format('Y-m-d') . ' ' . $plan->heure_limite_paiement);
            
            // 3. Vérifier si on est dans la fenêtre de rappel
            // window = [deadline - delai_rappel_heures, deadline]
            // Note: On continue aussi après la deadline si c'est toujours impayé.
            $debutRappel = (clone $deadline)->subHours($plan->delai_rappel_heures);
            
            if (now()->lt($debutRappel)) {
                // Trop tôt pour envoyer le rappel
                continue;
            }

            // 4. Vérifier l'intervalle depuis le dernier rappel
            if ($echeance->dernier_rappel_at) {
                $minutesDepuisDernierRappel = $echeance->dernier_rappel_at->diffInMinutes(now());
                if ($minutesDepuisDernierRappel < $plan->intervalle_rappel_minutes) {
                    // L'intervalle de répétition n'est pas encore atteint
                    continue;
                }
            }

            // 5. Envoyer les notifications
            $this->sendNotification($echeance, $pushService, $emailService);
            
            // 6. Mettre à jour le timestamp du dernier rappel
            $echeance->update(['dernier_rappel_at' => now()]);
            $count++;
        }

        $this->info("Terminé. {$count} rappels envoyés.");
    }

    /**
     * Envoie la notification Push et Email.
     */
    protected function sendNotification(EpargneEcheance $echeance, $pushService, $emailService)
    {
        $membre = $echeance->souscription->membre;
        $plan = $echeance->souscription->plan;
        
        $montant = number_format($echeance->montant, 0, ',', ' ') . ' XOF';
        $deadlineStr = $echeance->date_echeance->format('d/m/Y') . ' à ' . substr($plan->heure_limite_paiement, 0, 5);
        
        $title = "Rappel de paiement : Tontine {$plan->nom}";
        $body = "Cher membre, n'oubliez pas votre versement de {$montant} pour votre tontine {$plan->nom}. Date limite : {$deadlineStr}.";

        // 1. Notification Push (via FCM)
        try {
            if ($membre->fcm_token) {
                $pushService->sendPush($membre, $title, $body);
            }
        } catch (\Exception $e) {
            Log::error("Erreur Push Rappel Tontine (Membre {$membre->id}): " . $e->getMessage());
        }

        // 2. Notification Email (Optionnel mais recommandé)
        if ($membre->email) {
            try {
                $emailService->configureSMTP();
                Mail::raw($body, function ($message) use ($membre, $title) {
                    $message->to($membre->email)->subject($title);
                });
            } catch (\Exception $e) {
                Log::error("Erreur Email Rappel Tontine (Membre {$membre->id}): " . $e->getMessage());
            }
        }

        // 3. Log de la notification
        NotificationLog::createLog(
            NotificationLog::TYPE_PAYMENT_REMINDER,
            'App\Models\Membre',
            $membre->id,
            $membre->email,
            $title,
            $body
        )->markAsSent();
    }
}
