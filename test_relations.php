<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mission;

// Simuler comment Filament charge les données
$missions = Mission::with("users.poste")->get();

foreach ($missions as $mission) {
    echo "Mission ID: " . $mission->id . "\n";
    echo "Users loaded: " . ($mission->relationLoaded("users") ? "YES" : "NO") . "\n";
    
    if ($mission->relationLoaded("users")) {
        foreach ($mission->users as $user) {
            echo "  User: " . $user->name . "\n";
            echo "  Poste loaded: " . ($user->relationLoaded("poste") ? "YES" : "NO") . "\n";
            echo "  Poste ID: " . $user->poste_id . "\n";
            echo "  Poste Name: " . ($user->poste ? $user->poste->name : "NULL") . "\n";
            echo "  Pivot role: " . $user->pivot->role_mission . "\n";
        }
    }
    echo "\n";
}
?>
