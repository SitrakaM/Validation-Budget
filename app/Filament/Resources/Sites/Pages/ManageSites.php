<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ManageSites extends ManageRecords
{
    protected static string $resource = SiteResource::class;

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
