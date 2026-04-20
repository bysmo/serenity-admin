<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HandlesFriendlyErrors
{
    /**
     * Transforme une exception ou un message technique en message convivial pour le MEMBRE.
     * 
     * @param \Throwable|string $error
     * @param string|null $default Message par défaut
     * @return string
     */
    public function getFriendlyErrorMessage($error, ?string $default = null): string
    {
        $message = $error instanceof \Throwable ? $error->getMessage() : (string) $error;
        $default = $default ?? 'Une erreur est survenue lors de la communication avec le service de paiement. Veuillez réessayer.';

        // Mappings de messages techniques vers "Humain"
        $mappings = [
            'Could not resolve host'      => 'Problème de connexion à l\'un de nos services de paiement. Veuillez vérifier votre connexion internet ou réessayer plus tard.',
            'cURL error 6'                => 'Impossible de joindre le partenaire de paiement. Assurez-vous d\'être connecté à internet.',
            'Operation timed out'         => 'Le service de paiement met trop de temps à répondre. Votre opération est peut-être en cours, veuillez vérifier votre historique dans quelques instants.',
            'cURL error 28'               => 'Délai d\'attente dépassé avec le service de paiement.',
            'SSL certificate problem'     => 'Problème de sécurité lors de la connexion au partenaire de paiement. Veuillez contacter l\'assistance Serenity.',
            'cURL error 60'               => 'Connexion sécurisée impossible avec le partenaire de paiement.',
            'Unauthorized'                => 'Le service de paiement est momentanément indisponible (erreur d\'autorisation).',
            'Forbidden'                   => 'L\'accès au service de paiement a été refusé par le partenaire.',
            'Connection refused'          => 'Le serveur de paiement refuse la connexion. Veuillez réessayer plus tard.',
            'Server Error'                => 'Le service de paiement rencontre un problème technique interne.',
            'Internal Server Error'       => 'Erreur technique chez notre partenaire de paiement.',
        ];

        foreach ($mappings as $technical => $friendly) {
            if (Str::contains($message, $technical, true)) {
                return $friendly;
            }
        }

        // Si le message contient déjà du français "propre", on le garde (ex: erreur métier déjà traduite)
        if ($this->isAlreadyFriendly($message)) {
            return $message;
        }

        return $default;
    }

    /**
     * Vérifie si le message semble déjà être un message utilisateur (pas de jargon technique détecté).
     */
    private function isAlreadyFriendly(string $message): bool
    {
        $technicalJargon = ['cURL', 'resolve host', 'timed out', 'stack trace', 'exception', 'undefined', 'null', 'http', 'https', 'api', 'json'];
        
        foreach ($technicalJargon as $jargon) {
            if (Str::contains($message, $jargon, true)) {
                return false;
            }
        }
        
        return true;
    }
}
