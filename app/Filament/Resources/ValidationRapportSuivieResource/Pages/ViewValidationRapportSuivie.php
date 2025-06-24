<?php

namespace App\Filament\Resources\ValidationRapportSuivieResource\Pages;

use App\Filament\Resources\ValidationRapportSuivieResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidationRapportSuivie extends ViewRecord
{
    protected static string $resource = ValidationRapportSuivieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
