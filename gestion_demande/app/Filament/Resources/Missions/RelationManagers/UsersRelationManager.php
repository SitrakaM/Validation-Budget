<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Membres de la mission';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('scope')
                ->options([
                    'demande' => 'Demande',
                    'rapport' => 'Rapport',
                    'both' => 'Les deux',
                ])
                ->default('both')
                ->required(),

            Select::make('role_mission')
                ->options([
                    'responsable' => 'Responsable',
                    'member' => 'Membre',
                    'observateur' => 'Observateur',
                ])
                ->default('member')
                ->required(),

            TextInput::make('added_at')
                ->label('Date d\'ajout')
                ->default(now())
                ->hidden(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        $isResponsible = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', 'responsable')
            ->exists() || (!(optional(Auth::user())->roles()->count() === 1 && optional(Auth::user())->roles()->pluck('name')->contains('Simple')));

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Utilisateur'),
                Tables\Columns\TextColumn::make('pivot.scope')->label('Scope'),
                Tables\Columns\TextColumn::make('pivot.role_mission')->label('Rôle'),
                Tables\Columns\TextColumn::make('pivot.added_at')->date(),
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Ajouter un membre')
                    ->visible(fn () => $isResponsible())
                    ->preloadRecordSelect()
                    ->schema([
                        Select::make('recordId')
                            ->label('Utilisateur')
                            ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'Simple'))
                                ->when($this->getOwnerRecord()?->users()->exists(), fn ($q) => $q->whereNotIn('id', $this->getOwnerRecord()->users()->pluck('users.id')->toArray()))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('scope')
                            ->options([
                                'demande' => 'Demande',
                                'rapport' => 'Rapport',
                                'both' => 'Les deux',
                            ])
                            ->default('both')
                            ->required(),

                        Select::make('role_mission')
                            ->options([
                                'responsable' => 'Responsable',
                                'member' => 'Membre',
                                'observateur' => 'Observateur',
                            ])
                            ->default('member')
                            ->required(),

                        TextInput::make('added_at')
                            ->label('Date d\'ajout')
                            ->default(now())
                            ->hidden(),
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make()->visible(fn ($record) => true),
                Actions\EditAction::make()->visible(fn ($record) => (
                    $isResponsible()
                    || (Auth::id() === ($record->id ?? null) && (($record->pivot->role_mission ?? null) === 'member') && (($this->getOwnerRecord()?->statut ?? null) !== 'valide'))
                )),
                Actions\DetachAction::make()->visible(fn ($record) => $isResponsible()),
            ])
            ->bulkActions([
                Actions\DetachBulkAction::make()->visible(fn () => $isResponsible()),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return false;
        }

        if (! ($authUser->roles()->count() === 1 && $authUser->roles()->pluck('name')->contains('Simple'))) {
            return true;
        }

        $userId = $authUser->id;

        $user = $ownerRecord->users()->where('users.id', $userId)->first();

        if (! $user) {
            return false;
        }

        $role = $user->pivot->role_mission ?? null;

        return $role === 'responsable';
    }
}






