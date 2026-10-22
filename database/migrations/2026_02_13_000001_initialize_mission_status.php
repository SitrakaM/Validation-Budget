<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Mission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Initialize status for existing missions
        foreach (Mission::all() as $mission) {
            // Check if all demandes and rapports are valide
            $hasInvalidDemande = $mission->demandes()
                ->where('statut', '!=', 'valide')
                ->exists();

            $hasInvalidRapport = $mission->rapports()
                ->where('statut', '!=', 'valide')
                ->exists();

            $status = (!$hasInvalidDemande && !$hasInvalidRapport) ? 'valide' : 'en_cours';
            $mission->update(['status' => $status]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
