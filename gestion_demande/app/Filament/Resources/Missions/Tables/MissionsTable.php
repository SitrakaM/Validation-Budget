<?php

namespace App\Filament\Resources\Missions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;

class MissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(25)
                    ->searchable(),
                TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('users')
                    ->label('Responsable')
                    ->formatStateUsing(function ($record) {
                        $responsable = $record->users()
                            ->wherePivot('role_mission', 'responsable')
                            ->first();
                        if (!$responsable) {
                            return 'N/A';
                        }
                        $poste = $responsable->poste ? ' (' . $responsable->poste->name . ')' : '';
                        return $responsable->name . $poste;
                    })
                    ->searchable(),
                // TextColumn::make('activites')
                //     ->label('Activités')
                //     ->formatStateUsing(function ($record) {
                //         $activites = $record->activites()
                //             ->pluck('name')
                //             ->toArray();
                //         return !empty($activites) ? implode(', ', $activites) : 'Aucune';
                //     }),
                TextColumn::make('demandes_count')
                    ->label('Demandes')
                    ->counts('demandes')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('rapports')
                    ->label('Rapports soumis')
                    ->getStateUsing(function ($record) {
                        return $record->rapports()
                            ->where('statut', '!=', 'non_soumis')
                            ->count();
                    })
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'en_attente' => 'gray',
                        'en_cours' => 'warning',
                        'valide' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'en_attente' => 'En attente',
                        'en_cours' => 'En cours',
                        'valide' => 'Validé',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->label('Mise à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => (
                    in_array($record->status, ['en_attente', 'en_cours']) &&
                    ($user = Auth::user()) && (
                        // Admins can edit, or mission responsable
                        $user->roles()->pluck('name')->contains('Admin') ||
                        ($record->users()->where('users.id', $user->id)->where('mission_users.role_mission', 'responsable')->exists())
                    )
                )),
                DeleteAction::make()
                    ->visible(fn ($record) => (
                        $record->status === 'en_attente' &&
                        ($user = Auth::user()) && (
                            // Admins can delete, or mission responsable
                            $user->roles()->pluck('name')->contains('Admin') ||
                            ($record->users()->where('users.id', $user->id)->where('mission_users.role_mission', 'responsable')->exists())
                        )
                    ))
                    ->before(function (DeleteAction $action) {
                        $record = $action->getRecord();
                        
                        // Delete all rapports first
                        $record->rapports()->delete();
                        
                        // Delete all demandes
                        $record->demandes()->delete();
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
