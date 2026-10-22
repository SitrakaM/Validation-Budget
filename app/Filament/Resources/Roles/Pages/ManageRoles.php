<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

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
