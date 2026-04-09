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
        Schema::table('cotisations', function (Blueprint $table) {
            if (!Schema::hasColumn('cotisations', 'type_montant')) {
                $table->enum('type_montant', ['libre', 'fixe'])->default('fixe')->after('frequence');
            }
            // Rendre le montant nullable si ce n'est pas déjà le cas
            if (Schema::hasColumn('cotisations', 'montant')) {
                $table->decimal('montant', 15, 0)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotisations', function (Blueprint $table) {
            if (Schema::hasColumn('cotisations', 'type_montant')) {
                $table->dropColumn('type_montant');
            }
            // Remettre le montant en required
            if (Schema::hasColumn('cotisations', 'montant')) {
                $table->decimal('montant', 15, 0)->nullable(true)->change();
            }
        });
    }
};
