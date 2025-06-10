<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeValideResource\Pages;
use App\Filament\Resources\DemandeValideResource\RelationManagers;
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
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\Action;
use Filament\Facades\Filament;



class DemandeValideResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $label = 'Demande valide';
    protected static ?string $pluralLabel = 'Demande valides';
    protected static ?string $slug = 'Demande valide'; // ou un slug unique

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
                        Forms\Components\Select::make('objet_rapport_id')
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
                IconColumn::make('url')  
                ->icon('heroicon-o-arrow-down-tray')
                ->label("Demande")             
                ->url(fn ($record) => $record?->url 
                    ? Storage::disk('public')->url($record?->url) 
                    : null
                )
                ->openUrlInNewTab()
                ->tooltip('Télécharger le fichier')
                ->toggleable(isToggledHiddenByDefault: true),

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
                ),
                Tables\Columns\TextColumn::make('ObjetDemande.nomObjet')
                    ->sortable()
                    ->searchable(),


                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.poste.nomPoste')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('activite.nomActivite')
                    ->numeric()
                    ->searchable(),

                Tables\Columns\TextColumn::make('site.nomSite')
                    ->numeric()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                    // ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListDemandeValides::route('/'),
            'create' => Pages\CreateDemandeValide::route('/create'),
            'view' => Pages\ViewDemandeValide::route('/{record}'),
            'edit' => Pages\EditDemandeValide::route('/{record}/edit'),
        ];
    }
   
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role?->nomRole === 'Simple') {
            $query->where('user_id', auth()->id()); // suppose que tu stockes l'utilisateur qui a créé
        }
        $query->where('statut', 'valide');
        
        return $query;
    }
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin', 'Validateur','ValidateurRapport','Special','Simple','Budget']);
    }
   
   
}
