<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Réconciliation : soldes calculés vs soldes livre.
 * - balances_calculated : solde recalculé à partir des mouvements (solde_initial + sum(entrees) - sum(sorties))
 * - balances_book : solde théorique (celui stocké en caisse au moment du check)
 * Un job compare ; si écart > seuil → alerte + gel possible des comptes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_balances_calculated', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->onDelete('cascade');
            $table->decimal('solde_calcule', 20, 4);
            $table->timestamp('computed_at');
            $table->timestamps();
            $table->unique(['caisse_id', 'computed_at']);
        });

        Schema::create('audit_balances_book', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->onDelete('cascade');
            $table->decimal('solde_livre', 20, 4);
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->unique(['caisse_id', 'checked_at']);
        });

        Schema::create('audit_reconciliation_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->onDelete('cascade');
            $table->decimal('solde_calcule', 20, 4);
            $table->decimal('solde_livre', 20, 4);
            $table->decimal('ecart', 20, 4);
            $table->boolean('alerte_critique')->default(false);
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->index(['checked_at', 'alerte_critique']);
        });

        Schema::create('audit_alertes', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // reconciliation_ecart, merkle_integrity, etc.
            $table->foreignId('caisse_id')->nullable()->constrained('caisses')->onDelete('set null');
            $table->decimal('ecart', 20, 4)->nullable();
            $table->text('message');
            $table->boolean('comptes_geles')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('audit_merkle_roots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('merkle_root', 64);
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index('period_start');
        });

        // Autoriser le statut 'gelée' sur les caisses (réconciliation peut geler)
        if (Schema::hasTable('caisses') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE caisses MODIFY COLUMN statut ENUM('active','inactive','gelée') DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_merkle_roots');
        Schema::dropIfExists('audit_alertes');
        Schema::dropIfExists('audit_reconciliation_snapshots');
        Schema::dropIfExists('audit_balances_book');
        Schema::dropIfExists('audit_balances_calculated');
        if (Schema::hasTable('caisses') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE caisses MODIFY COLUMN statut ENUM('active','inactive') DEFAULT 'active'");
        }
    }
};
