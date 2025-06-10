<?php

namespace App\Filament\Resources\DemandeVoitureResource\Pages;

use App\Filament\Resources\DemandeVoitureResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;



class CreateDemandeVoiture extends CreateRecord
{
    protected static string $resource = DemandeVoitureResource::class;

}
