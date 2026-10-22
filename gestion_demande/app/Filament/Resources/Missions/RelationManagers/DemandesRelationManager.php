<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\Response;
use App\Models\Validation;

class DemandesRelationManager extends RelationManager
{
    protected static string $relationship = 'demandes';
    protected static ?string $title = 'Demandes liées';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            FileUpload::make('fichier')
                ->multiple()
                ->label('Fichiers')
                ->preserveFilenames()
                ->required(false),
            TextInput::make('statut')
                ->label('Status')
                ->hidden(),
            Toggle::make('validated_by_special')
                ->label('Validé par special')
                ->required(false)
                ->hidden(function () {
                    $user = Auth::user();
                    if (!$user) return true;
                    $roles = $user->roles()->pluck('name')->toArray();
                    return in_array('Valider demande', $roles) || 
                           (count($roles) === 1 && in_array('Simple', $roles));
                }),
            FileUpload::make('motif_special')
                ->label('Fichier justificatif (spécial)')
                ->hidden(function () {
                    $user = Auth::user();
                    if (!$user) return true;
                    $roles = $user->roles()->pluck('name')->toArray();
                    return in_array('Valider demande', $roles) || 
                           (count($roles) === 1 && in_array('Simple', $roles));
                }),
            Select::make('type_demande_id')
                ->relationship('typeDemande', 'name')
                ->label('Type demande')
                ->required(),
            Select::make('site_id')
                ->relationship('site', 'name')
                ->label('Site')
                ->required(),
            Select::make('activites')
                ->multiple()
                ->relationship('activites', 'name')
                ->label('Activités')
                ->preload(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        $isResponsible = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', 'responsable')
            ->exists() || (!(optional(Auth::user())->roles()->count() === 1 && optional(Auth::user())->roles()->pluck('name')->contains('Simple')));

        $hasScope = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', '!=', 'observateur')
            ->where(function ($q) {
                $q->where('mission_users.scope', 'demande')
                  ->orWhere('mission_users.scope', 'both');
            })
            ->exists() || (!(optional(Auth::user())->roles()->count() === 1 && optional(Auth::user())->roles()->pluck('name')->contains('Simple')));

        $isObserver = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', 'observateur')
            ->exists();

        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Validateur special', 'Autre',
        ];

        $currentUserRoles = optional(Auth::user())->roles()->pluck('name')->toArray() ?? [];
        $isPrivileged = ! empty(array_intersect($currentUserRoles, $privileged));

