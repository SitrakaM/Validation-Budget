<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Assure-toi que tu importes ton modèle
use App\Models\Role; // Assure-toi que tu importes ton modèle

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('nomRole', 'Admin')->first();

        User::create([
            'name' => 'Sitraka',
            'email' => 'ratovomanalinas@gmail.com',
            'password' => bcrypt('886400'),
            'role_id' => $adminRole->id, // clé étrangère
        ]);
    }
}
