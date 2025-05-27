<?php

namespace App\Filament\Resources\DemandeResource\Pages;

use App\Filament\Resources\DemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDemandes extends ListRecords
{
    protected static string $resource = DemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