        // canCreate: only mission responsables or privileged roles can create
        $canCreate = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', 'responsable')
            ->exists() || $isPrivileged;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('statut')
                    ->badge()
                    ->colors([
                        'gray' => 'en_attente',
                        'warning' => 'en_cours',
                        'info' => 'correction',
                        'secondary' => 'en attente des rapports',
                        'success' => 'valide',
                        'danger' => 'refuse',
                    ]),
                Tables\Columns\TextColumn::make('typeDemande.name')->label('Type'),
                Tables\Columns\TextColumn::make('site.name')->label('Site'),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Ajouter une demande')
                    ->visible(fn () => $canCreate()),
            ])
            ->actions([
                Actions\ViewAction::make()->visible(fn ($record) => (
                    ($user = Auth::user()) && (
                        $isResponsible() || $hasScope() || $isObserver() ||
                        ($user->roles()->count() === 1 && $user->roles()->pluck('name')->contains('Validateur special'))
                    )
                )),

                // action principale (validate) : permet aux validateurs de décider, aux autres de voir "Décisions"
                Actions\Action::make('validate')
                    ->label(function ($record) {
                        $user = Auth::user();
                        $userRoles = $user?->roles()->pluck('name')->toArray() ?? [];

                        if (! $user) {
                            return 'Décisions';
                        }

                        if (optional($record)->user_id === $user->id) {
                            return 'Décisions';
                        }


                        $isValidator = in_array('Valider demande', $userRoles);
                        $isSpecialValidator = in_array('Validateur special', $userRoles);

                        return $isValidator ? 'Décider' : 'Décisions';
                    })
                    ->icon('heroicon-o-check-circle')
                    ->modalWidth('3xl')
                    ->form(function ($record) {

                        $user = Auth::user();
                        $userRoles = $user?->roles()->pluck('name')->toArray() ?? [];

                        $isValidator = in_array('Valider demande', $userRoles);
                        $isSpecialValidator = in_array('Validateur special', $userRoles);

                        $currentUserValidation = $record->validations()
                            ->where('id_validateur', Auth::id())
                            ->first();

                        $allValidations = $record->validations()->get();
                        $validationsByUser = $allValidations->keyBy('id_validateur');

                        $validatorRoles = ['Valider demande'];
                        $allValidators = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', $validatorRoles))->get();

                        $allDecisions = $record->validations()->pluck('decision')->toArray();
                        $allValidated = count($allDecisions) > 0 && 
                                       !in_array(Validation::DECISION_PENDING, $allDecisions) &&
                                       !in_array('correction', $allDecisions) &&
                                       !in_array('rejete', $allDecisions) &&
                                       count(array_filter($allDecisions, fn($d) => $d === 'valide')) === count($allDecisions);

                        $tabs = [];

                        foreach ($allValidators as $validator) {
                            if ($validator->id === Auth::id()) {
                                continue;
                            }

                            $validation = $validationsByUser->get($validator->id);

                            $tabs[] = Tab::make($validator->name)
                                ->schema([
                                    Select::make('other_decision_' . $validator->id)
                                        ->label('Décision')
                                        ->options([
                                            'valide' => '✓ Validé',
                                            'correction' => '⚠ Correction demandée',
                                            'rejete' => '✗ Rejeté',
                                            'en_cours' => '⏳ En attente',
                                        ])
                                        ->default($validation?->decision ?? 'en_cours')
                                        ->disabled(),
                                    Textarea::make('other_commentaire_' . $validator->id)
                                        ->label('Commentaire')
                                        ->default($validation?->commentaire ?? '')
                                        ->disabled()
                                        ->rows(4),
                                    TextInput::make('other_validated_at_' . $validator->id)
                                        ->label('Décidé le')
                                        ->default($validation?->validated_at?->format('d/m/Y H:i') ?? 'En attente')
                                        ->disabled(),
                                    ViewField::make('other_fichiers_' . $validator->id)
                                        ->view('filament.components.validation-files', ['files' => $validation?->fichier_commentaire ?? []]),
                                ]);
                        }


                        if ($isSpecialValidator) {
                            $tabs[] = Tab::make('Validation spéciale')
                                ->schema([
                                    Toggle::make('validated_by_special')
                                        ->label('Valider la demande (spécial)')
                                        ->required(),
                                    FileUpload::make('motif_special')
                                        ->label('Fichier justificatif (obligatoire)')
                                        ->required()
                                        ->maxSize(10240)
                                        ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                        ->disk('public')
                                        ->directory('motifs_specials')
                                        ->preserveFilenames(),
                                ]);
                        } else if ($isValidator) {
                            $tabs[] = Tab::make('Ma décision')
                                ->schema([
                                    Select::make('decision')
                                        ->label('Votre décision')
                                        ->options([
                                            'valide' => '✓ Validé',
                                            'correction' => '⚠ Correction demandée',
                                            'rejete' => '✗ Rejeté',
                                        ])
                                        ->default($currentUserValidation?->decision !== Validation::DECISION_PENDING ? $currentUserValidation?->decision : null)
                                        ->required()
                                        ->disabled($allValidated),
                                    Textarea::make('commentaire')
                                        ->label('Commentaire / Remarque')
                                        ->placeholder('Expliquez votre décision...')
                                        ->rows(4)
                                        ->default($currentUserValidation?->commentaire)
                                        ->nullable()
                                        ->disabled($allValidated),
                                    FileUpload::make('fichier_commentaire')
                                        ->label('Fichiers joints (remarques, documents, etc.)')
                                        ->multiple()
                                        ->maxSize(10240)
                                        ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                        ->disk('public')
                                        ->directory('validation_comments')
                                        ->preserveFilenames()
                                        ->disabled($allValidated),
                                    ViewField::make('my_fichiers')
                                        ->view('filament.components.validation-files', ['files' => $currentUserValidation?->fichier_commentaire ?? []]),
                                ]);
                        }

                        return [
                            Tabs::make('all_decisions')
                                ->tabs($tabs)
                        ];
                    })
                    ->action(function ($record, array $data): void {
                        $user = Auth::user();

                        $userRoles = $user?->roles()->pluck('name')->toArray() ?? [];
                        $isValidator = in_array('Valider demande', $userRoles);
                        $isSpecialValidator = in_array('Validateur special', $userRoles);

                        if (! $isValidator && ! $isSpecialValidator) {
                            return;
                        }

                        if (($data['decision'] ?? null) === 'correction') {
                            $comment = trim((string) ($data['commentaire'] ?? ''));
                            $files = $data['fichier_commentaire'] ?? null;
                            $hasFiles = is_array($files) ? count($files) > 0 : (!empty($files));

                            if ($comment === '' && ! $hasFiles) {
                                Notification::make()
                                    ->title('Fichier requis')
                                    ->danger()
                                    ->body('Lorsque vous demandez une correction, fournissez un fichier ou précisez un commentaire.')
                                    ->send();

                                return;
                            }
                        }


                        if ($isSpecialValidator) {
                            $validated = $data['validated_by_special'] ?? false;
                            $motif = $data['motif_special'] ?? null;
                            if (!$validated || empty($motif)) {
                                Notification::make()
                                    ->title('Validation spéciale incomplète')
                                    ->danger()
                                    ->body('Vous devez cocher la validation et fournir un fichier justificatif.')
                                    ->send();
                                return;
                            }
                            $record->validated_by_special = true;
                            $record->motif_special = $motif;
                            $record->statut = 'valide';
                            $record->save();
                            Notification::make()
                                ->title('Validation spéciale enregistrée')
                                ->body('Le statut de la demande a été mis à jour (validation spéciale).')
                                ->success()
                                ->send();
                            return;
                        }

                        // Cas normal (valider demande)
                        $validation = Validation::firstOrCreate(
                            [
                                'validable_type' => 'App\\Models\\Demande',
                                'validable_id' => $record->id,
                                'id_validateur' => Auth::id(),
                            ],
                            [
                                'decision' => $data['decision'],
                                'commentaire' => $data['commentaire'] ?? null,
                                'fichier_commentaire' => $data['fichier_commentaire'] ?? null,
                                'validated_at' => now(),
                            ]
                        );

                        $validation->update([
                            'decision' => $data['decision'],
                            'commentaire' => $data['commentaire'] ?? null,
                            'fichier_commentaire' => !empty($data['fichier_commentaire']) ? $data['fichier_commentaire'] : $validation->fichier_commentaire,
                            'validated_at' => now(),
                        ]);

                        // Reload the validation to ensure relations are fresh
                        $validation = $validation->fresh();
                        $validation->applyDecision();

                        Notification::make()
                            ->title('Décision enregistrée')
                            ->body('Le statut de la demande a été mis à jour.')
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel('Soumettre ma décision')
                    ->visible(function ($record) {
                        if ($record->statut === 'valide' || $record->statut === 'en attente des rapports') {
                            return false;
                        }

                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $userRoles = $user->roles()->pluck('name')->toArray();

                        if (optional($record)->user_id === $user->id) {
                            return true;
                        }

                        if (in_array('Valider demande', $userRoles)) {
                            return true;
                        }

                        if ($record->mission) {
                            $userInMission = $record->mission->users()->where('users.id', $user->id)->first();
                            if ($userInMission) {
                                $role = $userInMission->pivot->role_mission ?? null;
                                $scope = $userInMission->pivot->scope ?? null;

                                // Allow mission responsables and observateurs to view decisions;
                                // members only when their scope includes 'demande' or 'both'.
                                if ($role === 'responsable' || $role === 'observateur') {
                                    return true;
                                }

                                if ($role === 'member' && in_array($scope, ['demande', 'both'])) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    }),

                // Note: readonly 'decisions' action removed. The 'validate' action
                // now serves both as the decision form for validators and as the
                // readonly decisions viewer for other permitted users (like in
                // RapportsRelationManager).

                Actions\EditAction::make()
                    // (supprimé : onSave non supporté)
                    // (supprimé : handle non supporté)
                    ->label(function ($record) {
                        $user = Auth::user();
                        $userRoles = $user?->roles()->pluck('name')->toArray() ?? [];
                        return in_array('Validateur special', $userRoles) ? 'Valider (spécial)' : 'Editer';
                    })
                    ->visible(function ($record) {
                        if (($record->statut ?? null) === 'valide') {
                            return false;
                        }
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }
                        $userRoles = $user->roles()->pluck('name')->toArray();
                        // Validateur special peut éditer seule si la demande n'est pas validée
                        if (in_array('Validateur special', $userRoles)) {
                            return !$record->validated_by_special;
                        }
                        $privileged = [
                            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                            'Autre',
                        ];
                        if (! empty(array_intersect($userRoles, $privileged))) {
                            return true;
                        }
                        $mission = $this->getOwnerRecord();
                        if (! $mission) {
                            return false;
                        }
                        $u = $mission->users()->where('users.id', $user->id)->first();
                        if (! $u) {
                            return false;
                        }
                        $role = $u->pivot->role_mission ?? null;
                        // Allow mission responsables and members to edit
                        if ($role === 'responsable' || $role === 'member') {
                            return true;
                        }
                        return false;
                    })
                    ->form(function ($record) {
                        $user = Auth::user();
                        $userRoles = $user?->roles->pluck('name')->toArray() ?? [];
                        $isSimple = ($user->roles()->count() === 1 && $user->roles()->pluck('name')->contains('Simple'));
                        
                        if (in_array('Validateur special', $userRoles)) {
                            // Formulaire spécial : seuls validated_by_special et motif_special sont éditables
                            return [
                                Toggle::make('validated_by_special')
                                    ->label('Valider la demande (spécial)')
                                    ->required(),
                                FileUpload::make('motif_special')
                                    ->label('Fichier justificatif (obligatoire)')
                                    ->required()
                                    ->maxSize(10240)
                                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->disk('public')
                                    ->directory('motifs_specials')
                                    ->preserveFilenames(),
                            ];
                        }
                        
                        if ($isSimple) {
                            // Formulaire pour Simple : fichier et autres champs éditables, champs spéciaux cachés
                            return [
                                FileUpload::make('fichier')
                                    ->multiple()
                                    ->label('Fichiers')
                                    ->preserveFilenames()
                                    ->required(false),
                                Select::make('type_demande_id')
                                    ->relationship('typeDemande', 'name')
                                    ->label('Type demande')
                                    ->required(),
                                Select::make('site_id')
                                    ->relationship('site', 'name')
                                    ->label('Site')
                                    ->required(),
                                Select::make('activites')
                                    ->multiple()
                                    ->relationship('activites', 'name')
                                    ->label('Activités')
                                    ->preload(),
                            ];
                        }
                                                                                    // (supprimé : saveRecordUsing non supporté)
                                                                // (supprimé : mutateRecordDataUsing non supporté)
                                            // (supprimé : action non supporté)
                        // Formulaire normal (readonly sur les champs spéciaux)
                        return [
                            FileUpload::make('fichier')
                                ->multiple()
                                ->label('Fichiers')
                                ->preserveFilenames()
                                ->required(false),
                            TextInput::make('statut')
                                ->label('Status')
                                ->disabled(),
                            Toggle::make('validated_by_special')
                                ->label('Validé par special')
                                ->disabled(),
                            FileUpload::make('motif_special')
                                ->label('Fichier justificatif (spécial)')
                                ->disabled(),
                            Select::make('type_demande_id')
                                ->relationship('typeDemande', 'name')
                                ->label('Type demande')
                                ->required()
                                ->disabled(),
                            Select::make('site_id')
                                ->relationship('site', 'name')
                                ->label('Site')
                                ->required()
                                ->disabled(),
                            Select::make('activites')
                                ->multiple()
                                ->relationship('activites', 'name')
                                ->label('Activités')
                                ->preload()
                                ->disabled(),
                        ];
                    }),

                Actions\DeleteAction::make()
                    ->visible(fn ($record) => (
                        $isResponsible() && 
                        !((Auth::user()->roles()->count() === 1) && Auth::user()->roles()->pluck('name')->contains('Validateur special')) &&
                        // Prevent deletion if demande is valide
                        ($record->statut ?? null) !== 'valide'
                    )),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->visible(fn () => (
                    $isResponsible() && 
                    !((Auth::user()->roles()->count() === 1) && Auth::user()->roles()->pluck('name')->contains('Validateur special')) &&
                    $this->getOwnerRecord()?->status !== 'valide'
                )),
            ]);
    }

    public function getDefaultActionAuthorizationResponse(Actions\Action $action): ?Response
    {
        // Prevent deletion of demandes with protected statuts
        if ($action instanceof Actions\DeleteAction) {
            $record = $action->getRecord();
            if ($record && in_array($record->statut, ['valide'])) {
                return Response::deny('Les demandes validées ne peuvent pas être supprimées.');
            }
            
            // Prevent deletion if mission is validated
            $owner = $this->getOwnerRecord();
            if ($owner?->status === 'valide') {
                return Response::deny('Les demandes d\'une mission validée ne peuvent pas être supprimées.');
            }
        }

        // Prevent editing of demandes with protected statuts
        if ($action instanceof Actions\EditAction) {
            $record = $action->getRecord();
            if ($record && $record->statut === 'valide') {
                return Response::deny('Les demandes validées ne peuvent pas être modifiées.');
            }
            
            // Prevent editing if mission is validated
            $owner = $this->getOwnerRecord();
            if ($owner?->status === 'valide') {
                return Response::deny('Les demandes d\'une mission validée ne peuvent pas être modifiées.');
            }
        }

        // Deny create/associate/attach logic here; keep default behaviour for those
        if ($action instanceof Actions\CreateAction || $action instanceof Actions\AssociateAction || $action instanceof Actions\AttachAction) {
            return parent::getDefaultActionAuthorizationResponse($action);
        }

        $user = Auth::user();
        if (! $user) {
            return parent::getDefaultActionAuthorizationResponse($action);
        }

        $roles = $user->roles()->pluck('name')->toArray();
        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Validateur special', 'Autre',
        ];

        if (! empty(array_intersect($roles, $privileged))) {
            return Response::allow();
        }

        $owner = $this->getOwnerRecord();
        $userInMission = $owner?->users()->where('users.id', $user->id)->first();
        if ($userInMission) {
            $role = $userInMission->pivot->role_mission ?? null;
            if (in_array($role, ['responsable', 'member'])) {
                return Response::allow();
            }
        }

        return parent::getDefaultActionAuthorizationResponse($action);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return false;
        }

        $roles = $authUser->roles()->pluck('name')->toArray();

        // Keep existing behaviour for single-role 'Simple' users
        if (count($roles) === 1 && $roles[0] === 'Simple') {
            $userId = $authUser->id;

            $user = $ownerRecord->users()->where('users.id', $userId)->first();

            if (! $user) {
                return false;
            }

            $role = $user->pivot->role_mission ?? null;
            $scope = $user->pivot->scope ?? null;

            if ($role === 'responsable' || $role === 'observateur') {
                return true;
            }

            return in_array($scope, ['demande', 'both']);
        }

        // For multi-role users: deny the demandes tab for users who only have roles
        // that match TypeRapport names and who do not have privileged roles
        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Valider demande', 'Valide demande', 'Validateur special', 'Autre',
        ];

        if (! empty(array_intersect($roles, $privileged))) {
            return true;
        }

        $typeNames = \App\Models\TypeRapport::pluck('name')->toArray();
        if (! empty(array_intersect($roles, $typeNames))) {
            return false;
        }

        return true;
    }

    public function canCreate(): bool
    {
        // If the mission is validated, no creation allowed
        $ownerRecord = $this->getOwnerRecord();
        if ($ownerRecord?->status === 'valide') {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $roles = $user->roles()->pluck('name')->toArray();
        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Valider demande', 'Valide demande', 'Validateur special', 'Autre',
        ];

        if (! empty(array_intersect($roles, $privileged))) {
            return true;
        }

        $ownerRecord = $this->getOwnerRecord();
        $userInMission = $ownerRecord?->users()->where('users.id', $user->id)->first();
        if ($userInMission && ($userInMission->pivot->role_mission ?? null) === 'responsable') {
            return true;
        }

        return false;
    }

    public function canEdit(Model $record): bool
    {
        // If the mission is validated, no editing allowed
        $ownerRecord = $this->getOwnerRecord();
        if ($ownerRecord?->status === 'valide') {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $roles = $user->roles()->pluck('name')->toArray();
        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Valider demande', 'Valide demande', 'Validateur special', 'Autre',
        ];

        if (! empty(array_intersect($roles, $privileged))) {
            return true;
        }

        $ownerRecord = $this->getOwnerRecord();
        $userInMission = $ownerRecord?->users()->where('users.id', $user->id)->first();
        if ($userInMission) {
            $role = $userInMission->pivot->role_mission ?? null;
            if ($role === 'responsable' || $role === 'member') {
                return true;
            }
        }

        return false;
    }
}
