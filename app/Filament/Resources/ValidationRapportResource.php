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


class ValidationRapportResource extends Resource
{
    protected static ?string $model = ValidationRapport::class;

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
                    ->directory('Revision/Rapport')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(
                        fn(Callable $get)=>$get('estValid') ==='revision' && empty($get('commentaire'))
                    )
                    ->visible(
                        fn(Callable $get)=>$get('estValid') ==='revision'
                    ),

                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name'),
                Forms\Components\Select::make('rapport_id')
                    ->relationship('rapport', 'id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('commentaire')
                    ->searchable(),
                // Tables\Columns\IconColumn::make('estValid')
                    // ->boolean(),
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
                Tables\Columns\TextColumn::make('rapport.id')
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
            'index' => Pages\ListValidationRapports::route('/'),
            'create' => Pages\CreateValidationRapport::route('/create'),
            'view' => Pages\ViewValidationRapport::route('/{record}'),
            'edit' => Pages\EditValidationRapport::route('/{record}/edit'),
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
    
}
