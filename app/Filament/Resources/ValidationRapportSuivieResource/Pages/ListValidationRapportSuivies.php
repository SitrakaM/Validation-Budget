<?php

namespace App\Filament\Resources\ValidationRapportSuivieResource\Pages;

use App\Filament\Resources\ValidationRapportSuivieResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidationRapportSuivies extends ListRecords
{
    protected static string $resource = ValidationRapportSuivieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
