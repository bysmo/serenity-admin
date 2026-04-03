<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nano_credits', function (Blueprint $table) {
            $table->foreignId('palier_id')
                ->nullable()
                ->after('nano_credit_type_id')
                ->constrained('nano_credit_paliers')
                ->nullOnDelete()
                ->comment('Palier auquel le crédit a été accordé');

            $table->decimal('montant_penalite', 15, 0)->default(0)
                ->after('error_message')
                ->comment('Pénalités de retard accumulées');

            $table->unsignedSmallInteger('jours_retard')->default(0)
                ->after('montant_penalite')
                ->comment('Nombre de jours de retard cumulé');

            $table->date('date_dernier_calcul_penalite')->nullable()
                ->after('jours_retard')
                ->comment('Date du dernier calcul de pénalité');
        });
    }

    public function down(): void
    {
        Schema::table('nano_credits', function (Blueprint $table) {
            $table->dropForeign(['palier_id']);
            $table->dropColumn([
                'palier_id',
                'montant_penalite',
                'jours_retard',
                'date_dernier_calcul_penalite',
            ]);
        });
    }
};
