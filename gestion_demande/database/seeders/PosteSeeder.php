<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Poste;

class PosteSeeder extends Seeder
{
    public function run(): void
    {
        $postes = ['Chef', 'Technicien', 'Assistant', 'Responsable Mission'];

        foreach ($postes as $poste) {
            Poste::updateOrCreate(['name' => $poste]);
        }
    }
}
