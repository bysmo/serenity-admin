<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nano_credit_types', function (Blueprint $table) {
            $table->decimal('min_epargne_percent', 5, 2)->default(85.00)->comment('% d\'épargne minimum requis par rapport au montant du crédit');
        });
    }

    public function down(): void
    {
        Schema::table('nano_credit_types', function (Blueprint $table) {
            $table->dropColumn('min_epargne_percent');
        });
    }
};
