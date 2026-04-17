<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrichissement de la table paiements pour supporter :
 *  - les paiements en ligne (PayDunya, Pi-SPI) avec statut et référence
 *  - des métadonnées JSON (engagement_id, type de paiement, etc.)
 *  - un commentaire libre
 *  - nullable cotisation_id / caisse_id (pour les paiements hors-cotisation)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            // Référence unique de la transaction (ex: PAY-xxxxx, P-PISPI-xxx)
            if (!Schema::hasColumn('paiements', 'reference')) {
                $table->string('reference')->nullable()->unique()->after('numero');
            }

            // Statut du paiement (en_attente, valide, echec, annule)
            if (!Schema::hasColumn('paiements', 'statut')) {
                $table->string('statut')->default('valide')->after('mode_paiement');
            }

            // Données supplémentaires (engagement_id, echeance_id, type...)
            if (!Schema::hasColumn('paiements', 'metadata')) {
                $table->json('metadata')->nullable()->after('statut');
            }

            // Commentaire libre (visible admin)
            if (!Schema::hasColumn('paiements', 'commentaire')) {
                $table->text('commentaire')->nullable()->after('notes');
            }

            // Rendre cotisation_id et caisse_id nullables pour les paiements hors-cotisation
            // (épargne libre, remboursement nano-crédit, etc.)
            // IMPORTANT : On modifie la colonne si elle existe et est NOT NULL
            // MySQL ne supporte pas directement DROP FOREIGN KEY + change facilement,
            // donc on procède de façon sécurisée.
        });

        // Rendre cotisation_id nullable séparément pour éviter les conflits de FK
        try {
            Schema::table('paiements', function (Blueprint $table) {
                $table->foreignId('cotisation_id')->nullable()->change();
            });
        } catch (\Exception $e) {
            \Log::warning('Migration paiements: impossible de rendre cotisation_id nullable: ' . $e->getMessage());
        }

        // Rendre caisse_id nullable
        try {
            Schema::table('paiements', function (Blueprint $table) {
                $table->foreignId('caisse_id')->nullable()->change();
            });
        } catch (\Exception $e) {
            \Log::warning('Migration paiements: impossible de rendre caisse_id nullable: ' . $e->getMessage());
        }

        // Étendre l'enum mode_paiement pour inclure paydunya et pispi
        try {
            \DB::statement("ALTER TABLE paiements MODIFY COLUMN mode_paiement ENUM('especes','cheque','virement','mobile_money','paydunya','pispi','autre') DEFAULT 'especes'");
        } catch (\Exception $e) {
            \Log::warning('Migration paiements: impossible d\'étendre l\'enum mode_paiement: ' . $e->getMessage());
        }

        // Ajouter checksum si absent
        if (!Schema::hasColumn('paiements', 'checksum')) {
            Schema::table('paiements', function (Blueprint $table) {
                $table->string('checksum')->nullable()->after('commentaire');
            });
        }
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $cols = ['reference', 'statut', 'metadata', 'commentaire'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('paiements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
