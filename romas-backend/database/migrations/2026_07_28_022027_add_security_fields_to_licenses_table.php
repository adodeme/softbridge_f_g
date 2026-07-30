<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            // Ajout de la clé de licence cryptée (unique)
            $table->string('key')->unique()->nullable();
            // Statut de la licence (active, expiree, suspendue, revoquee)
            $table->string('status')->default('active');
            // Dernier accès pour la sécurité
            $table->timestamp('last_accessed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['key', 'status', 'last_accessed_at']);
        });
    }
};