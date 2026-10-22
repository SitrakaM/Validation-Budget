<?php

namespace App\Filament\Resources\Rapports\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;


class ValidationsRelationManager extends RelationManager
{
    protected static string $relationship = 'validations';
    protected static ?string $recordTitleAttribute = 'validateur';
    protected static ?string $title = 'Décisions de validation';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('validateur.name')
                    ->label('Validateur')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('decision')
                    ->label('Décision')
                    // ->enum([
                    //     'en_cours' => 'En cours',
                    //     'valide' => 'Valide',
                    //     'rejete' => 'Refusé',
                    //     'correction' => 'Correction',
                    // ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'en_cours' => 'En cours',
                        'valide' => 'Valide',
                        'rejete' => 'Refusé',
                        'correction' => 'Correction',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'en_cours',
                        'success' => 'valide',
                        'danger' => 'rejete',
                        'warning' => 'correction',
                    ])
                    ->sortable(),
                TextColumn::make('commentaire')
                    ->label('Commentaire')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->commentaire)
                    ->default('-'),
                TextColumn::make('fichier_commentaire')
                    ->label('Fichiers')
                    ->formatStateUsing(fn($state) => is_array($state) && !empty($state) ? count($state) . ' fichier(s)' : '-')
                    ->default('-'),
                TextColumn::make('validated_at')
                    ->label('Décidé le')
                    ->dateTime('d/m/Y H:i')
                    // ->default('-')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->actions([
                Actions\Action::make('decide')
                    ->label('Décider')
                    ->icon('heroicon-o-check-circle')
                    ->modalWidth('2xl')
                    ->form([
                        Select::make('decision')
                            ->label('Votre décision')
                            ->options([
                                'valide' => '✓ Valide',
                                'correction' => '⚠ Correction requise',
                                'rejete' => '✗ Refusé',
                            ])
                            ->required(),
                        Textarea::make('commentaire')
                            ->label('Commentaire / Remarque')
                            ->placeholder('Expliquez votre décision...')
                            ->rows(3),
                        FileUpload::make('fichier_commentaire')
                            ->label('Fichiers joints (remarques, documents, etc.)')
                            ->multiple()
                            ->maxSize(10240) // 10 MB
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
                    ])
                    // Allow the assigned validator to decide or change their decision
                    // as long as the parent report is NOT finally validated ('valide').
                    ->visible(fn ($record) => (
                        Auth::id() === $record->id_validateur && optional($record->validable)->statut !== 'valide'
                    ))
                    ->disabled(fn ($record) => Auth::id() !== $record->id_validateur)
                    ->action(function ($record, array $data) {
                        $record->decision = $data['decision'];
                        $record->commentaire = $data['commentaire'] ?? null;
                        if (! empty($data['fichier_commentaire'])) {
                            $record->fichier_commentaire = $data['fichier_commentaire'];
                        }
                        $record->validated_at = now();
                        $record->save();
                        
                        // Reload the validation to ensure relations are fresh
                        $record = $record->fresh();
                        $record->applyDecision();
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Succès')
                            ->body('Action effectuée avec succès')
                    )
                    ->modalSubmitActionLabel('Soumettre ma décision'),
                Actions\ViewAction::make('view_decision')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->modal(true)
                    ->form([
                        Select::make('decision')
                            ->label('Décision')
                            ->options([
                                'en_cours' => 'En cours',
                                'valide' => 'Valide',
                                'correction' => 'Correction',
                                'rejete' => 'Rejeté',
                            ])
                            ->disabled(),
                        Textarea::make('commentaire')
                            ->label('Commentaire')
                            ->rows(6)
                            ->disabled(),
                        Forms\Components\Placeholder::make('validated_at')
                            ->label('Décidé le')
                            ->content(fn ($record) => $record->validated_at ? $record->validated_at->format('d/m/Y H:i') : '-'),
                    ])
                    ->visible(fn ($record) => (
                        // Visible to the validator, the owner of the report, mission responsable or privileged validators
                        ($user = Auth::user()) && (
                            Auth::id() === $record->id_validateur ||
                            (optional($record->validable)->user_id ?? null) === Auth::id() ||
                            (optional($record->validable)->mission && optional($record->validable)->mission->users()->where('users.id', Auth::id())->where('mission_users.role_mission', 'responsable')->exists()) ||
                            ! empty(array_intersect($user->roles()->pluck('name')->toArray(), ['Validateur','ValidateurRapport','Valider demande','Valide demande','Validateur special']))
                        )
                    )),
            ])
            ->headerActions([])
            ->bulkActions([])
            ->emptyStateHeading('Aucun validateur');
    }
}

