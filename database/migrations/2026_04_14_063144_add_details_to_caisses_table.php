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
        Schema::table('caisses', function (Blueprint $table) {
            if (!Schema::hasColumn('caisses', 'membre_id')) {
                $table->foreignId('membre_id')->nullable()->after('id')->constrained('membres')->onDelete('cascade');
            }
            if (!Schema::hasColumn('caisses', 'type')) {
                $table->string('type')->default('courant')->after('description');
            }
            if (!Schema::hasColumn('caisses', 'numero_core_banking')) {
                $table->string('numero_core_banking')->unique()->nullable()->after('type');
            }
        });

        // 1. Créer le client "SYSTEME" si nécessaire
        $systemClient = \App\Models\Membre::where('numero', 'SYSTEM')->first();
        if (!$systemClient) {
            $systemClient = \App\Models\Membre::create([
                'numero' => 'SYSTEM',
                'nom' => 'SYSTEME',
                'prenom' => 'SERENITY',
                'email' => 'system@serenity-admin.com',
                'telephone' => '+22600000000',
                'statut' => 'actif',
                'password' => \Illuminate\Support\Facades\Hash::make(bin2hex(random_bytes(16))),
                'date_adhesion' => now(),
            ]);
        }

        // 2. Lier les caisses existantes au client système
        \DB::table('caisses')->whereNull('membre_id')->update(['membre_id' => $systemClient->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            $table->dropForeign(['membre_id']);
            $table->dropColumn(['membre_id', 'type', 'numero_core_banking']);
        });
    }
};
