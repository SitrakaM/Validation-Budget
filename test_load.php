<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mission;

// Test 1: SANS with (comme avant ma modification)
echo "=== TEST 1: SANS with() ===\n";
$missions1 = Mission::limit(1)->get();
foreach ($missions1 as $mission) {
    $responsable = $mission->users->first(function ($user) {
        return $user->pivot->role_mission === "responsable";
    });
    if ($responsable) {
        echo "Responsable: " . $responsable->name . "\n";
        echo "Poste (lazy): " . ($responsable->poste ? $responsable->poste->name : "NULL") . "\n";
    }
}

echo "\n=== TEST 2: AVEC with() ===\n";
// Test 2: AVEC with (ma modification)
$missions2 = Mission::with("users.poste")->limit(1)->get();
foreach ($missions2 as $mission) {
    $responsable = $mission->users->first(function ($user) {
        return $user->pivot->role_mission === "responsable";
    });
    if ($responsable) {
        echo "Responsable: " . $responsable->name . "\n";
        echo "Poste (eager): " . ($responsable->poste ? $responsable->poste->name : "NULL") . "\n";
    }
}
?>
