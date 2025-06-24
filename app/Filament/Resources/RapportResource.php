<?php

namespace App\Filament\Resources;
use Illuminate\Support\Str;

use App\Filament\Resources\RapportResource\Pages;
use App\Filament\Resources\RapportResource\RelationManagers;
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
use App\Models\Post;
use Filament\Tables\Actions\Action;
use App\Models\Demande;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;


use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;


class RapportResource extends Resource
{
    protected static ?string $model = Rapport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    // protected static ?string $navigationGroup = 'Nouvelle';
    protected static ?string $navigationLabel = 'Detail Nouvelle Rapport';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([


                    Forms\Components\TextInput::make('titre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('statut')
                        ->label('Status')
                        ->required()
                        ->hidden()
                        ->maxLength(255),
                    Forms\Components\Select::make('objet_demande_id')
                        ->relationship(name: 'ObjetRapport', titleAttribute: 'nomObjet')
                        ->label('Objet')
                        ->searchable()
                        ->required()
                        ->preload(), 
                    Forms\Components\Select::make('activite_id')
                        ->relationship(name: 'Activite', titleAttribute: 'nomActivite')
                        ->label('Activité')
                        ->searchable()
                        ->required()
                        ->preload(), 
                ])->columns(3),

                Forms\Components\Hidden::make('demande_id')
                    ->required()
                    ->dehydrated(true),
               
                
                FileUpload::make('url')
                    ->panelLayout('grid')
                    ->multiple()
                    ->downloadable()
                    ->directory('Rapport')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required()
                    ->getUploadedFileNameForStorageUsing(function ($file) {
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
                // ToggleColumn::make('statut')
                //     ->searchable(),
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
           
                Tables\Columns\TextColumn::make('ObjetRapport.nomObjet')
                    ->label('Objet')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('demande.user.poste.nomPoste')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

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
            'index' => Pages\ListRapports::route('/'),
            // 'create' => Pages\CreateRapport::route('/create'),
            // 'view' => Pages\ViewRapport::route('/{record}'),
            // 'edit' => Pages\EditRapport::route('/{record}/edit'),
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
         $query->where('statut', 'en_attente');


        return $query;
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();                   

        return in_array($user->role?->nomRole, ['Admin','Simple']);
    }
    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()->role?->nomRole === 'Simple') {
            return static::getModel()::whereHas('demande', function ($q) {
                $q->where('user_id', auth()->id());
            })->whereNot('statut', 'valide')->count() ;       
        }else{
            return static::getModel()::whereNot('statut', 'valide')->count();    
        }
    }

}
