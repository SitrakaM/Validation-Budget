<?php

namespace App\Observers;

use App\Models\Validation;

class ValidationObserver
{
    /**
     * Handle the Validation "saved" event.
     * Update mission status when a validator makes a decision
     */
    public function saved(Validation $validation): void
    {
        // Only trigger if a decision was actually made (not just pending)
        // Decision is made when it's not 'en_cours' (pending)
        if ($validation->decision === Validation::DECISION_PENDING) {
            return;
        }

        // Check if this is a change from pending to a decision (use wasChanged for 'saved')
        if ($validation->wasChanged('decision') && $validation->getOriginal('decision') === Validation::DECISION_PENDING) {
            $this->updateMissionStatus($validation);
        }
    }

    /**
     * Update the mission status when a validation decision is made
     * 
     * Note: The actual status update is handled by DemandeObserver and RapportObserver
     * when the validable's statut is updated via applyDecision().
     * This observer just ensures the process is triggered.
     */
    private function updateMissionStatus(Validation $validation): void
    {
        // The actual mission status update will be triggered through:
        // 1. Validation decision changes
        // 2. This triggers applyDecision() on the validable (Demande/Rapport)
        // 3. Which changes the validable's statut
        // 4. Which triggers DemandeObserver or RapportObserver
        // 5. Which recalculates mission status based on all children
        // So we don't need to force any status update here
    }
}
