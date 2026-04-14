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
        Schema::table('epargne_plans', function (Blueprint $table) {
            $table->time('heure_limite_paiement')->default('17:00:00')->after('caisse_id');
            $table->integer('delai_rappel_heures')->default(24)->after('heure_limite_paiement');
            $table->integer('intervalle_rappel_minutes')->default(60)->after('delai_rappel_heures');
        });

        Schema::table('epargne_echeances', function (Blueprint $table) {
            $table->timestamp('dernier_rappel_at')->nullable()->after('paye_le');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epargne_plans', function (Blueprint $table) {
            $table->dropColumn(['heure_limite_paiement', 'delai_rappel_heures', 'intervalle_rappel_minutes']);
        });

        Schema::table('epargne_echeances', function (Blueprint $table) {
            $table->dropColumn('dernier_rappel_at');
        });
    }
};
