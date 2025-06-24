<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeValideResource\Pages;
use App\Filament\Resources\DemandeValideResource\RelationManagers;
use App\Models\Demande;
use App\Models\Poste;
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
use Illuminate\Support\Facades\Auth;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;



class DemandeValideResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    // protected static ?string $label = 'Demande valide';
    protected static ?string $pluralLabel = 'Demande valides';
    protected static ?string $slug = 'Demande valide'; // ou un slug unique
    protected static ?string $navigationGroup = 'Valide';
    protected static ?string $navigationLabel = 'Demande';

    public static function form(Form $form): Form
    {
       
        return $form
        ->schema([




        Forms\Components\Section::make('DEMANDE')
        ->schema([


            Forms\Components\Select::make('objet_demande_id')
            ->relationship(name: 'ObjetDemande', titleAttribute: 'nomObjet')
            ->label('Objet')
            ->options(function () {
                $user = auth()->user();
        
                // Rôles autorisés à voir "Voiture"
                $canSeeVoiture = in_array(strtolower($user->role?->nomRole), ['Admin', 'Budget']);
        
                return \App\Models\ObjetDemande::query()
                    ->when(!$canSeeVoiture, function ($query) {
                        $query->where('nomObjet', '!=', 'Voiture');
                    })
                    ->pluck('nomObjet', 'id');
            })
            ->default(1)
            ->searchable()
            ->required()
            ->preload(), 
        Forms\Components\TextInput::make('titre')
            ->required()
            ->dehydrateStateUsing(fn (string $state): string => ucwords($state))
            ->maxLength(255),
        Forms\Components\Select::make('site')
            ->relationship(titleAttribute: 'nomSite')
            ->options(
                function (){
                    return auth()->user()->site()->pluck('nomSite','id');  
                }
            )
            ->required()
            ->preload(),
        Forms\Components\Select::make('activite_id')
            ->relationship(name: 'Activite', titleAttribute: 'nomActivite')
            ->label('Activité')
            ->default(1)
            ->searchable()
            ->preload(), 

        FileUpload::make('url')
            ->label('Fichier attacher')
            ->downloadable()
            ->directory('Demande')
            ->visibility('public')
            ->preserveFilenames()
            ->columnSpanFull()
            ->required()
            ,
        
        Forms\Components\Hidden::make('user_id')
            ->default(auth()->id())
            ->dehydrated(true),
            

        ])
        ->disabled(fn () => in_array(Auth::user()?->role->nomRole,['Special','Budget','Validateur','ValidateurRapport']))
        ->columns(2),
           
        
            HasManyRepeater::make('rapport')
            ->label('RAPPORT')
            ->relationship('rapport')
            ->schema([
                Forms\Components\Select::make('objet_rapport_id')
                    ->relationship(name: 'ObjetRapport', titleAttribute: 'nomObjet')
                    ->label('Objet')
                    ->options(function () {
                        $user = auth()->user();
                        $canSeeVoiture = in_array(strtolower($user->role?->nomRole), ['Admin', 'Budget']);
                        return \App\Models\ObjetRapport::query()
                            ->when(!$canSeeVoiture, function ($query) {
                                $query->where('nomObjet', '!=', 'Voiture');
                            })
                            ->pluck('nomObjet', 'id');
                    })
                    ->default(1)
                    ->required()
                    ->searchable()
                    ->preload(), 
                Forms\Components\TextInput::make('titre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('site')
                    // ->multiple()
                    ->relationship(titleAttribute: 'nomSite')
                    ->options(
                        function (){
                            return auth()->user()->site()->pluck('nomSite','id');  
                        }
                    )                        
                    ->required()
                    ->preload(),
            
                Forms\Components\Select::make('activite_id')
                    ->relationship(name: 'Activite', titleAttribute: 'nomActivite')
                    ->label('Activité')
                    ->searchable()
                    ->preload(), 
                FileUpload::make('url')
                    ->label('Fichier attacher')
                    ->panelLayout('grid')
                    ->multiple()
                    ->downloadable()
                    ->directory('Rapport')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('statut')
                    ->hidden()
                    ->maxLength(255),   
               
              
                Forms\Components\Hidden::make('demande_id')
                    ->dehydrated(true),
                
                
            ])
            ->disabled(fn () => in_array(Auth::user()?->role->nomRole,['Special','Budget','Validateur','ValidateurRapport']))
            ->columns(2)
            ->columnSpanFull()
            ->createItemButtonLabel('Ajouter un rapport'),
      
            
        Forms\Components\Section::make('VALIDATION SPECIAL')
            ->schema([

            FileUpload::make('motifSpecial')
                ->downloadable()
                ->directory('Revision/Demande')
                ->visibility('public')
                ->preserveFilenames(),
            
            ])
            ->visible(
                fn(Callable $get)=>!empty($get('motifSpecial'))
            ),
            

            
            
            
    ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ToggleColumn::make('sortie')
                    ->sortable()
                    ->disabled(function ($record){
                        if(Auth::user()?->role->nomRole != 'Budget'){
                            return true;
                        }else{
                            if($record->sortie === true){
                                return true;
                            }else{
                                return false;
                            }
                        }
                    } ),
                    

                Tables\Columns\TextColumn::make('titre')
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
                ),
                Tables\Columns\TextColumn::make('ObjetDemande.nomObjet')
                    ->label('Objet')
                    ->sortable(),



                Tables\Columns\TextColumn::make('user.name')
                    ->label('Identifiant')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']))
                    ->searchable(),
                Tables\Columns\IconColumn::make('motifSpecial')
                    ->boolean()
                    ->sortable()
                    ->icon('heroicon-o-paper-clip'),
                    // ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Budget'])),
                

                Tables\Columns\TextColumn::make('user.poste.nomPoste')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']))
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('activite.nomActivite')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('site.nomSite')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
                    // ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                
                SelectFilter::make('ObjetDemande')
                    ->label('Objet')
                    ->relationship('ObjetDemande', 'nomObjet')
                    ->searchable()
                    ->preload()
                    ->visible(),
                SelectFilter::make('Site')
                    ->label('Site')
                    ->relationship('Site', 'nomSite')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('Activite')
                    ->label('Activité')
                    ->relationship('Activite', 'nomActivite')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('Poste')
                    ->options(Poste::all()->pluck('nomPoste', 'id'))
                    ->label('Poste')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple'])), 
                Filter::make('created_at')
                
                    ->form([
                        DatePicker::make('created_from')->label('Créer en')
                        ,
                        DatePicker::make('created_until')->label('Jusqu\'à')
                        ,
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
                    })->columnSpan(2)->columns(2)

            ], layout: FiltersLayout::AboveContent)->filtersFormColumns(3)

            ->actions([
                Tables\Actions\ViewAction::make()->label('Afficher'),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

 

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDemandeValides::route('/'),
            // 'create' => Pages\CreateDemandeValide::route('/create'),
            // 'view' => Pages\ViewDemandeValide::route('/{record}'),
            // 'edit' => Pages\EditDemandeValide::route('/{record}/edit'),
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
    public static function getNavigationBadge(): ?string
        {
            if (auth()->user()->role?->nomRole === 'Simple') {
                return static::getModel()::where('user_id', auth()->id())->where('statut', 'valide')->count();    
            }else{
                return static::getModel()::where('statut', 'valide')->count();    
            }
        }
        

   
}
