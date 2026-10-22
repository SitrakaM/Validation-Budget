<?php

namespace App\Observers;

use App\Models\MissionUser;

class MissionUserObserver
{
    /**
     * Handle the MissionUser "saving" event.
     */
    public function saving(MissionUser $missionUser): void
    {
        // Si le rôle est 'responsable', le scope doit être 'both'
        if ($missionUser->role_mission === 'responsable') {
            $missionUser->scope = 'both';
        }
    }

    /**
     * Handle the MissionUser "updating" event.
     */
    public function updating(MissionUser $missionUser): void
    {
        // Si le rôle est changé vers 'responsable', le scope doit être 'both'
        if ($missionUser->role_mission === 'responsable') {
            $missionUser->scope = 'both';
        }
    }
}
