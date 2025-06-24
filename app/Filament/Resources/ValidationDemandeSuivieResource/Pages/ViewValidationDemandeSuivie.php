<?php

namespace App\Filament\Resources\ValidationDemandeSuivieResource\Pages;

use App\Filament\Resources\ValidationDemandeSuivieResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidationDemandeSuivie extends ViewRecord
{
    protected static string $resource = ValidationDemandeSuivieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
