<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Poste;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les rôles
        $adminRole = Role::where('name', 'Admin')->first();
        $simpleRole = Role::where('name', 'Simple')->first();
        $validateRole = Role::where('name', 'Valider demande')->first();

        // Récupérer un poste par défaut (ou créer)
        $poste = Poste::first() ?? Poste::create(['name' => 'Développeur']);

        // 1. Utilisateur Admin
        $admin= User::create([
            'name' => 'Sitraka',
            'email' => 'ratovomanalinas@gmail.com',
            'password' => Hash::make('886400'),
            'poste_id' => $poste->id,
        ]);
        $admin->roles()->attach($adminRole);

        // 4. Utilisateurs Simple
        $simpleUsers = [
            ['name' => 'Simple 1', 'email' => 'simple1@example.com'],
            ['name' => 'Simple 2', 'email' => 'simple2@example.com'],
            ['name' => 'Simple 3', 'email' => 'simple3@example.com'],
            ['name' => 'Special', 'email' => 'special@example.com'],
        ];

        foreach ($simpleUsers as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('123'),
                'poste_id' => $poste->id,
            ]);
            $user->roles()->attach($simpleRole);
        }

        // 4. Utilisateurs Validateur (Valider demande)
        $validateUsers = [
            ['name' => 'Validateur 1', 'email' => 'validate1@example.com'],
            ['name' => 'Validateur 2', 'email' => 'validate2@example.com'],
            ['name' => 'Validateur 3', 'email' => 'validate3@example.com'],
            ['name' => 'Validateur 4', 'email' => 'validate4@example.com'],
        ];

        foreach ($validateUsers as $userData) {
            $user= User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('123'),
                'poste_id' => $poste->id,
            ]);
            $user->roles()->attach($validateRole);
        }
    }
}
