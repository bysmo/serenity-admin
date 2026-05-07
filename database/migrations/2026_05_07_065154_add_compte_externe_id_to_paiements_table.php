<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->unsignedBigInteger('compte_externe_id')
                ->nullable()
                ->after('wallet_alias_id')
                ->comment('Référence vers le compte externe utilisé pour le paiement');

            $table->foreign('compte_externe_id')
                ->references('id')
                ->on('membre_comptes_externes')
                ->nullOnDelete();
        });

        // Rétrocompatibilité : relier les paiements existants au compte externe migré
        if (Schema::hasTable('membre_comptes_externes')) {
            \DB::table('paiements')
                ->whereNotNull('wallet_alias_id')
                ->whereNull('compte_externe_id')
                ->orderBy('id')
                ->each(function ($paiement) {
                    // Trouver l'alias source
                    $alias = \DB::table('membre_wallet_aliases')
                        ->where('id', $paiement->wallet_alias_id)
                        ->first();
                    if (!$alias) return;

                    // Trouver le compte externe correspondant à cet identifiant
                    $ce = \DB::table('membre_comptes_externes')
                        ->where('membre_id', $alias->membre_id)
                        ->where('identifiant', $alias->alias)
                        ->first();

                    if ($ce) {
                        \DB::table('paiements')
                            ->where('id', $paiement->id)
                            ->update(['compte_externe_id' => $ce->id]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropForeign(['compte_externe_id']);
            $table->dropColumn('compte_externe_id');
        });
    }
};
