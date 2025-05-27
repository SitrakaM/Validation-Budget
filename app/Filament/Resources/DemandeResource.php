<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeResource\Pages;
use App\Filament\Resources\DemandeResource\RelationManagers;
use App\Models\Demande;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\HasManyRepeater;

class DemandeResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titre')
                    ->required()
                    ->dehydrateStateUsing(fn (string $state): string => ucwords($state))


                    ->maxLength(255),
               
                Forms\Components\TextInput::make('statut')
                    ->hidden()
                    ->maxLength(255),
        
                Forms\Components\Select::make('objet_demande_id')
                    ->relationship(name: 'ObjetDemande', titleAttribute: 'nomObjet')
                    ->label('Objet')
                    ->searchable()
                    ->preload(), 
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->dehydrated(true),
                    
                Forms\Components\Select::make('activite_id')
                    ->relationship(name: 'Activite', titleAttribute: 'nomActivite')
                    ->label('Activité')
                    ->searchable()
                    ->preload(), 
                Forms\Components\Select::make('site')
                    // ->multiple()
                    ->relationship(titleAttribute: 'nomSite')
                    ->options(
                        function (){
                            return auth()->user()->site()->pluck('nomSite','id');  
                        }
                    )
                    ->preload(),
                FileUpload::make('url')
                    ->downloadable()
                    ->directory('Demande')
                    ->visibility('public')
                    ->preserveFilenames(),
                HasManyRepeater::make('rapport')
                    ->relationship('rapport')
                    ->schema([
                        Forms\Components\TextInput::make('titre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('statut')
                            ->hidden()
                            ->maxLength(255),   
                        Forms\Components\Select::make('role_id')
                            ->relationship(name: 'ObjetRapport', titleAttribute: 'nomObjet')
                            ->label('Objet')
                            ->searchable()
                            ->preload(), 
                      
                        Forms\Components\Hidden::make('demande_id')
                            ->dehydrated(true),
                        Forms\Components\Select::make('site')
                            // ->multiple()
                            ->relationship(titleAttribute: 'nomSite')
                            ->options(
                                function (){
                                    return auth()->user()->site()->pluck('nomSite','id');  
                                }
                            )
                            ->preload(),
                    
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
                    ])
                    
                    ->createItemButtonLabel('Ajouter un rapport'),
              
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('objet_demande_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
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
            'index' => Pages\ListDemandes::route('/'),
            'create' => Pages\CreateDemande::route('/create'),
            'view' => Pages\ViewDemande::route('/{record}'),
            'edit' => Pages\EditDemande::route('/{record}/edit'),
        ];
    }
   
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role?->nomRole === 'Simple') {
            $query->where('user_id', auth()->id()); // suppose que tu stockes l'utilisateur qui a créé
        }
        return $query;
    }

   
   
}
