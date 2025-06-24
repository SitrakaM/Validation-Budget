<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeVoitureResource\Pages;
use App\Filament\Resources\DemandeVoitureResource\RelationManagers;
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


use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;




class DemandeVoitureResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    // protected static ?string $label = 'Demande voiture';
    protected static ?string $pluralLabel = 'Demande voitures';
    protected static ?string $slug = 'Demande voiture'; // ou un slug unique
    protected static ?string $navigationGroup = 'Voiture';
    protected static ?string $navigationLabel = 'Demande';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            Forms\Components\Section::make('DEMANDE')
            ->schema([

            Forms\Components\Select::make('objet_demande_id')
                ->relationship(name: 'ObjetDemande', titleAttribute: 'nomObjet')
                ->default(3)
                ->disabled() // on ne veut pas que l'utilisateur le modifie
                ->label('Objet')
                ->searchable()
                ->preload()
                ->dehydrated(true),
          
            Forms\Components\TextInput::make('titre')
                ->required()
                ->dehydrateStateUsing(fn (string $state): string => ucwords($state))
                ->maxLength(255),
         

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
            ->disabled(fn () => in_array(Auth::user()?->role->nomRole,['Special','Simple','Validateur','ValidateurRapport']))
            ->columns(2),
               
            
                HasManyRepeater::make('rapport')
                ->label('RAPPORT ATTACHER')
                ->relationship('rapport')
                ->schema([
                    Forms\Components\Select::make('objet_rapport_id')
                        ->relationship(name: 'ObjetRapport', titleAttribute: 'nomObjet')
                        ->default(3)
                        ->disabled() // on ne veut pas que l'utilisateur le modifie
                        ->label('Objet')
                        ->searchable()
                        ->preload()
                        ->dehydrated(true),
                
                    Forms\Components\TextInput::make('titre')
                        ->required()
                        ->maxLength(255),
  
           
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
                ->disabled(fn () => in_array(Auth::user()?->role->nomRole,['Special','Simple','Validateur','ValidateurRapport']))
                ->columns(2)
                ->columnSpanFull()
                ->createItemButtonLabel('Ajouter un rapport')                    
                ->collapsible(),

          
                
            Forms\Components\Section::make('VALIDATION SPECIAL')
                ->schema([


                    Forms\Components\ToggleButtons::make('statut')
                    ->options([
                        'valide'=>'valide',
                        'revision'=>'revision'
                    ])
                    ->icons([
                        'valide'=>'heroicon-o-check-circle',
                        'revision'=>'heroicon-o-pencil',
                    ])->colors(
                        [                            
                            'valide'=>'success',
                            'revision'=>'danger',
                        ]
                    )
                    ->inline()
                    ->required()
                    ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Special']))
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('voitureCommentaire')
                    ->label('Commentaire')
                    ->maxLength(255)
                    ->visible(
                        fn(Callable $get)=>$get('statut') ==='revision'
                    )
                    ->live()
                    ->columnSpanFull(),
                FileUpload::make('motifVoitureRevision')
                    ->label('Fichier commenté')
                    ->downloadable()
                    ->directory('Revision/Demande')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(
                        fn(Callable $get)=>$get('statut') ==='revision' && empty($get('voitureCommentaire'))
                    )
                    ->visible(
                        fn(Callable $get)=>$get('statut') ==='revision'
                    )
                    ->columnSpanFull(),
                
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
                    'changer'=>'heroicon-o-wrench-screwdriver',

                })
                ->color(
                    fn(string $state):string=>match($state){
                        'en_attente'=>'gray',
                        'valide'=>'success',
                        'revision'=>'danger',
                        'changer'=>'info',

                    }
                )
                ,
                Tables\Columns\TextColumn::make('ObjetDemande.nomObjet')
                    ->label('Objet')
                    ->sortable(),


                Tables\Columns\TextColumn::make('user.name')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple','Budget']))
                    ->label('Identifiant')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.poste.nomPoste')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple','Budget']))
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
                })->columnSpan(3)->columns(2)

        ], layout: FiltersLayout::AboveContent)->filtersFormColumns(3)

        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make()
                ->label(fn () => in_array(Auth::user()?->role->nomRole,['Simple']) ? 'Modifier' : 'Intervenir')
                ->icon(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']) ? 'heroicon-o-wrench' : 'heroicon-o-pencil-square') 
                ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Budget','Special']))
                ,
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Budget'])),
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
            'index' => Pages\ListDemandeVoitures::route('/'),
            // 'create' => Pages\CreateDemande::route('/create'),
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
        $query?->whereIn('statut', ['en_attente','revision','changer']);

        $query->where('objet_demande_id', 3);
        // $query->whereHas('ObjetDemande', function ($q) {
        //     $q->where('nomObjet', 'Voiture');
        // });

        return $query;
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin','Special','Budget','ValidateurRapport']);
    }

    public static function getNavigationBadge(): ?string
    {
            return static::getModel()::where('objet_demande_id',3)->whereNot('statut', 'valide')->count();    
       
    }
   

   
}
