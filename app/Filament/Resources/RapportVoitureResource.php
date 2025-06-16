<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RapportVoitureResource\Pages;
use App\Filament\Resources\RapportVoitureResource\RelationManagers;
use App\Models\Rapport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\TextInputColumn;
use App\Models\Post;
use Filament\Tables\Actions\Action;
use App\Models\Demande;
use Filament\Facades\Filament;


class RapportVoitureResource extends Resource
{
    protected static ?string $model = Rapport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $label = 'Rapport voiture';
    protected static ?string $pluralLabel = 'Rapport voitures';
    protected static ?string $slug = 'Rapport voiture'; // ou un slug unique

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titre')
                    ->required()
                    ->maxLength(255),
                // Forms\Components\TextInput::make('statut')
                //     ->maxLength(255),
                Forms\Components\TextInput::make('statut')
                ->hidden()
                ->maxLength(255),
                Forms\Components\Select::make('objet_rapport_id')
                    ->relationship(name: 'ObjetRapport', titleAttribute: 'nomObjet')
                    ->label('Objet')
                    ->searchable()
                    ->preload(), 

                Forms\Components\Hidden::make('demande_id')
                    ->dehydrated(true),
               
                Forms\Components\Select::make('activite_id')
                    ->relationship(name: 'Activite', titleAttribute: 'nomActivite')
                    ->label('Activité')
                    ->searchable()
                    ->preload(), 
                FileUpload::make('url')
                    ->panelLayout('grid')
                    ->multiple()
                    ->downloadable()
                    ->directory('Rapport')
                    ->visibility('public')
                    ->preserveFilenames(),
              
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titre')
                    ->searchable(),
                // ToggleColumn::make('statut')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('statut')
                ->icon(fn(string $state):string=>match($state){
                    'en_attente'=>'heroicon-o-clock',
                    'valide'=>'heroicon-o-check-circle',
                    'revision'=>'heroicon-o-pencil',
                })
                ->color(
                    fn(string $state):string=>match($state){
                        'en_attente'=>'gray',
                        'valide'=>'success',
                        'revision'=>'info',
                    }
                )
                    ->searchable(),
               
                Tables\Columns\TextColumn::make('objet_rapport_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('demande_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('activite_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('delete')
                    ->requiresConfirmation()
                    ->action(fn (Post $record) => $record->delete())
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRapportVoitures::route('/'),
            'create' => Pages\CreateRapportVoiture::route('/create'),
            'view' => Pages\ViewRapportVoiture::route('/{record}'),
            'edit' => Pages\EditRapportVoiture::route('/{record}/edit'),
        ];
    }


    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role?->nomRole === 'Simple') {
            $query->whereHas('demande', function ($q) {
                $q->where('user_id', auth()->id());
            });       
        }
        $query->where('objet_rapport_id', 3);

        // $query->where('statut', 'valide');


        return $query;
    }
    // public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery();

    //     if (auth()->user()->role?->nomRole === 'Simple') {
    //         $query->where('user_id', auth()->id()); // suppose que tu stockes l'utilisateur qui a créé
    //     }
    //     $query->where('objet_demande_id', 3);

    //     return $query;
    // }
    
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin','Special','Budget']);
    }
}
