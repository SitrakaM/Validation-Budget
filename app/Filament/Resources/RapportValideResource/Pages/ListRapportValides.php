<?php

namespace App\Filament\Resources\RapportValideResource\Pages;

use App\Filament\Resources\RapportValideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRapportValides extends ListRecords
{
    protected static string $resource = RapportValideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
