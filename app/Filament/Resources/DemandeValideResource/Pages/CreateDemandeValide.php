<?php

namespace App\Filament\Resources\DemandeValideResource\Pages;

use App\Filament\Resources\DemandeValideResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;



class CreateDemandeValide extends CreateRecord
{
    protected static string $resource = DemandeValideResource::class;


    protected function afterCreate(): void
    {
        $validateurs = User::whereHas('role',fn($q)=>$q->whereIn('nomRole',['Validateur','Special','ValidateurRapport']))->get();
        foreach ($validateurs as $validateur) {
            $this->record->userValidationDemande()->attach($validateur->id);
        }
    }
}
