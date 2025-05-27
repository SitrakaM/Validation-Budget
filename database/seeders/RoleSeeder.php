<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role; // Assure-toi que tu importes ton modèle

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            ['nomRole' => 'Admin'],
            ['nomRole' => 'Validateur'],
            ['nomRole' => 'ValidateurRapport'],
            ['nomRole' => 'Special'],
            ['nomRole' => 'Simple'],
            ['nomRole' => 'Budget'],
        ]);   
     }
}
