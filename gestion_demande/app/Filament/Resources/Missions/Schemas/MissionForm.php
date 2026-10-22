<?php

namespace App\Filament\Resources\Missions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('site_id')
                    ->relationship('site', 'name')
                    ->required(),
                Select::make('activites')
                    ->multiple()
                    ->relationship('activites', 'name')
                    ->preload(),
                Select::make('status')
                    ->options([
                        'en_attente' => 'En attente',
                        'en_cours' => 'En cours',
                        'valide' => 'Validé',
                    ])
                    ->default('en_attente')
                    ->disabled()
                    ->helperText('Le statut est mis à jour automatiquement selon le statut des demandes et rapports.')
            ]);
    }
}

