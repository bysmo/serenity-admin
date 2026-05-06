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
        Schema::table('collecte_sessions', function (Blueprint $table) {
            $table->longText('montant_ouverture')->nullable()->change();
            $table->longText('montant_fermeture')->nullable()->change();
        });

        Schema::table('collectes', function (Blueprint $table) {
            $table->longText('montant')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collecte_sessions', function (Blueprint $table) {
            $table->decimal('montant_ouverture', 15, 0)->default(0)->change();
            $table->decimal('montant_fermeture', 15, 0)->default(0)->change();
        });

        Schema::table('collectes', function (Blueprint $table) {
            $table->decimal('montant', 15, 0)->change();
        });
    }
};
