<?php

namespace App\Filament\Resources\ValidationDemandeResource\Pages;

use App\Filament\Resources\ValidationDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidationDemande extends EditRecord
{
    protected static string $resource = ValidationDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
