<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Site;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = ['Antananarivo', 'Toamasina', 'Fianarantsoa', 'Mahajanga'];

        foreach ($sites as $site) {
            Site::updateOrCreate(['name' => $site]);
        }
    }
}
