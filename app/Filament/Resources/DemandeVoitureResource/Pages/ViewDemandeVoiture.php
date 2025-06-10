<?php

namespace App\Filament\Resources\DemandeVoitureResource\Pages;

use App\Filament\Resources\DemandeVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDemandeVoiture extends ViewRecord
{
    protected static string $resource = DemandeVoitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
