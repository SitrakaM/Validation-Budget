<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValidationDemandeResource\Pages;
use App\Filament\Resources\ValidationDemandeResource\RelationManagers;
use App\Models\ValidationDemande;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\DemandeResource;
use Filament\Forms\Components\HasManyRepeater;


class ValidationDemandeResource extends Resource
{
    protected static ?string $model = ValidationDemande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $navigationGroup = 'Validation';
    protected static ?string $navigationLabel = 'Demande';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
             
                


      
                Forms\Components\Section::make('DEMANDE')
                        ->relationship('demande')
                        ->schema([
            
            
                            Forms\Components\Select::make('objet_demande_id')
                            ->relationship(name: 'ObjetDemande', titleAttribute: 'nomObjet')
                            ->label('Objet'), 
                        Forms\Components\TextInput::make('titre'),
                        Forms\Components\Select::make('site')
                            ->relationship(titleAttribute: 'nomSite'),
                        Forms\Components\Select::make('activite_id')
                            ->relationship(name: 'Activite', titleAttribute: 'nomActivite'), 
            
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
                            
                HasManyRepeater::make('rapport')
                ->label('RAPPORT ATTACHER')
                ->relationship('rapport')
                ->schema([
                    Forms\Components\Select::make('objet_rapport_id')
                        ->relationship(name: 'ObjetRapport', titleAttribute: 'nomObjet')
                        ->label('Objet'), 
                    Forms\Components\TextInput::make('titre'),
                        Forms\Components\Select::make('site')
                        // ->multiple()
                        ->relationship(titleAttribute: 'nomSite'),
                
                    Forms\Components\Select::make('activite_id')
                        ->relationship(name: 'Activite', titleAttribute: 'nomActivite')
                        ->label('Activité'), 
                    FileUpload::make('url')
                        ->label('Joindre des fichiers')
                        ->panelLayout('grid')
                        ->multiple()
                        ->downloadable()
                        ->directory('Rapport')
                        ->visibility('public')
                        ->preserveFilenames()
                        ->required()
                        ->columnSpanFull(),
                    // Forms\Components\TextInput::make('statut')
                    //     ->hidden()
                    //     ->maxLength(255),   
                  
                    
                ])
                ->columns(2)
                ->columnSpanFull()
                ->collapsible(),

            
                        ])
                        ->disabled()
                        ->columns(2)
                        ->collapsible(),

                Forms\Components\Section::make('INTERVENTION')
                        ->schema([
                            
                        Forms\Components\ToggleButtons::make('estValid')
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
                            ->live(),
                        FileUpload::make('motifRetour')
                            ->panelLayout('grid')
                            ->multiple()
                            ->downloadable()
                            ->directory('Revision/Demande')
                            ->visibility('public')
                            ->preserveFilenames()->getUploadedFileNameForStorageUsing(function ($file) {
                                $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                $extension = $file->getClientOriginalExtension();
                                $timestamp = now()->format('Y-m-d_H-i-s');
                        
                                return $name . '_' . $timestamp . '.' . $extension;
                            })
                            ->required(
                                fn(Callable $get)=>$get('estValid') ==='revision' && empty($get('commentaire'))
                            )
                            ->visible(
                                fn(Callable $get)=>$get('estValid') ==='revision'
                            ),
                            Forms\Components\Textarea::make('commentaire')
                            ->maxLength(255)
                            ->visible(
                                fn(Callable $get)=>$get('estValid') ==='revision'
                            )
                            ->columnSpanFull()
                            ->live(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->hidden()
                            ->required(),
                        Forms\Components\Select::make('demande_id')
                            ->relationship('demande', 'id')
                            ->hidden()
                            ->required(),
                        
                        ])                        
                        ->columns(2)
                        ,
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('commentaire')
                    ->searchable(),
                // Tables\Columns\IconColumn::make('estValid')
                //     ->boolean(),
                Tables\Columns\TextColumn::make('estValid')
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
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('demande.id')
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
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make('intervenir') // <-- Nouveau nom de l'action
                ->label('Intervenir')                    // <-- Texte affiché dans le bouton
                ->icon('heroicon-o-wrench') 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListValidationDemandes::route('/'),
            // 'create' => Pages\CreateValidationDemande::route('/create'),
            'view' => Pages\ViewValidationDemande::route('/{record}'),
            'edit' => Pages\EditValidationDemande::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->where('user_id', auth()->id()); // suppose que tu stockes l'utilisateur qui a créé
        return $query;
    }
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin', 'Validateur','ValidateurRapport']);
    }


}

