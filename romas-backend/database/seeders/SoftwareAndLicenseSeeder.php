<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Software;
use App\Models\License;

class SoftwareAndLicenseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Création du Logiciel
        $software = Software::create([
            'nom' => 'GESTION PRO',
            'description' => 'Logiciel de gestion complet pour PME.',
            'categorie' => 'Gestion',
            'capture' => null,
        ]);

        // 2. Création de ses licences (abonnements)
        License::create([
            'software_id' => $software->id,
            'type' => 'trimestrielle',
            'duree' => 90,
            'prix' => 50000,
        ]);

        License::create([
            'software_id' => $software->id,
            'type' => 'annuelle',
            'duree' => 365,
            'prix' => 160000,
        ]);

        License::create([
            'software_id' => $software->id,
            'type' => 'mensuelle',
            'duree' => 30,
            'prix' => 20000,
        ]);

        $this->command->info('Logiciel GESTION PRO et ses licences créés avec succès !');
    }
}