<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Resources\Missions\MissionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CreateMission extends CreateRecord
{
    protected static string $resource = MissionResource::class;

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();

        if (! $user || ! ($user->roles()->pluck('name')->contains('Simple') || $user->roles()->pluck('name')->contains('Admin'))) {
            throw new AccessDeniedHttpException();
        }
    }
}
