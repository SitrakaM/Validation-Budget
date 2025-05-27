<?php

namespace App\Filament\Resources\ValidationDemandeResource\Pages;

use App\Filament\Resources\ValidationDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidationDemande extends ViewRecord
{
    protected static string $resource = ValidationDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
