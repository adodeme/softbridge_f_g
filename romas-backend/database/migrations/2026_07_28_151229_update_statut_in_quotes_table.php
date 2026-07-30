<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Remplacement de l'ENUM par un string avec une valeur par défaut
            $table->string('statut')->default('en_attente')->change();
        });
    }

    public function down(): void
    {
        // En cas de rollback, on restaure l'ENUM initial (attention aux données)
        Schema::table('quotes', function (Blueprint $table) {
            $table->enum('statut', ['brouillon', 'envoye', 'valide', 'refuse'])->default('brouillon')->change();
        });
    }
};