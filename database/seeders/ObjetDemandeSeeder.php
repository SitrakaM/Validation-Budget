<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ObjetDemande; // Assure-toi que tu importes ton modèle

class ObjetDemandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ObjetDemande::insert([
            ['nomObjet' => 'Budget'],
            ['nomObjet' => 'Matériel'],
            ['nomObjet' => 'Voiture'],
            ['nomObjet' => 'Autre'],

        ]);   
     }
}
