<?php

namespace App\Filament\Resources\DemandeValideResource\Pages;

use App\Filament\Resources\DemandeValideResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDemandeValide extends ViewRecord
{
    protected static string $resource = DemandeValideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
