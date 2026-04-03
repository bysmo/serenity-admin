<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nano_credit_paliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero')->unique()->comment('Numéro du palier (1, 2, 3…)');
            $table->string('nom');
            $table->text('description')->nullable();

            // --- Conditions d'accession (pour passer À ce palier depuis le palier inférieur) ---
            $table->unsignedInteger('min_credits_rembourses')->default(0)
                ->comment('Nombre minimum de crédits entièrement remboursés requis');
            $table->decimal('min_montant_total_rembourse', 15, 0)->default(0)
                ->comment('Montant total minimum remboursé (tous crédits confondus)');
            $table->decimal('min_epargne_cumulee', 15, 0)->default(0)
                ->comment('Montant minimum d\'épargne/tontine cumulée');

            // --- Paramètres du crédit accordé à ce palier ---
            $table->decimal('montant_plafond', 15, 0)
                ->comment('Montant maximum empruntable à ce palier');
            $table->unsignedTinyInteger('nombre_garants')->default(0)
                ->comment('Nombre de garants requis pour une demande');
            $table->unsignedSmallInteger('duree_jours')
                ->comment('Durée maximale du crédit en jours');
            $table->decimal('taux_interet', 5, 2)->default(0)
                ->comment('Taux d\'intérêt annuel en %');
            $table->enum('frequence_remboursement', ['journalier', 'hebdomadaire', 'mensuel', 'trimestriel'])
                ->default('mensuel');
            $table->decimal('penalite_par_jour', 5, 2)->default(5.00)
                ->comment('Pénalité en % du capital restant dû par jour de retard');
            $table->unsignedSmallInteger('jours_avant_prelevement_garant')->default(30)
                ->comment('Nombre de jours d\'impayés avant prélèvement automatique des garants');

            // --- Conséquences impayés ---
            $table->boolean('downgrade_en_cas_impayes')->default(true)
                ->comment('Rétrograder automatiquement en cas d\'impayés');
            $table->unsignedSmallInteger('jours_impayes_pour_downgrade')->default(15)
                ->comment('Nombre de jours de retard déclenchant le downgrade');
            $table->boolean('interdiction_en_cas_recidive')->default(false)
                ->comment('Interdire le membre de prendre des crédits en cas de récidive');
            $table->unsignedTinyInteger('nb_recidives_pour_interdiction')->default(3)
                ->comment('Nombre de défauts de paiement avant interdiction');

            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nano_credit_paliers');
    }
};
