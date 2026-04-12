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
        Schema::table('membres', function (Blueprint $table) {
            $table->enum('sexe', ['M', 'F'])->nullable()->after('prenom');
            $table->string('pays')->nullable()->after('adresse');
            $table->string('ville')->nullable()->after('pays');
            $table->string('quartier')->nullable()->after('ville');
            $table->string('secteur')->nullable()->after('quartier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            $table->dropColumn(['sexe', 'pays', 'ville', 'quartier', 'secteur']);
        });
    }
};
