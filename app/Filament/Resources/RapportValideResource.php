<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RapportValideResource\Pages;
use App\Filament\Resources\RapportValideResource\RelationManagers;
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
use Filament\Tables\Actions\Action;
use App\Models\Demande;
use Filament\Facades\Filament;
use App\Models\Poste;
use Illuminate\Support\Facades\Auth;



use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;



class RapportValideResource extends Resource
{
    protected static ?string $model = Rapport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    // protected static ?string $label = 'Rapport valide';
    protected static ?string $pluralLabel = 'Rapport valides';
    protected static ?string $slug = 'Rapport valide'; // ou un slug unique
    protected static ?string $navigationGroup = 'Valide';
    protected static ?string $navigationLabel = 'Rapport';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([

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

            ])->columns(3),

                FileUpload::make('url')
                    ->label('Fichier attacher')
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
                    })
                    ->columnSpanFull()
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
                )
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('demande.user.name')
                // ->relationship(name: 'Demande', titleAttribute: 'nomObjet')
                    ->label('Identifiant')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']))
                    ->searchable(),
                Tables\Columns\TextColumn::make('demande.user.poste.nomPoste')
                    ->visible(fn () => !in_array(Auth::user()?->role->nomRole,['Simple']))
                    ->toggleable(isToggledHiddenByDefault: true),

               
                Tables\Columns\TextColumn::make('ObjetRapport.nomObjet')
                    ->label('Objet')
                    ->sortable(),     

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
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
            ])
            
            ->filters([
                
                SelectFilter::make('ObjetRapport')
                    ->label('Objet')
                    ->relationship('ObjetRapport', 'nomObjet')
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
                // Action::make('delete')
                //     ->requiresConfirmation()
                //     ->action(fn (Post $record) => $record->delete())
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
            'index' => Pages\ListRapportValides::route('/'),
            // 'create' => Pages\CreateRapportValide::route('/create'),
            // 'view' => Pages\ViewRapportValide::route('/{record}'),
            // 'edit' => Pages\EditRapportValide::route('/{record}/edit'),
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
            return static::getModel()::whereHas('demande', function ($q) {
                $q->where('user_id', auth()->id());
            })->where('statut', 'valide')->count() ;       
        }else{
            return static::getModel()::where('statut', 'valide')->count();    
        }
    }
}
