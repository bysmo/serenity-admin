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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Supprimer les anciennes clés étrangères si elles existent
            // Note: On utilise des try/catch ou on vérifie l'existence car l'état peut varier
            try {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['membre_id']);
            } catch (\Exception $e) {
                // Silencieusement ignorer si les clés n'existent pas
            }

            // Supprimer les colonnes
            $table->dropColumn(['user_id', 'membre_id']);

            // Ajouter les nouvelles colonnes polymorphiques
            $table->nullableMorphs('actor'); // Crée actor_id et actor_type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropMorphs('actor');
            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('membre_id')->nullable()->constrained('membres')->onDelete('set null');
        });
    }
};
