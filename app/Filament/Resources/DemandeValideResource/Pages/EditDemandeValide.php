<?php

namespace App\Filament\Resources\DemandeValideResource\Pages;

use App\Filament\Resources\DemandeValideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDemandeValide extends EditRecord
{
    protected static string $resource = DemandeValideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
