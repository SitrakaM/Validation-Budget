<?php

namespace App\Filament\Resources\PosteResource\Pages;

use App\Filament\Resources\PosteResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePostes extends ManageRecords
{
    protected static string $resource = PosteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
