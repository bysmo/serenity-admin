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
        Schema::table('nano_credit_paliers', function (Blueprint $table) {
             $table->unsignedInteger('min_epargne_percent')->default(85)->after('min_epargne_cumulee')->comment('Pourcentage minimum de l\'épargne par rapport au crédit pour être éligible garant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nano_credit_paliers', function (Blueprint $table) {
            $table->dropColumn('min_epargne_percent');
        });
    }
};
