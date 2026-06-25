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
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            Schema::table('epargne_plans', function (Blueprint $table) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE epargne_plans MODIFY frequence ENUM('journalier', 'hebdomadaire', 'mensuel', 'trimestriel') DEFAULT 'mensuel'");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            Schema::table('epargne_plans', function (Blueprint $table) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE epargne_plans MODIFY frequence ENUM('hebdomadaire', 'mensuel', 'trimestriel') DEFAULT 'mensuel'");
            });
        }
    }
};
