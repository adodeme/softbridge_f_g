<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            // Le lien avec la table users (un compte utilisateur = un profil client)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Les colonnes du profil client
            $table->string('nom_entreprise');
            $table->string('numero_client')->unique(); // Exemple : CLI-001
            $table->text('adresse')->nullable();
            $table->date('date_inscription')->nullable(); // Peut être optionnel, on peut aussi utiliser le created_at de timestamps()
            
            $table->timestamps(); // Crée les colonnes created_at et updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
