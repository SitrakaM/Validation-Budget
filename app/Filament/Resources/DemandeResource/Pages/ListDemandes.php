<?php

namespace App\Filament\Resources\DemandeResource\Pages;

use App\Filament\Resources\DemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;


class ListDemandes extends ListRecords
{
    protected static string $resource = DemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Simple'])),
        ];
    }
}
