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
            $table->unsignedTinyInteger('score_ai')->nullable()->after('statut');
            $table->unsignedTinyInteger('score_humain')->nullable()->after('score_ai');
            $table->unsignedTinyInteger('score_global')->nullable()->after('score_humain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nano_credits', function (Blueprint $table) {
            $table->dropColumn(['score_ai', 'score_humain', 'score_global']);
        });
    }
};
