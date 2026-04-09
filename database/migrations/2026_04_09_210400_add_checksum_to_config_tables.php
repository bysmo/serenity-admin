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
            'cotisations',
            'epargne_plans',
            'payment_methods',
            'app_settings',
            'nano_credit_paliers',
            'parrainage_configs'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'checksum')) {
                        $table->string('checksum')->nullable();
                    }
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
            'cotisations',
            'epargne_plans',
            'payment_methods',
            'app_settings',
            'nano_credit_paliers',
            'parrainage_configs'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'checksum')) {
                        $table->dropColumn('checksum');
                    }
                });
            }
        }
    }
};
