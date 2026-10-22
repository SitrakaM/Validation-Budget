<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Resources\Missions\MissionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewMission extends ViewRecord
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => (
                in_array($this->record->status, ['en_attente', 'en_cours']) &&
                ($user = Auth::user()) && (
                    $user->roles()->pluck('name')->contains('Admin') ||
                    // only mission responsable can edit from the view header
                    ($this->record->users()->where('users.id', $user->id)->where('mission_users.role_mission', 'responsable')->exists())
                )
            )),
        ];
    }
}
