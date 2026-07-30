<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('guest_nom')->nullable();
            $table->string('guest_prenom')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_telephone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['guest_nom', 'guest_prenom', 'guest_email', 'guest_telephone']);
        });
    }
};