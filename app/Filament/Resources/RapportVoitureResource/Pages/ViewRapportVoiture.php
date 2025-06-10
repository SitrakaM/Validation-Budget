<?php

namespace App\Filament\Resources\RapportVoitureResource\Pages;

use App\Filament\Resources\RapportVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRapportVoiture extends ViewRecord
{
    protected static string $resource = RapportVoitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
