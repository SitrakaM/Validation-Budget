<?php

namespace App\Filament\Resources\ValidationRapportResource\Pages;

use App\Filament\Resources\ValidationRapportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewValidationRapport extends ViewRecord
{
    protected static string $resource = ValidationRapportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
