<?php

namespace App\Filament\Resources\DemandeVoitureResource\Pages;

use App\Filament\Resources\DemandeVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;


class ListDemandeVoitures extends ListRecords
{
    protected static string $resource = DemandeVoitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Budget'])),
        ];
    }
}
