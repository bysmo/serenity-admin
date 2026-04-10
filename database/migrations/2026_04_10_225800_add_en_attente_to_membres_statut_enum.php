<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Utiliser DB::statement car enum change n'est pas supporté nativement de façon propre sous certains drivers
        DB::statement("ALTER TABLE membres MODIFY COLUMN statut ENUM('actif', 'inactif', 'suspendu', 'en_attente') DEFAULT 'en_attente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE membres MODIFY COLUMN statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif'");
    }
};
