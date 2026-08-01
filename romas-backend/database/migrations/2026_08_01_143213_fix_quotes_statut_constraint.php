<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer la contrainte CHECK nommée "quotes_statut_check"
        DB::statement('ALTER TABLE quotes DROP CONSTRAINT IF EXISTS quotes_statut_check');

        // Changer la colonne en VARCHAR (si ce n'est pas déjà fait)
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('statut')->default('en_attente')->change();
        });
    }

    public function down(): void
    {
        // Recréer une contrainte CHECK similaire (optionnel)
        DB::statement("ALTER TABLE quotes ADD CONSTRAINT quotes_statut_check CHECK (statut IN ('brouillon', 'envoye', 'valide', 'refuse'))");
    }
};