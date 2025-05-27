<?php

namespace App\Filament\Resources\RapportResource\Pages;

use App\Filament\Resources\RapportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRapport extends ViewRecord
{
    protected static string $resource = RapportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
