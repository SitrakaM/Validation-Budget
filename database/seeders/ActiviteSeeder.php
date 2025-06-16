<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Activite; // Assure-toi que tu importes ton modèle

class ActiviteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Activite::insert([
            ['nomActivite' => 'Aucun'],
        ]);   
     }
}
