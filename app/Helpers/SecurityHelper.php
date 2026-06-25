<?php

namespace App\Helpers;

/**
 * Helper de sécurité pour le traitement des entrées utilisateur
 */
class SecurityHelper
{
    /**
     * Échapper les caractères génériques SQL LIKE (% et _) dans une chaîne de recherche.
     *
     * Empêche l'injection de wildcards LIKE qui pourrait permettre :
     * - % seul → retourner toutes les lignes (DoS / fuite d'informations)
     * - _ → matcher des caractères individuels
     *
     * @param string $search La chaîne de recherche brute
     * @return string La chaîne échappée, prête pour LIKE "%...%"
     */
    public static function escapeLikeWildcards(string $search): string
    {
        return str_replace(
            ['%', '_', '\\'],
            ['\\%', '\\_', '\\\\'],
            $search
        );
    }

    /**
     * Échapper les wildcards LIKE et retourner le format pour une recherche LIKE.
     *
     * Usage : $query->where('nom', 'like', SecurityHelper::likeSearch($search));
     *
     * @param string $search La chaîne de recherche brute
     * @param string $position Le type de recherche : 'both', 'start', 'end'
     * @return string Le motif LIKE échappé
     */
    public static function likeSearch(string $search, string $position = 'both'): string
    {
        $escaped = self::escapeLikeWildcards($search);

        return match ($position) {
            'start' => "%{$escaped}",
            'end'   => "{$escaped}%",
            default => "%{$escaped}%",
        };
    }
}
