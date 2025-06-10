<?php

namespace App\Filament\Resources\DemandeVoitureResource\Pages;

use App\Filament\Resources\DemandeVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDemandeVoiture extends EditRecord
{
    protected static string $resource = DemandeVoitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
