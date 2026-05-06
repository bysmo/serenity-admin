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
        // Update caisses table to support collectors
        Schema::table('caisses', function (Blueprint $table) {
            if (!Schema::hasColumn('caisses', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('membre_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('caisses', 'alias')) {
                $table->string('alias')->nullable()->after('numero_core_banking');
            }
        });

        // Table for collection sessions (journées de collecte)
        Schema::create('collecte_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // The collector
            $table->date('date_session');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->enum('statut', ['ouvert', 'ferme'])->default('ouvert');
            $table->decimal('montant_ouverture', 15, 0)->default(0);
            $table->decimal('montant_fermeture', 15, 0)->default(0);
            $table->timestamps();
        });

        // Table for individual collections
        Schema::create('collectes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collecte_session_id')->constrained('collecte_sessions')->onDelete('cascade');
            $table->foreignId('membre_id')->constrained('membres')->onDelete('cascade');
            $table->string('type_collecte'); // 'tontine' or 'nano_credit'
            $table->unsignedBigInteger('echeance_id'); // ID of EpargneEcheance or NanoCreditEcheance
            $table->decimal('montant', 15, 0);
            $table->string('otp_code', 10)->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->string('reference_transaction')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collectes');
        Schema::dropIfExists('collecte_sessions');
        Schema::table('caisses', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'alias']);
        });
    }
};
