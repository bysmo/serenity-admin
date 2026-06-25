<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PiSpiConfiguration;
use App\Models\Paiement;
use App\Models\EpargneEcheance;
use App\Models\Cotisation;
use App\Models\Engagement;
use App\Models\Membre;

class PiSpiWebhookController extends Controller
{
    /**
     * Gérer le callback (Webhook) de Pi-SPI
     */
    public function handle(Request $request)
    {
        Log::info('Pi-SPI Webhook Received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $config = PiSpiConfiguration::getActive();
        if (!$config) {
            return response()->json(['message' => 'Config not found'], 404);
        }

        // Vérification de la signature
        $signature = $request->header('X-Signature');
        if ($signature && !$this->verifySignature($request->getContent(), $signature, $config->webhook_secret)) {
            Log::warning('Pi-SPI Webhook Signature Invalid');
            if ($config->mode === 'live') {
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        // Extraction des données (gestion des variantes de structure)
        $data = $request->all();
        $txId = $data['txId'] ?? ($data['donnees']['txId'] ?? null);
        $statut = $data['statut'] ?? ($data['donnees']['statut'] ?? null);
        $evenement = $data['evenement'] ?? null;

        Log::info("Pi-SPI Webhook Processing: TX={$txId}, Status={$statut}, Event={$evenement}");

        if ($txId) {
            $upperStatus = strtoupper($statut);
            if (in_array($upperStatus, ['SUCCES', 'VALIDE', 'COMPLETED'])) {
                $this->processSuccessPayment($txId, $data);
            } elseif (in_array($upperStatus, ['EXPIRE', 'REJETE', 'ECHEC', 'CANCELLED'])) {
                $this->processFailedPayment($txId, $data);
            } else {
                Log::info("Pi-SPI Webhook: Transaction {$txId} skipped (Status: {$statut})");
            }
        }

        return response()->json(['message' => 'Webhook received and processed'], 200);
    }

    /**
     * Vérifier la signature HMAC-SHA256
     */
    private function verifySignature($payload, $signature, $secret)
    {
        if (!$signature || !$secret) return false;
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }

    /**
     * Traiter un paiement réussi
     */
    private function processSuccessPayment($txId, $data)
    {
        // On cherche le paiement par sa référence (recherche floue si tronqué)
        $paiement = Paiement::where(function($query) use ($txId) {
                $query->where('reference', $txId)
                      ->orWhere('reference', 'LIKE', $txId . '%');
            })
            ->where('mode_paiement', 'pispi')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($paiement) {
            if ($paiement->statut === 'valide') {
                Log::info("Pi-SPI Webhook: Payment {$txId} already validated.");
                return;
            }

            $paiement->update([
                'statut' => 'valide',
                'date_paiement' => now(),
                'commentaire' => $paiement->commentaire . "\n[Pi-SPI Webhook OK: " . now()->toDateTimeString() . "]"
            ]);

            Log::info("Pi-SPI Payment Validated: {$txId} for Paiement ID {$paiement->id}");
            
            // Mise à jour de l'adhésion si c'est une cotisation/cagnotte
            if ($paiement->cotisation_id && $paiement->membre_id) {
                $adhesion = \App\Models\CotisationAdhesion::where('membre_id', $paiement->membre_id)
                    ->where('cotisation_id', $paiement->cotisation_id)
                    ->first();
                
                if ($adhesion && $adhesion->statut !== 'accepte') {
                    $adhesion->update(['statut' => 'accepte']);
                    Log::info("Cagnotte entry validated for member #{$paiement->membre_id}");
                }

                // ─────────────────────────────────────────────────────────────
                // PAIEMENT CAGNOTTE via Pi-SPI (mobile money externe)
                // → n'impacte PAS le compte personnel du membre
                // → seule la caisse de la cagnotte + SYS-CAG-PUB/PRV bougent
                // ─────────────────────────────────────────────────────────────
                if ($paiement->caisse_id) {
                    $cotisation = \App\Models\Cotisation::find($paiement->cotisation_id);
                    if ($cotisation && $cotisation->caisse_id === $paiement->caisse_id) {
                        $isPrivee = ($cotisation->visibilite === 'privee');
                        app(\App\Services\FinanceService::class)->logFluxCagnotte(
                            $cotisation->caisse,
                            (float) $paiement->montant,
                            'Paiement cagnotte via Pi-SPI - Réf: ' . $txId,
                            $paiement,
                            $isPrivee
                        );
                        Log::info("Pi-SPI: Flux cagnotte enregistré (caisse #{$paiement->caisse_id})");
                    }
                }
            }

            // Gestion des échéances tontines (tontines planifiées = impact compte membre)
            if ($paiement->metadata && isset($paiement->metadata['echeance_id'])) {
                $echeance = EpargneEcheance::find($paiement->metadata['echeance_id']);
                if ($echeance) {
                    $echeance->update(['statut' => 'payee', 'paye_le' => now()]);
                    Log::info("Tontine Echeance #{$echeance->id} marked as PAID via Pi-SPI");

                    // Création du mouvement de caisse pour la tontine (compte épargne membre)
                    if ($paiement->caisse_id) {
                        \App\Models\MouvementCaisse::create([
                            'caisse_id'      => $paiement->caisse_id,
                            'type'           => 'epargne',
                            'sens'           => 'entree',
                            'montant'        => $paiement->montant,
                            'date_operation' => now(),
                            'libelle'        => 'Paiement tontine via Pi-SPI',
                            'notes'          => 'Validation Webhook - Réf: ' . $txId,
                            'reference_type' => Paiement::class,
                            'reference_id'   => $paiement->id,
                        ]);

                        // Réconciliation globale tontine
                        $caisseGlobal = \App\Models\Caisse::getCaisseTontineCli();
                        if ($caisseGlobal) {
                             \App\Models\MouvementCaisse::create([
                                'caisse_id'      => $caisseGlobal->id,
                                'type'           => 'epargne',
                                'sens'           => 'entree',
                                'montant'        => $paiement->montant,
                                'date_operation' => now(),
                                'libelle'        => 'RÉCONCILIATION TONTINE (Pi-SPI): Member #' . $paiement->membre_id,
                                'notes'          => 'Pi-SPI Webhook - Global - Réf: ' . $txId,
                                'reference_type' => Paiement::class,
                                'reference_id'   => $paiement->id,
                            ]);
                        }
                    }
                }
            }
        } else {
            Log::warning("Pi-SPI Webhook: No record found for txId {$txId}");
        }
    }


    /**
     * Traiter un paiement échoué
     */
    private function processFailedPayment($txId, $data)
    {
        $paiement = Paiement::where(function($query) use ($txId) {
                $query->where('reference', $txId)
                      ->orWhere('reference', 'LIKE', $txId . '%');
            })
            ->where('mode_paiement', 'pispi')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($paiement && $paiement->statut === 'en_attente') {
            $paiement->update([
                'statut' => 'echoue',
                'commentaire' => $paiement->commentaire . "\n[Pi-SPI Webhook FAILED: " . ($data['statut'] ?? 'ERR') . " le " . now()->toDateTimeString() . "]"
            ]);

            // Remise à zéro de l'échéance si tontine
            if ($paiement->metadata && isset($paiement->metadata['echeance_id'])) {
                $echeance = EpargneEcheance::find($paiement->metadata['echeance_id']);
                if ($echeance && $echeance->statut === 'en_cours') {
                    $echeance->update(['statut' => 'en_attente']);
                    Log::info("Tontine Echeance #{$echeance->id} reset to UNPAID due to Pi-SPI failure");
                }
            }
        }
    }
}
