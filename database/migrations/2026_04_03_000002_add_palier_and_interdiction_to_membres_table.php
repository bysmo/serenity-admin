<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->foreignId('nano_credit_palier_id')
                ->nullable()
                ->after('id')
                ->constrained('nano_credit_paliers')
                ->nullOnDelete()
                ->comment('Palier nano-crédit actuel du membre');

            $table->boolean('nano_credit_interdit')->default(false)
                ->after('nano_credit_palier_id')
                ->comment('Membre interdit de prendre des nano-crédits');

            $table->text('motif_interdiction')->nullable()
                ->after('nano_credit_interdit')
                ->comment('Motif de l\'interdiction de crédit');

            $table->timestamp('interdit_le')->nullable()
                ->after('motif_interdiction');

            $table->unsignedTinyInteger('nb_defauts_paiement')->default(0)
                ->after('interdit_le')
                ->comment('Nombre de défauts de paiement cumulés');
        });
    }

    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->dropForeign(['nano_credit_palier_id']);
            $table->dropColumn([
                'nano_credit_palier_id',
                'nano_credit_interdit',
                'motif_interdiction',
                'interdit_le',
                'nb_defauts_paiement',
            ]);
        });
    }
};
