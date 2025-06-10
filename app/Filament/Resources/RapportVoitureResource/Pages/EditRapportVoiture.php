<?php

namespace App\Filament\Resources\RapportVoitureResource\Pages;

use App\Filament\Resources\RapportVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;

class EditRapportVoiture extends EditRecord
{
    protected static string $resource = RapportVoitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

}
