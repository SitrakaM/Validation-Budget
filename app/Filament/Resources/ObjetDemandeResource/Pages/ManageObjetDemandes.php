<?php

namespace App\Filament\Resources\ObjetDemandeResource\Pages;

use App\Filament\Resources\ObjetDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageObjetDemandes extends ManageRecords
{
    protected static string $resource = ObjetDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
