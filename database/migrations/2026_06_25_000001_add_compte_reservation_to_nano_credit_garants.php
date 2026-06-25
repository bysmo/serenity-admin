<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le compte de réservation (blocage de garantie) sur la table nano_credit_garants.
     * Lorsqu'un garant accepte une sollicitation, le montant de couverture est bloqué sur ce compte.
     */
    public function up(): void
    {
        Schema::table('nano_credit_garants', function (Blueprint $table) {
            $table->foreignId('compte_reservation_id')
                ->nullable()
                ->after('gain_partage')
                ->constrained('caisses')
                ->nullOnDelete()
                ->comment('Compte RESERVATIONS NANO-CREDIT où le montant de couverture est bloqué');

            $table->decimal('montant_reserve', 15, 0)->default(0)
                ->after('compte_reservation_id')
                ->comment('Montant bloqué sur le compte de réservation lors de l\'acceptation');

            $table->timestamp('reserve_le')->nullable()
                ->after('montant_reserve')
                ->comment('Date du blocage du montant de couverture');

            $table->timestamp('libere_le')->nullable()
                ->after('reserve_le')
                ->comment('Date de libération du montant de couverture (remboursement final)');
        });
    }

    public function down(): void
    {
        Schema::table('nano_credit_garants', function (Blueprint $table) {
            $table->dropForeign(['compte_reservation_id']);
            $table->dropColumn(['compte_reservation_id', 'montant_reserve', 'reserve_le', 'libere_le']);
        });
    }
};
