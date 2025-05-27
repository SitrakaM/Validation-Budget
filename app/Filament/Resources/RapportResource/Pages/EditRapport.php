<?php

namespace App\Filament\Resources\RapportResource\Pages;

use App\Filament\Resources\RapportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;

class EditRapport extends EditRecord
{
    protected static string $resource = RapportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

}
