<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membre_comptes_externes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('membre_id');
            $table->foreign('membre_id')->references('id')->on('membres')->onDelete('cascade');

            $table->string('nom', 100)->comment('Libellé du compte ex: Mon Orange Money');
            $table->text('description')->nullable();
            $table->string('pays', 5)->nullable()->comment('Code ISO pays ex: BF, CI, SN');

            // Type d'identifiant externe
            $table->enum('type_identifiant', ['alias', 'telephone', 'iban'])
                ->default('alias')
                ->comment('Nature de l\'identifiant : alias UUID, numéro de téléphone ou IBAN');

            $table->string('identifiant', 255)->comment('La valeur : UUID alias, tel E.164, ou IBAN');

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['membre_id', 'type_identifiant']);
        });

        // Migrer les alias existants vers les comptes externes
        if (Schema::hasTable('membre_wallet_aliases')) {
            \DB::table('membre_wallet_aliases')->orderBy('id')->each(function ($row) {
                \DB::table('membre_comptes_externes')->insert([
                    'membre_id'        => $row->membre_id,
                    'nom'              => $row->label ?? 'Portefeuille Pi-SPI',
                    'description'      => 'Migré depuis les alias Pi-SPI',
                    'pays'             => null,
                    'type_identifiant' => 'alias',
                    'identifiant'      => $row->alias,
                    'is_default'       => $row->is_default,
                    'created_at'       => $row->created_at,
                    'updated_at'       => $row->updated_at,
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('membre_comptes_externes');
    }
};
