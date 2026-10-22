<?php

namespace App\Filament\Resources\Demandes\Pages;

use App\Filament\Resources\Demandes\DemandeResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewDemande extends ViewRecord
{
    protected static string $resource = DemandeResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Informations Générales
                TextInput::make('id')
                    ->label('ID Demande')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('created_at')
                    ->label('Crée le')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),
                TextInput::make('updated_at')
                    ->label('Mise à jour')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),
                TextInput::make('statut')
                    ->label('Statut')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('type_demande_id')
                    ->label('Type de Demande')
                    ->relationship('typeDemande', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('site_id')
                    ->label('Site')
                    ->relationship('site', 'name')
                    ->disabled()
                    ->dehydrated(false),

                // État et Validations
                Toggle::make('sortie')
                    ->label('Sortie')
                    ->disabled()
                    ->dehydrated(false),
                Toggle::make('validated_by_special')
                    ->label('Validée par Spécial')
                    ->disabled()
                    ->dehydrated(false),

                // Mission
                Select::make('mission_id')
                    ->label('Mission')
                    ->relationship('mission', 'id')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('mission.id')
                    ->label('ID Mission')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('mission.users')
                    ->label('Responsable')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(function ($record) {
                        if (!$record || !$record->mission) {
                            return 'N/A';
                        }
                        $responsable = $record->mission->users()
                            ->wherePivot('role_mission', 'responsable')
                            ->first();
                        return $responsable ? $responsable->name : 'N/A';
                    }),
                TextInput::make('mission.users.poste.name')
                    ->label('Poste du Responsable')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(function ($record) {
                        if (!$record || !$record->mission) {
                            return 'N/A';
                        }
                        $responsable = $record->mission->users()
                            ->wherePivot('role_mission', 'responsable')
                            ->first();
                        if (!$responsable || !$responsable->poste) {
                            return 'N/A';
                        }
                        return $responsable->poste->name;
                    }),
                Textarea::make('mission.description')
                    ->label('Description de la Mission')
                    ->disabled()
                    ->dehydrated(false),

                // Activités
                Select::make('activites')
                    ->label('Activités')
                    ->multiple()
                    ->relationship('activites', 'name')
                    ->disabled()
                    ->dehydrated(false),

                // Fichiers
                FileUpload::make('fichier')
                    ->label('Fichiers de la Demande')
                    ->multiple()
                    ->disabled()
                    ->dehydrated(false),

                // Fichiers de Validation
                Textarea::make('fichier_validation')
                    ->label('Fichiers de Validation Spéciale')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(function ($record) {
                        if (!$record || !$record->validated_by_special) {
                            return null;
                        }

                        $validation = $record->validations()
                            ->whereHas('validateur', function ($q) {
                                $q->whereHas('roles', function ($subq) {
                                    $subq->where('name', 'Validateur special');
                                });
                            })
                            ->first();

                        if (!$validation || !$validation->fichier_commentaire || empty($validation->fichier_commentaire)) {
                            return 'Aucun fichier de validation';
                        }

                        $fileList = [];
                        if (is_array($validation->fichier_commentaire)) {
                            foreach ($validation->fichier_commentaire as $file) {
                                if (is_string($file)) {
                                    $fileList[] = basename($file);
                                }
                            }
                        }
                        return !empty($fileList) ? implode("\n", $fileList) : 'Aucun fichier de validation';
                    })
                    ->visible(fn($record) => $record && $record->validated_by_special),
            ]);
    }
}
