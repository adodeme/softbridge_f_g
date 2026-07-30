<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            // Passer la colonne status en string avec une valeur par défaut 'inactive'
            $table->string('status')->default('inactive')->change();
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            // Revenir à l'ancien type (par exemple ENUM) si nécessaire
            // À ajuster selon votre migration originale
            $table->enum('status', ['active', 'expiree', 'suspendue', 'revoquee'])->default('active')->change();
        });
    }
};