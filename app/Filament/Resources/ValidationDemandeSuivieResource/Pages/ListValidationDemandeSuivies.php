<?php

namespace App\Filament\Resources\ValidationDemandeSuivieResource\Pages;

use App\Filament\Resources\ValidationDemandeSuivieResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidationDemandeSuivies extends ListRecords
{
    protected static string $resource = ValidationDemandeSuivieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
 
}
