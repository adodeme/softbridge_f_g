<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Administrateur de test (désactivé pour éviter les conflits) ---
        // User::updateOrCreate(
        //     ['email' => 'admin@softbridge.com'],
        //     [
        //         'nom' => 'Super',
        //         'prenom' => 'Admin',
        //         'password' => Hash::make('password'),
        //         'role' => 'administrateur'
        //     ]
        // );

        // 2. Chef de Projet
        User::updateOrCreate(
            ['email' => 'chef@softbridge.com'],
            [
                'nom' => 'Jean',
                'prenom' => 'Dupont',
                'password' => Hash::make('password'),
                'role' => 'chef_projet'
            ]
        );

        // 3. Client
        $userClient = User::updateOrCreate(
            ['email' => 'client@softbridge.com'],
            [
                'nom' => 'Alice',
                'prenom' => 'Martin',
                'password' => Hash::make('password'),
                'role' => 'client'
            ]
        );

        Client::updateOrCreate(
            ['user_id' => $userClient->id],
            [
                'nom_entreprise' => 'Entreprise ABC',
                'numero_client' => 'CLI-001',
                'adresse' => '123 Rue de la Tech',
                'date_inscription' => now()
            ]
        );

        // --- Administrateur principal (depuis les variables d'environnement) ---
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if ($adminEmail && $adminPassword) {
            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'nom' => 'Admin',
                    'prenom' => 'Principal',
                    'password' => Hash::make($adminPassword),
                    'role' => 'administrateur'
                ]
            );
            $this->command->info('✅ Administrateur principal créé ou mis à jour.');
        }

        $this->command->info('✅ Utilisateurs de test créés avec succès !');
    }
}