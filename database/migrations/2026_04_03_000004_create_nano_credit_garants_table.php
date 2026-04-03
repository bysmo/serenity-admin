<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nano_credit_garants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nano_credit_id')
                ->constrained('nano_credits')
                ->onDelete('cascade');

            $table->foreignId('membre_id')
                ->constrained('membres')
                ->onDelete('cascade')
                ->comment('Membre garant');

            $table->enum('statut', [
                'en_attente',   // Garant sollicité, n'a pas encore répondu
                'accepte',      // Garant a accepté
                'refuse',       // Garant a refusé
                'preleve',      // Garant a été prélevé (solidarité activée)
                'libere',       // Garant libéré (crédit remboursé)
            ])->default('en_attente');

            $table->decimal('montant_preleve', 15, 0)->default(0)
                ->comment('Montant prélevé au garant en cas de défaillance');

            $table->timestamp('preleve_le')->nullable()
                ->comment('Date du prélèvement effectif');

            $table->timestamp('accepte_le')->nullable();
            $table->timestamp('refuse_le')->nullable();
            $table->text('motif_refus')->nullable();

            $table->timestamps();

            // Un membre ne peut être garant qu'une fois par crédit
            $table->unique(['nano_credit_id', 'membre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nano_credit_garants');
    }
};
