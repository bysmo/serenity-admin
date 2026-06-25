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
        Schema::table('nano_credits', function (Blueprint $table) {
            $table->foreignId('beneficiaire_effectif_id')->nullable()->constrained('membres')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nano_credits', function (Blueprint $table) {
            $table->dropForeign(['beneficiaire_effectif_id']);
            $table->dropColumn('beneficiaire_effectif_id');
        });
    }
};
