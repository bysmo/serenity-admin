<?php

namespace App\Services;

use App\Models\Membre;
use App\Models\NanoCreditGarant;
use App\Models\NanoCredit;
use Illuminate\Support\Facades\Log;

class AiRiskEvaluationService
{
    /**
     * Evalue le risque d'un membre (Note IA de 0 à 3).
     * Plus la note est basse, meilleur est le risque.
     * 0 = Très bon risque (Déblocage auto possible)
     * 3 = Risque maximal (Nécessite analyse humaine)
     * 
     * Cette logique simule un agent IA avancé effectuant des requêtes OSI.
     */
    public function evaluateRisk(Membre $membre): int
    {
        $score = 0;

        // 1. Analyse OSINT simulée (Réseaux sociaux / Email fictif)
        if (!$this->isEmailValidAndReal($membre->email)) {
            Log::info("AiRiskEvaluationService: Empreinte numérique / Email jugée suspecte pour le membre {$membre->id}");
            $score += 1;
        }

        // 2. Incident de paiements internes (Demandes de Garants)
        // Vérifie combien de fois ses garants ont rejeté sa demande de couverture de crédit
        $garantsRefus = NanoCreditGarant::whereHas('nanoCredit', function ($query) use ($membre) {
            $query->where('membre_id', $membre->id);
        })->where('statut', 'refuse')->count();

        if ($garantsRefus >= 2) {
            $score += 1;
        }

        // 3. Comportement de remboursement (Impayés ou retards significatifs dans le réseau / Tontines)
        if ($membre->hasImpayes()) {
            $score += 2; // L'IA détecte des incidents graves actifs.
        } elseif ($membre->maxJoursRetard() >= 10) {
            // Comportement lourdement en retard par le passé
            $score += 1;
        }

        // On borne le score de l'IA entre 0 et 3 max.
        return min(3, max(0, $score));
    }

    /**
     * Simule la vérification anti-fraude d'un email (scan internet)
     */
    private function isEmailValidAndReal(?string $email): bool
    {
        if (empty($email)) return false;

        $domainesSuspects = ['yopmail.com', 'tempmail.com', '10minutemail.com', 'mailinator.com', 'fake.com', 'test.com', 'example.com'];
        $parts = explode('@', $email);
        if (count($parts) !== 2) return false;

        $domain = strtolower($parts[1]);
        if (in_array($domain, $domainesSuspects)) {
            return false;
        }

        // On pourrait ajouter d'autres heuristiques (longueur, caractères aléatoires, absense de nom dans le prenom...)
        $username = strtolower($parts[0]);
        if (preg_match('/[0-9]{4,}/', $username)) {
            // username avec beaucoup de chiffres, suspect
            // Ce n'est qu'une pénalité mineure, l'algorithme complet d'IA le gérerait mieux,
            // mais on garde ici notre simulation stricte.
        }

        return true;
    }
}
