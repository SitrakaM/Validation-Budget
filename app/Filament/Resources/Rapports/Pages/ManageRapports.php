<?php

namespace App\Filament\Resources\Rapports\Pages;

use App\Filament\Resources\Rapports\RapportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRapports extends ManageRecords
{
    protected static string $resource = RapportResource::class;

    protected function getHeaderActions(): array
    {
        // Disable global creation from the Rapports index; rapports must be created via Mission relation manager
        return [];
    }
}
