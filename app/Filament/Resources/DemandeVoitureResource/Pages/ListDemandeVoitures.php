<?php

namespace App\Filament\Resources\DemandeVoitureResource\Pages;

use App\Filament\Resources\DemandeVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDemandeVoitures extends ListRecords
{
    protected static string $resource = DemandeVoitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
