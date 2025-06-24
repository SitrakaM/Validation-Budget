<?php

namespace App\Filament\Resources\RapportValideResource\Pages;

use App\Filament\Resources\RapportValideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;

class EditRapportValide extends EditRecord
{
    protected static string $resource = RapportValideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
        ];
    }

}
