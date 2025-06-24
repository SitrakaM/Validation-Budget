<?php

namespace App\Filament\Resources\ValidationDemandeResource\Pages;

use App\Filament\Resources\ValidationDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidationDemandes extends ListRecords
{
    protected static string $resource = ValidationDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
   
}
