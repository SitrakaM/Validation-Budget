<?php

namespace App\Filament\Resources\Activites\Pages;

use App\Filament\Resources\Activites\ActiviteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ManageActivites extends ManageRecords
{
    protected static string $resource = ActiviteResource::class;

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        if (!$user || !$user->roles()->pluck('name')->contains('Admin')) {
            throw new AccessDeniedHttpException();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
