<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mission;
use App\Models\User;

$mission = Mission::has('users')->first();
if ($mission) {
    echo "Mission ID: " . $mission->id . "\n";
    $responsable = $mission->users()
        ->wherePivot('role_mission', 'responsable')
        ->first();
    if ($responsable) {
        echo "Responsable: " . $responsable->name . "\n";
        echo "Poste ID: " . ($responsable->poste_id ?? 'NULL') . "\n";
        $responsable->load('poste');
        echo "Poste Name: " . ($responsable->poste ? $responsable->poste->name : 'NULL') . "\n";
    }
} else {
    echo "No mission found\n";
}
?>
