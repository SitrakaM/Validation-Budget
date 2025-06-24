<?php

namespace App\Filament\Resources\ValidationRapportSuivieResource\Pages;

use App\Filament\Resources\ValidationRapportSuivieResource;
use Filament\Actions;

use Filament\Resources\Pages\EditRecord;

class EditValidationRapportSuivie extends EditRecord
{
    protected static string $resource = ValidationRapportSuivieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
        ];
    }
}
