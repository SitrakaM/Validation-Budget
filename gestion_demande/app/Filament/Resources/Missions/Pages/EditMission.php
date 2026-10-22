<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Resources\Missions\MissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class EditMission extends EditRecord
{
    protected static string $resource = MissionResource::class;

    public function getTitle(): string | Htmlable
    {
        if ($this->record->status === 'valide') {
            return 'Consulter Mission (Validée)';
        }
        if ($this->record->status === 'en_cours') {
            return 'Modifier Mission (En cours)';
        }
        return 'Modifier Mission';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->disabled(fn () => $this->record->status === 'valide')
                ->requiresConfirmation(),
        ];
    }

    protected function beforeSave(): void
    {
        if ($this->record->status === 'valide') {
            throw new Halt('Cette mission est validée et ne peut pas être modifiée.');
        }
    }
}
