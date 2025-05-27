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

class ValidationDemandeResource extends Resource
{
    protected static ?string $model = ValidationDemande::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
             
                Forms\Components\TextInput::make('commentaire')
                    ->maxLength(255)
                    ->visible(
                        fn(Callable $get)=>$get('estValid') ==='revision'
                    )
                    ->live(),
                Forms\Components\ToggleButtons::make('estValid')
                    ->options([
                        'valide'=>'valide',
                        'revision'=>'revision'
                    ])
                    ->icons([
                        'valide'=>'heroicon-o-check-circle',
                        'revision'=>'heroicon-o-pencil',
                    ])
                    ->inline()
                    ->required()
                    ->live(),
                FileUpload::make('motifRetour')
                    ->panelLayout('grid')
                    ->multiple()
                    ->downloadable()
                    ->directory('Revision/Demande')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(
                        fn(Callable $get)=>$get('estValid') ==='revision' && empty($get('commentaire'))
                    )
                    ->visible(
                        fn(Callable $get)=>$get('estValid') ==='revision'
                    ),

                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('demande_id')
                    ->relationship('demande', 'id')
                    ->required(),
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
                    })
                    ->color(
                        fn(string $state):string=>match($state){
                            'en_attente'=>'gray',
                            'valide'=>'success',
                            'revision'=>'info',
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
            'index' => Pages\ListValidationDemandes::route('/'),
            'create' => Pages\CreateValidationDemande::route('/create'),
            'view' => Pages\ViewValidationDemande::route('/{record}'),
            'edit' => Pages\EditValidationDemande::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role?->nomRole === 'Validateur') {
            $query->where('user_id', auth()->id()); // suppose que tu stockes l'utilisateur qui a créé
        }

        return $query;
    }

}
