<?php

namespace App\Filament\Resources\RapportVoitureResource\Pages;

use App\Filament\Resources\RapportVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRapportVoitures extends ListRecords
{
    protected static string $resource = RapportVoitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
