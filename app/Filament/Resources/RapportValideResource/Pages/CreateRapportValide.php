<?php

namespace App\Filament\Resources\RapportValideResource\Pages;

use App\Filament\Resources\RapportValideResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateRapportValide extends CreateRecord
{
    protected static string $resource = RapportValideResource::class;
    // protected function afterCreate(): void
    // {
    //     $validateurs = User::whereHas('role',fn($q)=>$q->whereIn('nomRole',['ValidateurRapport']))->get();

    //     foreach ($validateurs as $validateur) {
    //         $this->record->userValidationRapport()->attach($validateur->id);
    //     }
    // }
}
