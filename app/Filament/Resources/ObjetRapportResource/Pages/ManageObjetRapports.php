<?php

namespace App\Filament\Resources\ObjetRapportResource\Pages;

use App\Filament\Resources\ObjetRapportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageObjetRapports extends ManageRecords
{
    protected static string $resource = ObjetRapportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
