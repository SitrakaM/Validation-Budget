<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ObjetRapport; // Assure-toi que tu importes ton modèle

class ObjetRapportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ObjetRapport::insert([
            ['nomObjet' => 'Mission'],
            ['nomObjet' => 'Financière'],
            ['nomObjet' => 'Voiture'],
            ['nomObjet' => 'Autre'],

        ]);   
     }
}
