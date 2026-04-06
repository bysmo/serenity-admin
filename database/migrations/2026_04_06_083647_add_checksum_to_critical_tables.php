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
        $tables = [
            'paiements',
            'nano_credits',
            'nano_credit_garants',
            'nano_credit_versements',
            'nano_credit_echeances',
            'epargne_souscriptions',
            'epargne_versements',
            'epargne_echeances',
            'remboursements',
            'membres',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('checksum', 64)->nullable()->after('updated_at');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'paiements',
            'nano_credits',
            'nano_credit_garants',
            'nano_credit_versements',
            'nano_credit_echeances',
            'epargne_souscriptions',
            'epargne_versements',
            'epargne_echeances',
            'remboursements',
            'membres',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('checksum');
                });
            }
        }
    }
};
