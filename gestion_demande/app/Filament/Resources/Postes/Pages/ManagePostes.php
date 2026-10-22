<?php

namespace App\Filament\Resources\Postes\Pages;

use App\Filament\Resources\Postes\PosteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ManagePostes extends ManageRecords
{
    protected static string $resource = PosteResource::class;

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
