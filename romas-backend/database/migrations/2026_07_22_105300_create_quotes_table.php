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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            // On lie le devis à un client, et à un projet (qui sera créé à la validation)
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->text('besoins')->nullable();
            $table->json('fonctionnalites')->nullable(); // Pour stocker la liste des fonctionnalités demandées
            $table->decimal('montant', 10, 2)->default(0);
            $table->enum('statut', ['brouillon', 'envoye', 'valide', 'refuse'])->default('brouillon');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
