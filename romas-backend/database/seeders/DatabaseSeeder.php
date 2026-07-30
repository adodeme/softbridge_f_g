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

        // 4. Logiciels (avec la table 'softwares')
        $sw1 = Software::create([
            'nom' => 'GESTION PRO',
            'description' => 'Logiciel de gestion complet pour PME.',
            'categorie' => 'Gestion',
            'capture' => null
        ]);
        License::create(['software_id' => $sw1->id, 'type' => 'trimestrielle', 'duree' => 90, 'prix' => 50000]);
        License::create(['software_id' => $sw1->id, 'type' => 'annuelle', 'duree' => 365, 'prix' => 160000]);
        License::create(['software_id' => $sw1->id, 'type' => 'mensuelle', 'duree' => 30, 'prix' => 20000]);

        $sw2 = Software::create([
            'nom' => 'RH MANAGER',
            'description' => 'Gestion des RH, paie et recrutement.',
            'categorie' => 'RH',
            'capture' => null
        ]);
        License::create(['software_id' => $sw2->id, 'type' => 'trimestrielle', 'duree' => 90, 'prix' => 40000]);
        License::create(['software_id' => $sw2->id, 'type' => 'annuelle', 'duree' => 365, 'prix' => 120000]);
        License::create(['software_id' => $sw2->id, 'type' => 'mensuelle', 'duree' => 30, 'prix' => 15000]);

        $sw3 = Software::create([
            'nom' => 'FACTU PLUS',
            'description' => 'Gestion de facturation.',
            'categorie' => 'Finance',
            'capture' => null
        ]);
        License::create(['software_id' => $sw3->id, 'type' => 'trimestrielle', 'duree' => 90, 'prix' => 25000]);
        License::create(['software_id' => $sw3->id, 'type' => 'annuelle', 'duree' => 365, 'prix' => 80000]);
        License::create(['software_id' => $sw3->id, 'type' => 'mensuelle', 'duree' => 30, 'prix' => 10000]);

        $this->command->info('✅ Utilisateurs et logiciels créés avec succès !');
    }
}