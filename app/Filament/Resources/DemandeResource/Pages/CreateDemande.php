<?php

namespace App\Filament\Resources\DemandeResource\Pages;

use App\Filament\Resources\DemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;



class CreateDemande extends CreateRecord
{
    protected static string $resource = DemandeResource::class;


    protected function afterCreate(): void
    {
        $validateurs = User::whereHas('role',fn($q)=>$q->whereIn('nomRole',['Validateur','ValidateurRapport']))->get();
        foreach ($validateurs as $validateur) {
            $this->record->userValidationDemande()->attach($validateur->id);
        }
    }
}
