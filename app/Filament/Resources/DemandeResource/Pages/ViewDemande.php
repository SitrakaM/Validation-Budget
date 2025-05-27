<?php

namespace App\Filament\Resources\DemandeResource\Pages;

use App\Filament\Resources\DemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDemande extends ViewRecord
{
    protected static string $resource = DemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
