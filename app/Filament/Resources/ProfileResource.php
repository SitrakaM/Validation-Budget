<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages\EditProfile;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Mon Profil';

    protected static ?string $pluralModelLabel = 'Profil';

    protected static ?string $modelLabel = 'Profil Utilisateur';

    protected static ?int $navigationSort = 999;

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) Auth::user();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom complet')
                    ->required()
                    ->minLength(2)
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Adresse E-mail')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8)
                    ->hint('Laissez vide pour garder le mot de passe actuel'),
                TextInput::make('password_confirmation')
                    ->label('Confirmation du mot de passe')
                    ->password()
                    ->same('password')
                    ->dehydrated(false)
                    ->visible(fn ($get) => filled($get('password')))
                    ->hint('Confirmez votre nouveau mot de passe'),
                Select::make('poste_id')
                    ->label('Poste')
                    ->relationship('poste', 'name')
                    ->disabled()
                    ->placeholder('Aucun poste assigné'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nom complet'),
                TextEntry::make('email')
                    ->label('Adresse E-mail'),
                TextEntry::make('poste.name')
                    ->label('Poste')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Compte créé le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Dernière mise à jour')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => EditProfile::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('id', Auth::id());
    }
}
