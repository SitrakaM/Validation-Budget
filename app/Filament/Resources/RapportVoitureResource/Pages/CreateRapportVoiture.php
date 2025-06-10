<?php

namespace App\Filament\Resources\RapportVoitureResource\Pages;

use App\Filament\Resources\RapportVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateRapportVoiture extends CreateRecord
{
    protected static string $resource = RapportVoitureResource::class;
    // protected function afterCreate(): void
    // {
    //     $validateurs = User::whereHas('role',fn($q)=>$q->whereIn('nomRole',['ValidateurRapport']))->get();

    //     foreach ($validateurs as $validateur) {
    //         $this->record->userValidationRapport()->attach($validateur->id);
    //     }
    // }
}
