<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TypeRapport;
use App\Models\Role;

class TypeRapportSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Rapport Financier',
            'Compte-rendu',
            'Bdd+Rapport',
            'Photo',
            'soumission pieces',
        ];

        foreach ($types as $type) {
            TypeRapport::updateOrCreate(['name' => $type]);
            // Create corresponding Role as model events may be disabled during seeding
            Role::updateOrCreate(['name' => $type]);
        }
    }
}
