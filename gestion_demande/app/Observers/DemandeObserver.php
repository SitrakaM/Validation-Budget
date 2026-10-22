<?php

namespace App\Observers;

use App\Models\Demande;

class DemandeObserver
{
    /**
     * Handle the Demande "saved" event.
     * Update mission status based on demande statut.
     */
    public function saved(Demande $demande): void
    {
        // Only update mission status if the demande has changed its statut
        if ($demande->isDirty('statut') || $demande->wasRecentlyCreated) {
            $this->updateMissionStatus($demande);
        }
    }

    /**
     * Update the mission status based on all its demandes and rapports.
     * 
     * Logic:
     * - If ALL demandes and rapports are 'non_soumis' or 'en_attente' → mission status = 'en_attente'
     * - Else if ALL demandes and rapports are 'valide' → mission status = 'valide'
     * - Else → mission status = 'en_cours'
     */
    private function updateMissionStatus(Demande $demande): void
    {
        $mission = $demande->mission;
        if (!$mission) {
            return;
        }

        // Check if all demandes are in 'non_soumis' or 'en_attente' status
        $allDemandesWaiting = !$mission->demandes()
            ->whereNotIn('statut', ['non_soumis', 'en_attente'])
            ->exists();

        // Check if all rapports are in 'non_soumis' or 'en_attente' status
        $allRapportsWaiting = !$mission->rapports()
            ->whereNotIn('statut', ['non_soumis', 'en_attente'])
            ->exists();

        // Check if all demandes and rapports are 'valide'
        $allDemandesValid = !$mission->demandes()
            ->where('statut', '!=', 'valide')
            ->exists();

        $allRapportsValid = !$mission->rapports()
            ->where('statut', '!=', 'valide')
            ->exists();

        // Determine new status based on progress
        $newStatus = 'en_cours'; // Default: in progress

        if ($allDemandesWaiting && $allRapportsWaiting) {
            // All are waiting → mission is waiting
            $newStatus = 'en_attente';
        } elseif ($allDemandesValid && $allRapportsValid) {
            // All are validated → mission is validated
            $newStatus = 'valide';
        }

        if ($mission->status !== $newStatus) {
            $mission->update(['status' => $newStatus]);
        }
    }
}
