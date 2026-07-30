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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('numero')->unique(); // Exemple: FAC-2026-001
            $table->date('date_creation');
            $table->decimal('montant', 10, 2);
            $table->enum('statut', ['impaye', 'paye'])->default('impaye');
            $table->string('cle_acces')->nullable(); // La clé générée après paiement pour le client
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
