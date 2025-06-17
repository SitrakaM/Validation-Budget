<?php

namespace App\Filament\Resources\DemandeValideResource\Pages;

use App\Filament\Resources\DemandeValideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDemandeValides extends ListRecords
{
    protected static string $resource = DemandeValideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
