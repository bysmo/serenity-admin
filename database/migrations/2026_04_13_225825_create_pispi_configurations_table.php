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
        Schema::create('pispi_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('api_key')->nullable();
            $table->string('paye_alias')->nullable(); // ID Marchand / Business Alias
            $table->enum('mode', ['sandbox', 'live'])->default('sandbox');
            $table->boolean('enabled')->default(false);
            $table->string('webhook_secret')->nullable();
            $table->string('token_cache_key')->default('pispi_access_token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pispi_configurations');
    }
};
