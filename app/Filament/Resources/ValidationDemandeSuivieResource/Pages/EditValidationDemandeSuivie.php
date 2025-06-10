<?php

namespace App\Filament\Resources\ValidationDemandeSuivieResource\Pages;

use App\Filament\Resources\ValidationDemandeSuivieResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidationDemandeSuivie extends EditRecord
{
    protected static string $resource = ValidationDemandeSuivieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
