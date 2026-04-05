<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nano_credit_paliers', function (Blueprint $table) {
            $table->unsignedInteger('min_garant_qualite')->default(0)->comment('Qualité minimale requise pour les garants à ce palier');
            $table->decimal('pourcentage_partage_garant', 5, 2)->default(0)->comment('Pourcentage des intérêts redistribués aux garants');
        });
    }

    public function down(): void
    {
        Schema::table('nano_credit_paliers', function (Blueprint $table) {
            $table->dropColumn(['min_garant_qualite', 'pourcentage_partage_garant']);
        });
    }
};
