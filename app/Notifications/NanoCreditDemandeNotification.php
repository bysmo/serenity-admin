<?php

namespace App\Notifications;

use App\Models\NanoCredit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NanoCreditDemandeNotification extends Notification
{
    use Queueable;

    public function __construct(public NanoCredit $nanoCredit)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $membre = $this->nanoCredit->membre;
        $palier = $this->nanoCredit->palier;

        return [
            'type' => 'nano_credit_demande',
            'title' => 'Nouvelle demande de nano crédit',
            'message' => $membre ? "{$membre->prenom} {$membre->nom} a demandé un nano crédit de " . number_format($this->nanoCredit->montant, 0, ',', ' ') . " XOF" . ($palier ? " ({$palier->nom})" : '') . "." : "Nouvelle demande de nano crédit.",
            'nano_credit_id' => $this->nanoCredit->id,
            'membre_id' => $this->nanoCredit->membre_id,
            'montant' => $this->nanoCredit->montant,
            'url' => route('nano-credits.show', $this->nanoCredit),
        ];
    }
}
