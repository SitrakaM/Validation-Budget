<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Mon Profil';
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('index');
    }

    public function mount(string|int $record = null): void
    {
        // Load the current authenticated user's profile
        $record = Auth::id();
        parent::mount($record);
    }
}
