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
        Schema::create('audit_checksum_logs', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_valid')->default(true);
            $table->unsignedInteger('rows_checked_count')->default(0);
            $table->unsignedInteger('corrupted_count')->default(0);
            
            // Stocke un tableau JSON décrivant les lignes corrompues (table, id)
            $table->json('corrupted_data')->nullable();
            
            $table->timestamps();
            
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_checksum_logs');
    }
};
