<?php

namespace App\Filament\Resources\ValidationRapportResource\Pages;

use App\Filament\Resources\ValidationRapportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidationRapports extends ListRecords
{
    protected static string $resource = ValidationRapportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
