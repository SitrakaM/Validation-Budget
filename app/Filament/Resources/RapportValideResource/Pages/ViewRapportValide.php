<?php

namespace App\Filament\Resources\RapportValideResource\Pages;

use App\Filament\Resources\RapportValideResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRapportValide extends ViewRecord
{
    protected static string $resource = RapportValideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
