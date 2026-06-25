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
        if (!Schema::hasColumn('membres', 'date_naissance')) {
            Schema::table('membres', function (Blueprint $table) {
                $table->date('date_naissance')->nullable()->after('prenom');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('membres', 'date_naissance')) {
            Schema::table('membres', function (Blueprint $table) {
                $table->dropColumn('date_naissance');
            });
        }
    }
};
