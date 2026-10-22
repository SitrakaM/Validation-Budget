<?php

namespace App\Filament\Resources\Missions;

use App\Filament\Resources\Missions\Pages\CreateMission;
use App\Filament\Resources\Missions\Pages\EditMission;
use App\Filament\Resources\Missions\Pages\ListMissions;
use App\Filament\Resources\Missions\Pages\ViewMission;
use App\Filament\Resources\Missions\Schemas\MissionForm;
use App\Filament\Resources\Missions\Schemas\MissionInfolist;
use App\Filament\Resources\Missions\Tables\MissionsTable;
use App\Models\Mission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\Missions\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Missions\RelationManagers\DemandesRelationManager;
use App\Filament\Resources\Missions\RelationManagers\RapportsRelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


class MissionResource extends Resource
{
    protected static ?string $model = Mission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Mission';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $roles = $user->roles()->pluck('name')->toArray();

        // Exclure les utilisateurs avec JUSTE "Responsable sortie"
        if (count($roles) === 1 && $roles[0] === 'Responsable sortie') {
            return false;
        }

        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return MissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
            DemandesRelationManager::class,
            RapportsRelationManager::class,

            ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMissions::route('/'),
            'create' => CreateMission::route('/create'),
            'view' => ViewMission::route('/{record}'),
            'edit' => EditMission::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        $routeName = request()->route()?->getName() ?? '';
        if ($user && str_ends_with($routeName, '.index')) {
            $roles = $user->roles()->pluck('name')->toArray();
            if (count($roles) === 1 && $roles[0] === 'Simple') {
                // Simple users only see missions they belong to (only on index/listing)
                $query->whereHas('users', function (Builder $q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            }
        }

        return $query;
    }
}
