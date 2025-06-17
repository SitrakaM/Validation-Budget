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




class DemandeVoitureResource extends Resource
{
    protected static ?string $model = Demande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $label = 'Demande voiture';
    protected static ?string $pluralLabel = 'Demande voitures';
    protected static ?string $slug = 'Demande voiture'; // ou un slug unique
    protected static ?string $navigationGroup = 'Voiture';
    protected static ?string $navigationLabel = 'Demande';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titre')
                    ->required()
                    ->dehydrateStateUsing(fn (string $state): string => ucwords($state))
                    ->maxLength(255),
               
                // Forms\Components\TextInput::make('statut')
                //     // ->hidden()
                //     ->visible(fn () => in_array(Auth::user()?->role->nomRole,['Special']))
                //     ->maxLength(255),
        
                Forms\Components\Select::make('objet_demande_id')
                    ->relationship(name: 'ObjetDemande', titleAttribute: 'nomObjet')
                    ->default(3)
                    ->disabled() // on ne veut pas que l'utilisateur le modifie
                    ->label('Objet')
                    ->searchable()
                    ->preload()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('voitureCommentaire')
                    ->maxLength(255)
                    ->visible(
                        fn(Callable $get)=>$get('statut') ==='revision'
                    )
                    ->live(),
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
                    ->live(),
                FileUpload::make('motifVoitureRevision')
                    ->downloadable()
                    ->directory('Revision/Demande')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(
                        fn(Callable $get)=>$get('statut') ==='revision' && empty($get('voitureCommentaire'))
                    )
                    ->visible(
                        fn(Callable $get)=>$get('statut') ==='revision'
                    ),

 
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->dehydrated(true),
               
                FileUpload::make('url')
                    ->downloadable()
                    ->directory('Demande')
                    ->visibility('public')
                    ->preserveFilenames()->getUploadedFileNameForStorageUsing(function ($file) {
                        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension = $file->getClientOriginalExtension();
                        $timestamp = now()->format('Y-m-d_H-i-s');
                
                        return $name . '_' . $timestamp . '.' . $extension;
                    }),
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
                            ->default(3)
                            ->label('Objet')
                            ->disabled()
                            ->searchable()
                            ->preload()
                            ->dehydrated(true),
                                        
                      
                        Forms\Components\Hidden::make('demande_id')
                            ->dehydrated(true),
                        
                        FileUpload::make('url')
                            ->panelLayout('grid')
                            ->multiple()
                            ->downloadable()
                            ->directory('Rapport')
                            ->visibility('public')
                            ->preserveFilenames()->getUploadedFileNameForStorageUsing(function ($file) {
                                $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                $extension = $file->getClientOriginalExtension();
                                $timestamp = now()->format('Y-m-d_H-i-s');
                        
                                return $name . '_' . $timestamp . '.' . $extension;
                            }),
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

                // Tables\Columns\TextColumn::make('statut')
                // ->icon(fn(string $state):string=>match($state){
                //     'en_attente'=>'heroicon-o-clock',
                //     'valide'=>'heroicon-o-check-circle',
                //     'revision'=>'heroicon-o-pencil',
                // })
                // ->color(
                //     fn(string $state):string=>match($state){
                //         'en_attente'=>'gray',
                //         'valide'=>'success',
                //         'revision'=>'info',
                //     }
                // ),
                Tables\Columns\TextColumn::make('statut')
                    ->icon(fn(string $state):string=>match($state){
                        'en_attente'=>'heroicon-o-clock',
                        'valide'=>'heroicon-o-check-circle',
                        'revision'=>'heroicon-o-pencil',
                        'changer'=>'heroicon-o-wrench-screwdriver',                    })
                    ->color(
                        fn(string $state):string=>match($state){
                            'en_attente'=>'gray',
                            'valide'=>'success',
                            'revision'=>'danger',
                            'changer'=>'info',
                        }
                    )
                    ->sortable(),
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
            'index' => Pages\ListDemandeVoitures::route('/'),
            'create' => Pages\CreateDemandeVoiture::route('/create'),
            'view' => Pages\ViewDemandeVoiture::route('/{record}'),
            'edit' => Pages\EditDemandeVoiture::route('/{record}/edit'),
        ];
    }
   
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role?->nomRole === 'Simple') {
            $query->where('user_id', auth()->id()); // suppose que tu stockes l'utilisateur qui a créé
        }
        $query->where('objet_demande_id', 3);
        // $query->whereHas('ObjetDemande', function ($q) {
        //     $q->where('nomObjet', 'Voiture');
        // });

        return $query;
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin','Special','Budget']);
    }

   
   
}
