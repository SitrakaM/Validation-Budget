<?php

namespace App\Filament\Resources\Demandes\Pages;

use App\Filament\Resources\Demandes\DemandeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDemandes extends ManageRecords
{
    protected static string $resource = DemandeResource::class;

    protected function getHeaderActions(): array
    {
        // Disable global creation from the Demandes index; demandes must be created via Mission relation manager
        return [];
    }
}
