<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nano_credit_garants', function (Blueprint $table) {
            $table->decimal('gain_partage', 15, 0)->default(0)->comment('Montant gagné par le garant sur ce crédit');
        });
    }

    public function down(): void
    {
        Schema::table('nano_credit_garants', function (Blueprint $table) {
            $table->dropColumn('gain_partage');
        });
    }
};
