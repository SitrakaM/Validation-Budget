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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use App\Models\Site;
use App\Models\User;
use App\Models\Poste;
use App\Models\Activite;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class MissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
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
                
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(25)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),
                SelectColumn::make('activites')
                    ->label('Activités')
                    ->options(function ($record) {
                        return $record->activites()->pluck('activites.name', 'activites.id')->toArray();
                    })
                    ->default(function ($record) {
                        return $record->activites()->first()?->id;
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('users')
                    ->label('Responsable')
                    ->formatStateUsing(function ($record) {
                        $responsable = $record->users()
                            ->wherePivot('role_mission', 'responsable')
                            ->first();
                        if (!$responsable) {
                            return 'N/A';
                        }
                        return $responsable->name;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('users.poste.name')
                    ->label('Poste')
                    ->formatStateUsing(function ($record) {
                        $responsable = $record->users()
                            ->wherePivot('role_mission', 'responsable')
                            ->first();
                        if (!$responsable || !$responsable->poste) {
                            return 'N/A';
                        }
                        return $responsable->poste->name;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
               
                TextColumn::make('demandes_count')
                    ->label('Demandes')
                    ->counts('demandes')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('rapports')
                    ->label('Rapports soumis')
                    ->getStateUsing(function ($record) {
                        return $record->rapports()
                            ->where('statut', '!=', 'non_soumis')
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),
                
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
                Filter::make('id')
                    ->form([
                        Select::make('id')
                            ->label('ID')
                            ->options(function () {
                                return \App\Models\Mission::distinct()
                                    ->pluck('id', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['id'],
                            fn (Builder $query, $id): Builder => $query->where('id', $id)
                        );
                    }),
                SelectFilter::make('site_id')
                    ->label('Filtrer par site')
                    ->relationship('site', 'name')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('activites')
                    ->label('Filtrer par activité')
                    ->relationship('activites', 'name')
                    ->preload()
                    ->searchable(),
                Filter::make('responsable')
                    ->form([
                        Select::make('responsable')
                            ->label('Responsable')
                            ->options(function () {
                                return \DB::table('mission_users')
                                    ->where('role_mission', 'responsable')
                                    ->join('users', 'mission_users.user_id', '=', 'users.id')
                                    ->distinct()
                                    ->pluck('users.name', 'users.id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['responsable'],
                            fn (Builder $query, $value): Builder => $query->whereHas('users', function ($subquery) use ($value) {
                                $subquery->where('users.id', $value)
                                    ->where('mission_users.role_mission', 'responsable');
                            })
                        );
                    }),
                Filter::make('poste')
                    ->form([
                        Select::make('poste')
                            ->label('Poste')
                            ->options(function () {
                                $posteIds = \DB::table('mission_users')
                                    ->where('role_mission', 'responsable')
                                    ->join('users', 'mission_users.user_id', '=', 'users.id')
                                    ->whereNotNull('users.poste_id')
                                    ->distinct()
                                    ->pluck('users.poste_id');
                                
                                return Poste::whereIn('id', $posteIds)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['poste'],
                            fn (Builder $query, $value): Builder => $query->whereHas('users', function ($subquery) use ($value) {
                                $subquery->where('users.poste_id', $value)
                                    ->where('mission_users.role_mission', 'responsable');
                            })
                        );
                    }),
                SelectFilter::make('status')
                    ->label('Filtrer par statut')
                    ->options([
                        'en_attente' => 'En attente',
                        'en_cours' => 'En cours',
                        'valide' => 'Validé',
                    ]),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Créée à partir du'),
                        DatePicker::make('created_until')->label('Créée jusqu\'au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->columnSpan(2)
                    ->columns(2),
            ], layout: FiltersLayout::AboveContent)->filtersFormColumns(4)
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
