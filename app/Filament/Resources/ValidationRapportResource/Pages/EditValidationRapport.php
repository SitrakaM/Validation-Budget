<?php

namespace App\Filament\Resources\ValidationRapportResource\Pages;

use App\Filament\Resources\ValidationRapportResource;
use Filament\Actions;

use Filament\Resources\Pages\EditRecord;

class EditValidationRapport extends EditRecord
{
    protected static string $resource = ValidationRapportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
