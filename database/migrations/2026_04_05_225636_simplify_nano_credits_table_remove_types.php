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
        // 1. S'assurer que tous les membres avec KYC validé ont le Palier 1
        $palier1 = \DB::table('nano_credit_paliers')->where('numero', 1)->first();
        if ($palier1) {
            \DB::table('membres')
                ->whereNull('nano_credit_palier_id')
                ->whereExists(function ($query) {
                    $query->select(\DB::raw(1))
                        ->from('kyc_verifications')
                        ->whereColumn('kyc_verifications.membre_id', 'membres.id')
                        ->where('statut', 'valide');
                })
                ->update(['nano_credit_palier_id' => $palier1->id]);

            // 2. Assigner le Palier 1 aux anciens crédits si palier_id est null
            \DB::table('nano_credits')
                ->whereNull('palier_id')
                ->update(['palier_id' => $palier1->id]);
        }

        // 3. Supprimer la colonne nano_credit_type_id
        if (Schema::hasColumn('nano_credits', 'nano_credit_type_id')) {
            Schema::table('nano_credits', function (Blueprint $table) {
                try {
                    $table->dropForeign(['nano_credit_type_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('nano_credit_type_id');
            });
        }

        // 4. Modifier palier_id (facultatif si erreur persistante)
        /*
        if (Schema::hasColumn('nano_credits', 'palier_id')) {
            Schema::table('nano_credits', function (Blueprint $table) {
                $table->unsignedBigInteger('palier_id')->nullable(false)->change();
            });
        }
        */

        // 5. Supprimer la table
        Schema::dropIfExists('nano_credit_types');
    }

    public function down(): void
    {
        // Pas simple de revenir en arrière sans les données supprimées
        Schema::create('nano_credit_types', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('montant_min', 15, 2);
            $table->decimal('montant_max', 15, 2);
            $table->integer('duree_jours');
            $table->decimal('taux_interet', 5, 2);
            $table->string('frequence_remboursement')->default('mensuel');
            $table->boolean('actif')->default(true);
            $table->integer('ordre')->default(0);
            $table->decimal('min_epargne_percent', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('nano_credits', function (Blueprint $table) {
            $table->foreignId('nano_credit_type_id')->nullable()->constrained('nano_credit_types');
        });
    }
};
