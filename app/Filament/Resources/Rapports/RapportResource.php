<?php

namespace App\Filament\Resources\Rapports;

use App\Filament\Resources\Rapports\Pages\ManageRapports;
use App\Models\Rapport;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use App\Filament\Resources\Rapports\RelationManagers\ValidationsRelationManager;

class RapportResource extends Resource
{
    protected static ?string $model = Rapport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Rapport';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fichier'),
                TextInput::make('statut')
                        ->label('Status')
                        ->hidden(),
                Toggle::make('physical')
                    ->required(),
                Select::make('type_rapport_id')
                    ->relationship('typeRapport', 'name')
                    ->required(),
                Select::make('mission_id')
                    ->relationship('mission', 'id')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('statut'),
                IconEntry::make('physical')
                    ->boolean(),
                TextEntry::make('typeRapport.name')
                    ->label('Type rapport'),
                TextEntry::make('mission.id')
                    ->label('Mission'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Rapport')
            ->columns([
                TextColumn::make('statut')
                    ->badge()
                    ->colors([
                        'secondary' => 'non_soumis',
                        'gray' => 'en_attente',
                        'warning' => 'en_cours',
                        'info' => 'correction',
                        'success' => 'valide',
                        'danger' => 'refuse',
                    ])
                    ->searchable(),
                IconColumn::make('physical')
                    ->boolean(),
                TextColumn::make('typeRapport.name')
                    ->searchable(),
                TextColumn::make('mission.id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->visible(fn ($record) => (
                    ($user = Auth::user()) && (
                        (in_array($user->roles()->pluck('name')->toArray()[0] ?? null, ['Simple', 'Responsable sortie']) && count($user->roles()) === 1 && (
                            ($userId = $user->id) && ($record->mission) && ($u = $record->mission->users()->where('users.id', $userId)->first()) && (
                                ($u->pivot->role_mission ?? null) === 'responsable' || in_array($u->pivot->scope ?? null, ['rapport', 'both'])
                            )
                        )) ||
                        (!in_array($user->roles()->pluck('name')->toArray()[0] ?? null, ['Simple', 'Responsable sortie']) || count($user->roles()) > 1) && (
                            $roles = $user->roles()->pluck('name')->toArray() and (
                                !empty(array_intersect($roles, ['Admin','Validateur','ValidateurRapport','Special','Budget','Valider demande','Valide demande','Validateur special','Autre'])) || in_array($record->typeRapport?->name, $roles)
                            )
                        )
                    )
                )),
                    EditAction::make()->visible(fn ($record) => (
                        ($user = Auth::user()) && (
                            // mission responsable can edit
                            (($userId = $user->id) && ($record->mission) && ($u = $record->mission->users()->where('users.id', $userId)->first()) && (($u->pivot->role_mission ?? null) === 'responsable'))
                            || (
                                // owners (report creators) can edit
                                (optional($record->mission) && (optional($record)->user_id ?? null) === $user->id)
                            )
                            || (
                                // mission member with rapport scope can edit
                                (($userId = $user->id) && ($record->mission) && ($u = $record->mission->users()->where('users.id', $userId)->first()) && ((($u->pivot->role_mission ?? null) === 'member') && in_array($u->pivot->scope ?? null, ['rapport','both'])))
                            )
                        )
                    )),
                DeleteAction::make()->visible(fn ($record) => (
                    ($user = Auth::user()) && (
                        (!($user->roles()->count() === 1 && $user->roles()->pluck('name')->contains('Simple')) && (
                            $roles = $user->roles()->pluck('name')->toArray() and (
                                in_array('Admin', $roles) || (
                                    ($userId = $user->id) && ($record->mission) && ($u = $record->mission->users()->where('users.id', $userId)->first()) && (($u->pivot->role_mission ?? null) === 'responsable')
                                )
                            )
                        ))
                    )
                )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => (
                        ($user = Auth::user()) && ($userId = $user->id) && $user->roles()->pluck('name')->contains('Simple') && 
                        // ensure the user is responsable on at least one mission (broad check)
                        \App\Models\Mission::whereHas('users', fn (Builder $q) => $q->where('users.id', $userId)->where('mission_users.role_mission', 'responsable'))->exists()
                    )),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRapports::route('/'),
            'view' => \App\Filament\Resources\Rapports\Pages\ViewRapport::route('/{record}'),
        ];
    }

            // 'index' => Pages\ManageRapports::route('/'),
            // 'view' => Pages\ViewRapport::route('/{record}'),
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

            // Roles that should bypass type-based filtering (administrative/validator/etc.)
            // NOTE: "Responsable sortie" is NOT here - it behaves like a mission-based user
            $privileged = [
                'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                'Valider demande', 'Valide demande', 'Validateur special', 'Autre',
            ];

            // If the user is only a Simple user or only Responsable sortie, keep mission-based restriction
            $simpleOrSortie = (count($roles) === 1 && in_array($roles[0], ['Simple', 'Responsable sortie']));
            
            if ($simpleOrSortie) {
                $query->whereHas('mission.users', function (Builder $q) use ($user) {
                    $q->where('users.id', $user->id)
                      ->where('mission_users.role_mission', '!=', 'observateur')
                      ->where(function (Builder $q2) {
                          $q2->where('mission_users.scope', 'rapport')
                             ->orWhere('mission_users.scope', 'both')
                             ->orWhere('mission_users.role_mission', 'responsable');
                      });
                });
            } else {
                // User has multiple roles
                // If they have a privileged role, show all rapports
                if (!empty(array_intersect($roles, $privileged))) {
                    // Show all rapports - privileged role takes precedence
                } else {
                    // No privileged roles, check for TypeRapport-based roles
                    // If user only has Responsable sortie + type roles, restrict to those types
                    $typeIds = \App\Models\TypeRapport::whereIn('name', $roles)->pluck('id')->toArray();
                    if (!empty($typeIds)) {
                        $query->whereIn('type_rapport_id', $typeIds);
                    }
                }
            }
        }

        return $query;
    }
}
