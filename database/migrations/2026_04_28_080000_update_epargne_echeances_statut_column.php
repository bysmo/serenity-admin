<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On modifie la colonne statut en varchar car l'application utilise maintenant 
        // de nouvelles valeurs (en_attente, en_cours) non prévues dans l'enum initial.
        Schema::table('epargne_echeances', function (Blueprint $table) {
            $table->string('statut', 30)->default('en_attente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En cas de rollback, on essaie de revenir à l'enum si possible,
        // mais attention aux données existantes.
        DB::statement("ALTER TABLE epargne_echeances MODIFY statut ENUM('a_venir', 'payee', 'en_retard', 'annulee') DEFAULT 'a_venir'");
    }
};
