<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Demande;
use App\Models\TypeDemande;
use App\Models\Site;
use App\Models\Mission;

$type = TypeDemande::first();
$site = Site::first();
$mission = Mission::first();

$d = Demande::create([
    'fichier' => ['test.pdf'],
    'type_demande_id' => $type->id,
    'site_id' => $site->id,
    'mission_id' => $mission->id,
]);

echo "Created Demande id={$d->id} statut={$d->statut}\n";

// set statut to valide (allowed)
$d->statut = 'valide';
$d->save();

echo "Set statut to valide\n";

// attempt to modify after statut is valide (should be blocked)
try {
    $d->fichier = ['changed.pdf'];
    $d->save();
    echo "Update succeeded (ERROR)\n";
} catch (Throwable $e) {
    echo "Update blocked: " . get_class($e) . " - " . $e->getMessage() . "\n";
}

// attempt delete (should be blocked)
try {
    $d->delete();
    echo "Delete succeeded (ERROR)\n";
} catch (Throwable $e) {
    echo "Delete blocked: " . get_class($e) . " - " . $e->getMessage() . "\n";
}
