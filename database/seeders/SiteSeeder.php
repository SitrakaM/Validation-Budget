<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Site; // Assure-toi que tu importes ton modèle

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Site::insert([
            ['nomSite' => 'MARIARANO'],
            ['nomSite' => 'MAROANTSETRA'],
            ['nomSite' => 'KAMOTRO'],
            ['nomSite' => 'MADIROMIRAFY'],
            ['nomSite' => 'ANKIRIHITRA'],
            ['nomSite' => 'MAHAJEBY'],
            ['nomSite' => 'AMBOHIJANAHARY'],
            ['nomSite' => 'DABOLAVA'],
            ['nomSite' => 'VOHITRARIVO'],
            ['nomSite' => 'BERENTY'],
        ]);   
     }
}
