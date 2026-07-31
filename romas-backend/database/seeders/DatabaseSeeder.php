<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Software;
use App\Models\License;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Administrateur
        User::create([
            'nom' => 'Super',
            'prenom' => 'Admin',
            'email' => 'admin@softbridge.com',
            'password' => Hash::make('password'),
            'role' => 'administrateur'
        ]);

        // 2. Chef de Projet
        User::create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'email' => 'chef@softbridge.com',
            'password' => Hash::make('password'),
            'role' => 'chef_projet'
        ]);

        // 3. Client
        $userClient = User::create([
            'nom' => 'Alice',
            'prenom' => 'Martin',
            'email' => 'client@softbridge.com',
            'password' => Hash::make('password'),
            'role' => 'client'
        ]);

        Client::create([
            'user_id' => $userClient->id,
            'nom_entreprise' => 'Entreprise ABC',
            'numero_client' => 'CLI-001',
            'adresse' => '123 Rue de la Tech',
            'date_inscription' => now()
        ]);

        
        $this->command->info('✅ Utilisateurs et logiciels créés avec succès !');
    }
}