<?php

namespace App\Filament\Resources\ActiviteResource\Pages;

use App\Filament\Resources\ActiviteResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageActivites extends ManageRecords
{
    protected static string $resource = ActiviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
