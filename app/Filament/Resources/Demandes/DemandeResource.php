<?php

namespace App\Filament\Resources\Demandes;

use App\Filament\Resources\Demandes\Pages\ManageDemandes;
use App\Filament\Resources\Demandes\RelationManagers\ValidationsRelationManager;
use App\Models\Demande;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\FileUpload;



class DemandeResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Demande';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fichier')
                    ->required(),
                TextInput::make('statut')
                    ->label('Status')
                    ->hidden(),
                Toggle::make('validated_by_special')
                    ->required(),
                Select::make('type_demande_id')
                    ->relationship('typeDemande', 'name')
                    ->required(),
                Select::make('site_id')
                    ->relationship('site', 'name')
                    ->required(),
                Select::make('mission_id')
                    ->relationship('mission', 'id')
                    ->required(),
                Select::make('activites')
                    ->multiple()
                    ->relationship('activites', 'name')
                    ->preload()
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // FileUpload::make('fichier')
                //     ->required()
                //     ->disabled(fn ($context) => $context === 'view'),
                FileUpload::make('fichier')
                    ->multiple()
                    ->label('Fichiers')
                    ->preserveFilenames()
                    ->required(false)
                    ->downloadable()
                    ->disabled(fn ($context) => $context === 'view'),
                 FileUpload::make('motif_special')
                    ->label('Fichier justificatif (spécial)')
                    ->multiple()
                    ->previewable(false)
                    ->downloadable()
                    ->disk('public'),
                TextInput::make('statut')
                    ->label('Status')
                    ->hidden()
                    ->disabled(fn ($context) => $context === 'view'),
                Toggle::make('validated_by_special')
                    ->required()
                    ->disabled(fn ($context) => $context === 'view'),
                Select::make('type_demande_id')
                    ->relationship('typeDemande', 'name')
                    ->required()
                    ->disabled(fn ($context) => $context === 'view'),
                Select::make('site_id')
                    ->relationship('site', 'name')
                    ->required()
                    ->disabled(fn ($context) => $context === 'view'),
                Select::make('mission_id')
                    ->relationship('mission', 'id')
                    ->required()
                    ->disabled(fn ($context) => $context === 'view'),
                Select::make('activites')
                    ->multiple()
                    ->relationship('activites', 'name')
                    ->preload()
                    ->disabled(fn ($context) => $context === 'view')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordTitleAttribute('Demande')
            ->columns([
                ToggleColumn::make('sortie')
                    ->disabled(function ($record): bool {
                        $user = Auth::user();
                        if (!$user) {
                            return true;
                        }
                        $isResponsableSortie = $user->roles()->pluck('name')->contains('Responsable sortie');
                        // Disable if NOT responsable sortie OR if already checked
                        return !$isResponsableSortie || $record->sortie;
                    }),
                IconColumn::make('validated_by_special')
                    ->boolean(),
                TextColumn::make('typeDemande.name')
                    ->label('Type')
                    ->searchable(),
                TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable(),
                TextColumn::make('mission.id')
                    ->label('Mission')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mission.users')
                    ->label('Responsable')
                    ->formatStateUsing(function ($record) {
                        if (!$record->mission) {
                            return 'N/A';
                        }
                        $responsable = $record->mission->users()
                            ->wherePivot('role_mission', 'responsable')
                            ->first();
                        return $responsable ? $responsable->name : 'N/A';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mission.users.poste.name')
                    ->label('Poste')
                    ->formatStateUsing(function ($record) {
                        if (!$record->mission) {
                            return 'N/A';
                        }
                        $responsable = $record->mission->users()
                            ->wherePivot('role_mission', 'responsable')
                            ->first();
                        if (!$responsable || !$responsable->poste) {
                            return 'N/A';
                        }
                        return $responsable->poste->name;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                SelectColumn::make('activites')
                    ->label('Activités')
                    ->options(function ($record) {
                        return $record->activites()->pluck('activites.name', 'activites.id')->toArray();
                    })
                    ->default(function ($record) {
                        return $record->activites()->first()?->id;
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('sortie')
                    ->label('Sortie')
                    ->options([
                        0 => 'Non',
                        1 => 'Oui',
                    ]),
                SelectFilter::make('validated_by_special')
                    ->label('Spéciale')
                    ->options([
                        0 => 'Non',
                        1 => 'Oui',
                    ]),
                SelectFilter::make('typeDemande')
                    ->label('Type')
                    ->relationship('typeDemande', 'name')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('site_id')
                    ->label('Site')
                    ->relationship('site', 'name')
                    ->preload()
                    ->searchable(),
                Filter::make('responsable')
                    ->form([
                        Select::make('responsable')
                            ->label('Responsable')
                            ->options(\App\Models\User::all()->pluck('name', 'id'))
                            ->searchable()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['responsable'],
                            fn (Builder $query, $value): Builder => $query->whereHas('mission.users', function ($subquery) use ($value) {
                                $subquery->where('users.id', $value)
                                    ->where('mission_users.role_mission', 'responsable');
                            })
                        );
                    }),
                Filter::make('poste')
                    ->form([
                        Select::make('poste')
                            ->label('Poste')
                            ->options(\App\Models\Poste::all()->pluck('name', 'id'))
                            ->searchable()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['poste'],
                            fn (Builder $query, $value): Builder => $query->whereHas('mission.users', function ($subquery) use ($value) {
                                $subquery->where('users.poste_id', $value)
                                    ->where('mission_users.role_mission', 'responsable');
                            })
                        );
                    }),
                SelectFilter::make('mission_id')
                    ->label('Mission')
                    ->relationship('mission', 'id')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('activites')
                    ->label('Activité')
                    ->relationship('activites', 'name')
                    ->preload()
                    ->searchable(),
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
            ], layout: FiltersLayout::AboveContent)->filtersFormColumns(5)
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => ! in_array($record->statut, ['valide', 'en attente des rapports'])),
                DeleteAction::make()
                    ->visible(fn ($record) => ! in_array($record->statut, ['valide', 'en attente des rapports'])),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDemandes::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ValidationsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        $routeName = request()->route()?->getName() ?? '';
        if ($user && str_ends_with($routeName, '.index')) {
            $roles = $user->roles()->pluck('name')->toArray();

            // If the user has 'Responsable sortie' role, let them see all validated demandes
            // regardless of mission access
            if (in_array('Responsable sortie', $roles)) {
                // Allow global view - no mission restriction
            }
            // If user is only 'Simple', keep mission-based restriction
            else if (count($roles) === 1 && $roles[0] === 'Simple') {
                $query->whereHas('mission.users', function (Builder $q) use ($user) {
                    $q->where('users.id', $user->id)
                      ->where('mission_users.role_mission', '!=', 'observateur')
                      ->where(function (Builder $q2) {
                          $q2->where('mission_users.scope', 'demande')
                             ->orWhere('mission_users.scope', 'both')
                             ->orWhere('mission_users.role_mission', 'responsable');
                      });
                });
            } else {
                // Privileged roles that should be allowed to see demandes
                // NOTE: "Responsable sortie" is NOT here - it uses its own logic above
                $privileged = [
                    'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                    'Valider demande', 'Valide demande', 'Validateur special', 'Autre',
                ];

                // If the user has no privileged role but has a role that matches a TypeRapport,
                // they must NOT see demandes (deny access to index)
                $typeNames = \App\Models\TypeRapport::pluck('name')->toArray();
                if (empty(array_intersect($roles, $privileged)) && !empty(array_intersect($roles, $typeNames))) {
                    // Make the query return no results
                    $query->whereRaw('0 = 1');
                }
                // otherwise leave query unmodified (privileged roles can see demandes)
            }
        }

        // Apply the statut filter unconditionally to ensure only validated demandes are shown.
        // This must be applied after access control logic, not gated by route check,
        // to prevent bug where non-validated demandes appear after actions.
        $query->where('statut', 'valide');

        return $query;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $roles = $user->roles()->pluck('name')->toArray();

        // Privileged roles that can access demandes
        // NOTE: "Responsable sortie" is NOT here - it uses mission-based access
        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Valider demande', 'Valide demande', 'Validateur special', 'Autre',
        ];

        // If user has any privileged role, allow access
        if (! empty(array_intersect($roles, $privileged))) {
            return true;
        }

        // "Responsable sortie" alone should allow access (mission-based)
        if (in_array('Responsable sortie', $roles)) {
            return true;
        }

        // If user roles intersect TypeRapport names and they are not privileged, hide access
        $typeNames = \App\Models\TypeRapport::pluck('name')->toArray();
        if (! empty(array_intersect($roles, $typeNames))) {
            return false;
        }

        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $roles = $user->roles()->pluck('name')->toArray();

        // Afficher si rôle "Responsable sortie" (avec ou sans autres rôles)
        if (in_array('Responsable sortie', $roles)) {
            return true;
        }

        // Sinon, afficher si utilisateur a un rôle dans les rôles privilégiés
        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Valider demande', 'Valide demande', 'Validateur special', 'Autre','Simple'
        ];

        return ! empty(array_intersect($roles, $privileged));
    }



}
