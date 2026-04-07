<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer les colonnes liées aux paiements individuels
        Schema::table('cotisations', function (Blueprint $table) {
            if (Schema::hasColumn('cotisations', 'membre_id')) {
                $table->dropForeign(['membre_id']);
                $table->dropColumn('membre_id');
            }
            if (Schema::hasColumn('cotisations', 'date_echeance')) {
                $table->dropColumn('date_echeance');
            }
            if (Schema::hasColumn('cotisations', 'date_paiement')) {
                $table->dropColumn('date_paiement');
            }
            if (Schema::hasColumn('cotisations', 'statut')) {
                $table->dropColumn('statut');
            }
        });

        // Ajouter les colonnes pour les templates de cotisation
        Schema::table('cotisations', function (Blueprint $table) {
            if (!Schema::hasColumn('cotisations', 'nom')) {
                $table->string('nom')->after('numero');
            }
            if (!Schema::hasColumn('cotisations', 'frequence')) {
                $table->enum('frequence', ['mensuelle', 'trimestrielle', 'hebdomadaire', 'semestrielle', 'annuelle', 'unique'])->after('type');
            }
            if (!Schema::hasColumn('cotisations', 'description')) {
                $table->text('description')->nullable()->after('montant');
            }
            if (!Schema::hasColumn('cotisations', 'actif')) {
                $table->boolean('actif')->default(true)->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotisations', function (Blueprint $table) {
            if (Schema::hasColumn('cotisations', 'nom')) {
                $table->dropColumn('nom');
            }
            if (Schema::hasColumn('cotisations', 'frequence')) {
                $table->dropColumn('frequence');
            }
            if (Schema::hasColumn('cotisations', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('cotisations', 'actif')) {
                $table->dropColumn('actif');
            }
        });

        Schema::table('cotisations', function (Blueprint $table) {
            $table->foreignId('membre_id')->nullable()->constrained('membres')->onDelete('cascade');
            $table->date('date_echeance')->nullable();
            $table->date('date_paiement')->nullable();
            $table->enum('statut', ['payee', 'en_attente', 'en_retard'])->default('en_attente');
        });
    }
};
