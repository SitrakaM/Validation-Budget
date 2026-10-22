<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TypeDemande;

class TypeDemandeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Achat', 'Maintenance', 'Transport', 'Autre'];

        foreach ($types as $type) {
            TypeDemande::updateOrCreate(['name' => $type]);
        }
    }
}
