<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->unsignedInteger('garant_qualite')->default(0)->comment('Score de qualité du membre en tant que garant');
            $table->decimal('garant_solde', 15, 0)->default(0)->comment('Solde du compte garant pour les reversements de bénéfices');
        });
    }

    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->dropColumn(['garant_qualite', 'garant_solde']);
        });
    }
};
