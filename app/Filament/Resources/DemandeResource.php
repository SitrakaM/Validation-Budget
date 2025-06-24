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
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use App\Models\Poste;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;




class DemandeResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $pluralLabel = 'Demande';
    // protected static ?string $navigationGroup = 'Nouvelle';
    protected static ?string $navigationLabel = 'Nouvelle Demande';

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
                        $allSite= auth()->user()->site()->pluck('nomSite','id');
                        if(($allSite->isNotEmpty()) && in_array(Auth::user()?->role->nomRole,['Simple'])){
                            return auth()->user()->site()->pluck('nomSite','id'); 
                        }else{
                          
                            return \App\Models\Site::query()
                            ->pluck('nomSite', 'id');
                        }
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
                ->label('Joindre une fichier')
                ->downloadable()
                ->directory('Demande')
                ->visibility('public')
                ->preserveFilenames()
                ->columnSpanFull()
                ->required()->getUploadedFileNameForStorageUsing(function ($file) {
                    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $timestamp = now()->format('Y-m-d_H-i-s');
            
                    return $name . '_' . $timestamp . '.' . $extension;
                }),
            
            Forms\Components\Hidden::make('user_id')
                ->default(auth()->id())
                ->dehydrated(true),
                

            ])
            ->disabled(fn () => in_array(Auth::user()?->role->nomRole,['Special','Budget','Validateur','ValidateurRapport']))
            ->columns(2),
               
            
                HasManyRepeater::make('rapport')
                ->label(fn () => in_array(Auth::user()?->role->nomRole,['Simple']) ? 'ATTACHEMENT DES RAPPORTS' : 'RAPPORT ATTACHER')
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
                                $allSite= auth()->user()->site()->pluck('nomSite','id');
                            if(($allSite->isNotEmpty()) && in_array(Auth::user()?->role->nomRole,['Simple'])){
                            return auth()->user()->site()->pluck('nomSite','id'); 
                                }else{
                                  
                                    return \App\Models\Site::query()
                                    ->pluck('nomSite', 'id');
                                }
                            }
                        )                        
                        ->required()
                        ->preload(),
                
                    Forms\Components\Select::make('activite_id')
                        ->relationship(name: 'Activite', titleAttribute: 'nomActivite')
                        ->label('Activité')
                        ->searchable()
                        ->default(1)
                        ->preload(), 
                    FileUpload::make('url')
                        ->label('Joindre des fichiers')
                        ->panelLayout('grid')
                        ->multiple()
                        ->downloadable()
                        ->directory('Rapport')
                        ->visibility('public')
                        ->preserveFilenames()
                        ->required()->getUploadedFileNameForStorageUsing(function ($file) {
                            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                            $extension = $file->getClientOriginalExtension();
                            $timestamp = now()->format('Y-m-d_H-i-s');
                    
                            return $name . '_' . $timestamp . '.' . $extension;
                        })
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
                ->createItemButtonLabel('Ajouter un rapport')                    
                ->collapsible(),

          
                
            Forms\Components\Section::make('VALIDATION SPECIAL')
                ->schema([


                    Forms\Components\ToggleButtons::make('statut')
                    ->options([
                        'valide'=>'valide',
                    ])
                    ->icons([
                        'valide'=>'heroicon-o-check-circle',
                    ])->colors(
                        [                            
                            'valide'=>'success',
                        ]
                    )
                    ->inline()
                    ->required()
                    ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Special']))
                    ->live()
                    ->columnSpanFull(),
                FileUpload::make('motifSpecial')
                    ->downloadable()
                    ->directory('Revision/Demande')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(
                        fn(Callable $get)=>$get('statut') ==='valide'
                    )
                    ->visible(
                        fn(Callable $get)=>$get('statut') ==='valide'
                    ),
                
                ])->visible(fn () => in_array(Auth::user()?->role->nomRole,['Special']))
                ,

                
                
                
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titre')
                    ->searchable(),
           
                Tables\Columns\TextColumn::make('statut')
                ->label('Status')
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
                    ->searchable()
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple'])),

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label(fn () => in_array(Auth::user()?->role->nomRole,['Simple']) ? 'Modifier' : 'Intervenir')
                    ->icon(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']) ? 'heroicon-o-wrench' : 'heroicon-o-pencil-square') 
                    ,
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Simple'])),
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
            // 'view' => Pages\ViewDemande::route('/{record}'),
            // 'edit' => Pages\EditDemande::route('/{record}/edit'),
        ];
    }
   
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role?->nomRole === 'Simple') {
            $query->where('user_id', auth()->id()); // suppose que tu stockes l'utilisateur qui a créé
        }

        $query?->where('statut', 'en_attente');
        $query->whereNot('objet_demande_id', 3);



        return $query;
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin','Special','Simple']);
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()->role?->nomRole === 'Simple' || auth()->user()->role?->nomRole === 'Special') {
            return static::getModel()::where('user_id', auth()->id())->whereNot('statut', 'valide')->count();    
        }else{
            return static::getModel()::whereNot('statut', 'valide')->count();    
        }
    }
    

   
}
