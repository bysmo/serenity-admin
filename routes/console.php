<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planifier les vérifications de rappels et notifications
Schedule::command('app:check-overdue-payments')
    ->dailyAt('09:00')
    ->description('Vérifier les paiements en retard');

Schedule::command('app:check-low-balances')
    ->dailyAt('09:00')
    ->description('Vérifier les caisses avec solde faible');

Schedule::command('app:check-upcoming-engagements')
    ->dailyAt('09:00')
    ->description('Vérifier les engagements arrivant à échéance');

Schedule::command('app:check-upcoming-payments')
    ->dailyAt('09:00')
    ->description('Vérifier les paiements de cotisations à venir et envoyer des rappels');

// Audit financier : racine Merkle toutes les heures
Schedule::command('audit:merkle --period=1')
    ->hourly()
    ->description('Calcul racine Merkle journal audit');

// Rappels personnalisables pour les tontines (Épargne)
Schedule::command('tontine:send-reminders')
    ->everyMinute()
    ->description('Envoi des rappels configurables pour les tontines');

// Réconciliation soldes (calculé vs livre) toutes les 5 minutes
Schedule::command('audit:reconcile')
    ->everyFiveMinutes()
    ->description('Réconciliation soldes caisses');

// ─── Nano-Crédits : Paliers, Pénalités & Garants ─────────────────────────────

// Vérification et mise à jour automatique des paliers (upgrade/downgrade)
Schedule::command('nano-credits:check-paliers')
    ->dailyAt('06:00')
    ->description('Vérification et mise à jour des paliers nano-crédit');

// Calcul des pénalités journalières sur les crédits en retard
Schedule::command('nano-credits:appliquer-penalites')
    ->dailyAt('06:30')
    ->description('Calcul des pénalités de retard sur nano-crédits');

// Prélèvement automatique des garants (après n-jours d'impayés)
Schedule::command('nano-credits:prelever-garants')
    ->dailyAt('07:00')
    ->description('Prélèvement automatique des garants nano-crédit en défaut');

// Vérification automatique de l'intégrité de la base de données toutes les 10 minutes
Schedule::command('audit:checksums')
    ->everyTenMinutes()
    ->description('Vérifier l\'intégrité des checksums financiers');

// ─── Parrainage : Activation des commissions dont le délai est écoulé ────────
Schedule::command('parrainage:activer-commissions')
    ->hourly()
    ->description('Activer les commissions de parrainage dont le délai de validation est écoulé');
