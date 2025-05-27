<?php

namespace App\Filament\Resources\RapportResource\Pages;

use App\Filament\Resources\RapportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateRapport extends CreateRecord
{
    protected static string $resource = RapportResource::class;
    // protected function afterCreate(): void
    // {
    //     $validateurs = User::whereHas('role',fn($q)=>$q->whereIn('nomRole',['ValidateurRapport']))->get();

    //     foreach ($validateurs as $validateur) {
    //         $this->record->userValidationRapport()->attach($validateur->id);
    //     }
    // }
}
