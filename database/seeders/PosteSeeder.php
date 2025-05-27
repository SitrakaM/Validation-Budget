<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Poste; // Assure-toi que tu importes ton modèle

class PosteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Poste::insert([
            ['nomPoste' => 'General_Administrateur'],
            ['nomPoste' => 'Directeur'],
            ['nomPoste' => 'Manager_Operations'],
            ['nomPoste' => 'Manager_Programme_Technique'],
            ['nomPoste' => 'Responsable_Developpement_Communautaire'],
            ['nomPoste' => 'Responsable_Education_Sig'],
            ['nomPoste' => 'Wetland_Specialiste'],
            ['nomPoste' => 'Coordinateur_Regional_Boeny_Betsiboka'],
            ['nomPoste' => 'Coordinateur_Regional_V7V_Bongolava'],
            ['nomPoste' => 'Superviseur_Restoration_Ecologique'],
            ['nomPoste' => 'Responsable_Communications'],
            ['nomPoste' => 'Assistant_Admin_Finance'],
            ['nomPoste' => 'Responsable_Agri-business'],
            ['nomPoste' => 'Technicien_Agricole'],
            ['nomPoste' => 'Chef_de_Site'],
            ['nomPoste' => 'Assistant_Technique'],
            ['nomPoste' => 'Agent_de_Liaison'],
        ]);   
     }
}
