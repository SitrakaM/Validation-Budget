<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValidationRapportSuivieResource\Pages;
use App\Filament\Resources\ValidationRapportSuivieResource\RelationManagers;
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


class ValidationRapportSuivieResource extends Resource
{
    protected static ?string $model = ValidationRapport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    // protected static ?string $label = 'Suivie rapport';
    protected static ?string $pluralLabel = 'Suivie rapports';
    protected static ?string $slug = 'Suivie rapport'; // ou un slug unique
    protected static ?string $navigationGroup = 'Suivie Validation';
    protected static ?string $navigationLabel = 'Rapport';

    public static function form(Form $form): Form
    {
        return $form


            ->schema([
                Forms\Components\Grid::make(2)->schema([
                  
   
                    
                Forms\Components\Grid::make(2)
                ->relationship('rapport')
                ->schema([
    
    
                Forms\Components\Select::make('objet_rapport_id')
                    ->relationship(name: 'ObjetRapport', titleAttribute: 'nomObjet')
                    ->label('Objet'), 
                Forms\Components\TextInput::make('titre'),
                Forms\Components\Select::make('site')
                    ->relationship(titleAttribute: 'nomSite'),
                Forms\Components\Select::make('activite_id')
                    ->relationship(name: 'Activite', titleAttribute: 'nomActivite'), 
                
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->dehydrated(true),
                FileUpload::make('url')
                                ->label('Fichier attacher')
                                ->downloadable()
                                ->directory('Rapport')
                                ->visibility('public')
                                ->preserveFilenames()
                                ->columnSpanFull()
                                ->required()

                ])
                ->columns(4),


                    Forms\Components\Select::make('user_id')
                        ->label('Responsable validation')
                        ->relationship('user', 'name')
                        ->required(),
                    
                    Forms\Components\ToggleButtons::make('estValid')
                        ->label('Status')
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
                    ])->columns(3),

                    Forms\Components\Grid::make(2)->schema([
//
                    ])->columns(3),

                    Forms\Components\Textarea::make('commentaire')
                    ->maxLength(255)
                    ->visible(
                        fn(Callable $get)=>$get('estValid') ==='revision'
                    )
                    ->live()
                    ->columnSpanFull()
                    ,
                FileUpload::make('motifRetour')
                    ->label('Fichier commenté')
                    ->panelLayout('grid')
                    ->multiple()
                    ->downloadable()
                    ->directory('Revision/Rapport')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(
                        fn(Callable $get)=>$get('estValid') ==='revision' && empty($get('commentaire'))
                    )
                    ->visible(
                        fn(Callable $get)=>$get('estValid') ==='revision'
                    )
                    ->columnSpanFull()
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable validation')
                    ->searchable()
                    ->numeric()
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
                Tables\Actions\ViewAction::make()->label('Voir commentaire'),
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListValidationRapportSuivies::route('/'),
            // 'create' => Pages\CreateValidationRapportSuivie::route('/create'),
            // 'view' => Pages\ViewValidationRapportSuivie::route('/{record}'),
            // 'edit' => Pages\EditValidationRapportSuivie::route('/{record}/edit'),
        ];
    }
  
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->user()->role?->nomRole === 'Simple') {
            $query->whereHas('rapport', function ($q) {
                $q->whereHas('demande', function ($r) {
                    $r->where('user_id', auth()->id());
                });
            });       
         }
        return $query;
    }
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin','ValidateurRapport','Validateur','Simple']);
    }
}
