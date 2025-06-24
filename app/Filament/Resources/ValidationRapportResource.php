<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValidationRapportResource\Pages;
use App\Filament\Resources\ValidationRapportResource\RelationManagers;
use App\Models\ValidationRapport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Facades\Filament;

use Illuminate\Support\Facades\Auth;
use App\Models\Poste;



use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;


class ValidationRapportResource extends Resource
{
    protected static ?string $model = ValidationRapport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $navigationGroup = 'Validation';
    protected static ?string $navigationLabel = 'Rapport à Valider';
    public static function form(Form $form): Form
    {
        return $form
        
            ->schema([
             
                


      
                Forms\Components\Grid::make()
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
                        ->label('Fichier attacher')
                        ->panelLayout('grid')
                        ->multiple()
                        ->downloadable()
                        ->directory('Rapport')
                        ->visibility('public')
                        ->preserveFilenames()
                        ->required()
                        ->columnSpanFull(),
                   
                    
                ])
                ->disabled()
                ->columns(4)
                ->columnSpanFull(),

            
                    

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
                            
                        Forms\Components\Textarea::make('commentaire')
                            ->maxLength(255)
                            ->visible(
                                fn(Callable $get)=>$get('estValid') ==='revision'
                            )
                            ->columnSpanFull()
                            ->live(),
                        
                        FileUpload::make('motifRetour')
                            ->panelLayout('grid')
                            ->multiple()
                            ->downloadable()
                            ->directory('Revision/Rapport')
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
                            )
                            ->columnSpanFull()
                            ,
                       
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->hidden()
                            ->required(),
                        Forms\Components\Select::make('rapport_id')
                            ->relationship('rapport', 'id')
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
                Tables\Columns\TextColumn::make('rapport.titre')
                    ->label('Titre')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('rapport.ObjetRapport.nomObjet')
                    ->label('Objet')
                    ->sortable(),
          
                Tables\Columns\TextColumn::make('commentaire')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('estValid')
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
                    ->sortable(),


                Tables\Columns\TextColumn::make('rapport.demande.user.name')
                    ->label('Identifiant')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']))
                    ->searchable(),

                Tables\Columns\TextColumn::make('rapport.demande.user.poste.nomPoste')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']))
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('rapport.activite.nomActivite')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('rapport.site.nomSite')
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
                
                SelectFilter::make('ObjetRapport')
                    ->label('Objet')
                    ->relationship('rapport.ObjetRapport', 'nomObjet')
                    ->searchable()
                    ->preload()
                    ->visible(),
                SelectFilter::make('Site')
                    ->label('Site')
                    ->relationship('rapport.Site', 'nomSite')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('Activite')
                    ->label('Activité')
                    ->relationship('rapport.Activite', 'nomActivite')
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
                Tables\Actions\EditAction::make() // <-- Nouveau nom de l'action
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
            'index' => Pages\ListValidationRapports::route('/'),
            // 'create' => Pages\CreateValidationRapport::route('/create'),
            // 'view' => Pages\ViewValidationRapport::route('/{record}'),
            // 'edit' => Pages\EditValidationRapport::route('/{record}/edit'),
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

        return in_array($user->role?->nomRole, ['Admin','ValidateurRapport']);
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())->whereNot('estValid', 'valide')->count();
    }
    

}
