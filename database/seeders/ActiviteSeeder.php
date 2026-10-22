<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activite;

class ActiviteSeeder extends Seeder
{
    public function run(): void
    {
        $activites = ['Construction', 'Réhabilitation', 'Formation', 'Consultation'];

        foreach ($activites as $activite) {
            Activite::updateOrCreate(['name' => $activite]);
        }
    }
}
