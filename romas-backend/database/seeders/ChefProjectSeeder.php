<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ChefProjectSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'chef@softbridge.com'],
            [
                'nom' => 'Jean',
                'prenom' => 'Dupont',
                'password' => Hash::make('password'),
                'role' => 'chef_projet',
                'telephone' => '0102030405',
            ]
        );
    }
}