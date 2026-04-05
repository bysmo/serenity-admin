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
        Schema::create('garant_gain_retraits', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('membre_id')->constrained('membres')->onDelete('cascade');
            $table->text('montant'); // EncryptedDecimal
            $table->string('statut')->default('en_attente'); // en_attente, approuve, refuse
            $table->foreignId('traite_par')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('traite_le')->nullable();
            $table->text('commentaire_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garant_gain_retraits');
    }
};
