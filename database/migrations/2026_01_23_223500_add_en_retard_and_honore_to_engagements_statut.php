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
        if (DB::getDriverName() === 'mysql') {
            // Modifier l'ENUM pour ajouter 'en_retard' et 'honore'
            DB::statement("ALTER TABLE `engagements` MODIFY COLUMN `statut` ENUM('en_cours', 'en_retard', 'honore', 'termine', 'annule') DEFAULT 'en_cours'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Remettre l'ENUM original
            DB::statement("ALTER TABLE `engagements` MODIFY COLUMN `statut` ENUM('en_cours', 'termine', 'annule') DEFAULT 'en_cours'");
        }
        
        // Convertir les statuts 'en_retard' et 'honore' en 'en_cours' avant de supprimer les valeurs
        DB::table('engagements')
            ->whereIn('statut', ['en_retard', 'honore'])
            ->update(['statut' => 'en_cours']);
    }
};
