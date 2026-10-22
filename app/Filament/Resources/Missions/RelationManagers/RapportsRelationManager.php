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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\Response;
use App\Models\Validation;


class RapportsRelationManager extends RelationManager
{
    protected static string $relationship = 'rapports';
    protected static ?string $title = 'Rapports liés';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            FileUpload::make('fichier')
                ->multiple()
                ->label('Fichiers')
                ->preserveFilenames()
                ->required(false)
                ->downloadable(),
            Select::make('type_rapport_id')
                ->relationship('typeRapport', 'name')
                ->label('Type de rapport')
                ->required(),
            Toggle::make('physical')
                ->label('Physique')
                ->required()
                ->disabled(fn (?\Illuminate\Database\Eloquent\Model $record) => (bool) ($record?->physical ?? false)),
            TextInput::make('statut')
                ->label('Status')
                ->hidden(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        $userRoles = optional(Auth::user())->roles()->pluck('name')->toArray() ?? [];
        $privileged = [
            'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
            'Autre',
        ];

        $isPrivileged = ! empty(array_intersect($userRoles, $privileged));

        $isResponsible = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', 'responsable')
            ->exists();
        $hasScope = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', '!=', 'observateur')
            ->where(function ($q) {
                $q->where('mission_users.scope', 'rapport')
                  ->orWhere('mission_users.scope', 'both');
            })
            ->exists() || $isPrivileged;

        $isObserver = fn () => $this->getOwnerRecord()
            ?->users()
            ->where('users.id', Auth::id())
            ->where('mission_users.role_mission', 'observateur')
            ->exists();

        $table = $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('typeRapport.name')->label('Type'),
                Tables\Columns\TextColumn::make('statut')
                    ->badge()
                    ->colors([
                        'secondary' => 'non_soumis',
                        'gray' => 'en_attente',
                        'warning' => 'en_cours',
                        'info' => 'correction',
                        'success' => 'valide',
                        'danger' => 'refuse',
                    ]),
                Tables\Columns\IconColumn::make('physical')->boolean()->label('Physique'),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Ajouter un rapport')
                    ->visible(fn () => $isResponsible() || $isPrivileged),
            ])
            ->actions([
                Actions\ViewAction::make()->visible(fn ($record) => (
                    ($user = Auth::user()) && (
                        ($userRoles = $user->roles()->pluck('name')->toArray()) && (
                            // Validateur special peut voir (pas besoin d'être dans mission)
                            (in_array('Validateur special', $userRoles)) ||
                            // Ou simple/responsable/observateur
                            (($user->roles()->count() === 1 && $user->roles()->pluck('name')->contains('Simple'))
                                && ($userId = $user->id) && ($record->mission) && ($u = $record->mission->users()->where('users.id', $userId)->first()) && (
                                    ($u->pivot->role_mission ?? null) === 'responsable' || in_array($u->pivot->scope ?? null, ['rapport', 'both'])
                                )
                            ) ||
                            // Ou privilégié avec rôle correspondant
                            (!empty(array_intersect($userRoles, $privileged)) || in_array($record->typeRapport?->name, $userRoles) || in_array('Valider demande', $userRoles))
                        )
                    )
                )),
                Actions\Action::make('validate')
                    ->label(function ($record) {
                        $user = Auth::user();
                        if (!$user) {
                            return 'Décisions';
                        }

                        $userRoles = $user->roles()->pluck('name')->toArray();
                        
                        // Envoyeur/créateur peut voir les décisions
                        if (optional($record)->user_id === $user->id) {
                            return 'Décisions';
                        }
                        
                        // "Valider demande" seul ne peut pas valider les rapports
                        if ($userRoles === ['Valider demande'] || (count($userRoles) === 2 && in_array('Simple', $userRoles) && in_array('Valider demande', $userRoles))) {
                            return 'Décisions';
                        }

                        // Vérifier si l'utilisateur est un validateur pour ce type de rapport
                        $privileged = [
                            'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                            'Autre',
                        ];

                        $isValidator = false;
                        
                        // Si a un rôle privilégié
                        if (!empty(array_intersect($userRoles, $privileged))) {
                            $isValidator = true;
                        }
                        
                        // Ou si a un rôle correspondant au type de rapport
                        if (in_array($record->typeRapport?->name, $userRoles)) {
                            $isValidator = true;
                        }

                        return $isValidator ? 'Décider' : 'Décisions';
                    })
                    ->icon('heroicon-o-check-circle')
                    ->modalWidth('3xl')
                    ->form(function ($record) {
                        $user = Auth::user();
                        if (!$user) {
                            return [];
                        }

                        $userRoles = $user->roles()->pluck('name')->toArray();
                        
                        // Déterminer si c'est un validateur
                        $isValidator = false;
                        $privileged = [
                            'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                            'Valide demande', 'Autre',
                        ];

                        // "Valider demande" seul ne peut pas valider
                        if (!($userRoles === ['Valider demande'] || (count($userRoles) === 2 && in_array('Simple', $userRoles) && in_array('Valider demande', $userRoles)))) {
                            // Si a un rôle privilégié
                            if (!empty(array_intersect($userRoles, $privileged))) {
                                $isValidator = true;
                            }
                            // Ou si a un rôle correspondant au type de rapport
                            else if (in_array($record->typeRapport?->name, $userRoles)) {
                                $isValidator = true;
                            }
                        } else if (in_array('Valider demande', $userRoles) && in_array($record->typeRapport?->name, $userRoles)) {
                            // "Valider demande" + rôle du rapport = peut valider
                            $isValidator = true;
                        }

                        $currentUserValidation = $record->validations()
                            ->where('id_validateur', Auth::id())
                            ->first();

                        $allValidations = $record->validations()->get();
                        $validationsByUser = $allValidations->keyBy('id_validateur');

                        // Build full list of validators (privileged users + users with role matching report type)
                        $validatorUsers = collect();
                        // privileged validators
                        $privilegedValidatorRoles = $privileged;
                        if (!empty($privilegedValidatorRoles)) {
                            $validatorUsers = $validatorUsers->merge(
                                \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', $privilegedValidatorRoles))->get()
                            );
                        }

                        // users with role equal to the report type
                        $typeRole = $record->typeRapport?->name;
                        if ($typeRole) {
                            $validatorUsers = $validatorUsers->merge(
                                \App\Models\User::whereHas('roles', fn($q) => $q->where('name', $typeRole))->get()
                            );
                        }

                        $allValidators = $validatorUsers->unique('id')->values();
                        // Vérifier si TOUS les utilisateurs ayant le rôle 'Valider demande' ont validé
                        $validatorRoleNames = ['Valider demande', 'Valide demande'];
                        $validatorIds = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', $validatorRoleNames))->pluck('id')->toArray();

                        $validatedCount = 0;
                        if (!empty($validatorIds)) {
                            $validatedCount = $record->validations()
                                ->whereIn('id_validateur', $validatorIds)
                                ->where('decision', 'valide')
                                ->count();
                        }

                        $allValidated = !empty($validatorIds) && $validatedCount === count($validatorIds);

                        $tabs = [];

                        // Onglets: Décisions des autres validateurs (en readonly)
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
                                    FileUpload::make('other_fichiers_' . $validator->id)
                                        ->label('Fichiers joints')
                                        ->afterStateHydrated(fn (FileUpload $component) => $component->state($validation?->fichier_commentaire ?? []))
                                        ->disk('public')
                                        ->multiple()
                                        ->downloadable()
                                        ->disabled()
                                        ->deletable(false)
                                        ->previewable(false)
                                        ->dehydrated(false),
                                    ]);
                        }

                        // Ajouter l'onglet "Ma décision" seulement si c'est un validateur
                        if ($isValidator) {
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
                                        // ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                        ->afterStateHydrated(fn (FileUpload $component) => $component->state($currentUserValidation?->fichier_commentaire ?? []))
                                        ->previewable(false)
                                        ->disk('public')
                                        ->directory('validation_comments')
                                        ->preserveFilenames()
                                        ->downloadable()   
                                        ->deletable(true)                                     
                                        ->disabled($allValidated),

                                    
                                  
                                ]);
                        }

                        return [
                            Tabs::make('all_decisions')
                                ->tabs($tabs)
                        ];
                    })
                    ->action(function ($record, array $data): void {
                        // Seulement les validateurs peuvent soumettre une décision
                        $user = Auth::user();
                        if (!$user) {
                            return;
                        }

                        // Bloquer la soumission des décisions si le rapport n'est pas encore soumis
                        if ($record->statut === 'non_soumis') {
                            Notification::make()
                                ->title('Rapport non soumis')
                                ->danger()
                                ->body("Le rapport n'est pas encore soumis. Les validateurs ne peuvent prendre de décision tant qu'il est 'non_soumis'.")
                                ->send();

                            return;
                        }

                        $userRoles = $user->roles()->pluck('name')->toArray();
                        
                        // Déterminer si c'est un validateur
                        $isValidator = false;
                        $privileged = [
                            'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                            'Valide demande', 'Autre',
                        ];

                        // "Valider demande" seul ne peut pas valider les rapports
                        if (!($userRoles === ['Valider demande'] || (count($userRoles) === 2 && in_array('Simple', $userRoles) && in_array('Valider demande', $userRoles)))) {
                            // Si a un rôle privilégié
                            if (!empty(array_intersect($userRoles, $privileged))) {
                                $isValidator = true;
                            }
                            // Ou si a un rôle correspondant au type de rapport
                            else if (in_array($record->typeRapport?->name, $userRoles)) {
                                $isValidator = true;
                            }
                        } else if (in_array('Valider demande', $userRoles) && in_array($record->typeRapport?->name, $userRoles)) {
                            // "Valider demande" + rôle du rapport = peut valider
                            $isValidator = true;
                        }
                        
                        if (!$isValidator) {
                            return; // Non-validateur ne peut pas décider
                        }

                        // Validation: si la décision est 'correction' et pas de commentaire,
                        // exiger au moins un fichier joint.
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

                        $validation = Validation::firstOrCreate(
                            [
                                'validable_type' => 'App\\Models\\Rapport',
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

                        // Apply the decision and update the rapport status
                        $validation->applyDecision();

                        Notification::make()
                            ->title('Décision enregistrée')
                            ->body('Le statut du rapport a été mis à jour.')
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel('Soumettre ma décision')
                    ->visible(function ($record) {
                        // Si le rapport est définitivement valide, cacher le bouton
                        if ($record->statut === 'valide') {
                            return false;
                        }
                        // Tant que le rapport n'est pas soumis, les validateurs ne doivent pas intervenir
                        if ($record->statut === 'non_soumis') {
                            return false;
                        }

                        $user = Auth::user();
                        if (!$user) {
                            return false;
                        }

                        $userRoles = $user->roles()->pluck('name')->toArray();
                        $privileged = [
                            'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                            'Valide demande', 'Autre',
                        ];

                        // Envoyeur/créateur peut toujours voir les décisions
                        if ($record->mission && optional($record)->user_id === $user->id) {
                            return true;
                        }

                        // Pour les utilisateurs 'Simple'
                        if ($user->roles()->count() === 1 && $user->roles()->pluck('name')->contains('Simple')) {
                            if ($record->mission) {
                                $userInMission = $record->mission->users()->where('users.id', $user->id)->first();
                                if ($userInMission) {
                                    $role = $userInMission->pivot->role_mission ?? null;
                                    $scope = $userInMission->pivot->scope ?? null;
                                    return $role === 'responsable' || in_array($scope, ['rapport', 'both']);
                                }
                            }
                            return false;
                        }

                        // "Valider demande" seul should NOT see the validate modal (they have a readonly 'decision' action)
                        if ($userRoles === ['Valider demande'] || (count($userRoles) === 2 && in_array('Simple', $userRoles) && in_array('Valider demande', $userRoles))) {
                            return false; // readonly handled by separate 'decision' action
                        }

                        // Pour les utilisateurs privilégiés
                        if (!empty(array_intersect($userRoles, $privileged))) {
                            return true;
                        }

                        // Pour les utilisateurs avec rôle spécifique au type de rapport
                        if (in_array($record->typeRapport?->name, $userRoles)) {
                            return true;
                        }

                        // Membre de mission avec scope rapport/both peut voir les décisions
                        if ($record->mission) {
                            $userInMission = $record->mission->users()->where('users.id', $user->id)->first();
                            if ($userInMission) {
                                $role = $userInMission->pivot->role_mission ?? null;
                                $scope = $userInMission->pivot->scope ?? null;
                                if ($role === 'member' && in_array($scope, ['rapport', 'both'])) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    }),
                Actions\Action::make('decision')
                    ->label('Décisions')
                    ->icon('heroicon-o-eye')
                    ->modalWidth('3xl')
                    ->form(function ($record) {
                        $user = Auth::user();
                        if (!$user) {
                            return [];
                        }

                        // Build validators list (same logic as validate form)
                        $privileged = [
                            'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                            'Valide demande', 'Autre',
                        ];

                        $allValidations = $record->validations()->get();
                        $validationsByUser = $allValidations->keyBy('id_validateur');

                        $validatorUsers = collect();
                        if (!empty($privileged)) {
                            $validatorUsers = $validatorUsers->merge(
                                \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', $privileged))->get()
                            );
                        }

                        $typeRole = $record->typeRapport?->name;
                        if ($typeRole) {
                            $validatorUsers = $validatorUsers->merge(
                                \App\Models\User::whereHas('roles', fn($q) => $q->where('name', $typeRole))->get()
                            );
                        }

                        $allValidators = $validatorUsers->unique('id')->values();

                        $tabs = [];

                        foreach ($allValidators as $validator) {
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
                                    // ViewField::make('other_fichiers_' . $validator->id)
                                    //     ->view('filament.components.validation-files', ['files' => $validation?->fichier_commentaire ?? []]),
                               
                                    FileUpload::make('other_fichiers_' . $validator->id)
                                        ->label('Fichiers joints')
                                        // On injecte les données manuellement
                                        ->afterStateHydrated(fn (FileUpload $component) => $component->state($validation?->fichier_commentaire ?? []))
                                        
                                        // Configuration du stockage
                                        ->disk('public') 
                                        ->multiple()
                                        ->downloadable()
                                        ->disabled()
                                        ->deletable(false)
                                        ->previewable(false)
                                        ->dehydrated(false)
                                    ]);
                        }

                        return [
                            Tabs::make('all_decisions')
                                ->tabs($tabs)
                        ];
                    })

                    ->visible(function ($record) {
                        // Empêcher l'accès en lecture des décisions lorsque le rapport n'est pas soumis
                        if ($record->statut === 'non_soumis') {
                            return false;
                        }

                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        $userRoles = $user->roles()->pluck('name')->toArray();

                        // Permettre à 'Validateur special' de voir les décisions comme un simple
                        if (in_array('Valider demande', $userRoles) || in_array('Validateur special', $userRoles)) {
                            return true;
                        }

                        return false;
                    }),
                Actions\EditAction::make()->visible(function ($record) {
                    if (($record->statut ?? null) === 'valide') {
                        return false;
                    }

                    $user = Auth::user();
                    if (! $user) {
                        return false;
                    }

                    $userRoles = $user->roles()->pluck('name')->toArray();
                    // Empêcher le validateur special d'éditer/valider un rapport
                    if (in_array('Validateur special', $userRoles)) {
                        return false;
                    }
                    $privileged = [
                        'Admin', 'Validateur', 'ValidateurRapport', 'Special', 'Budget',
                        'Valide demande', 'Autre',
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

                    // Allow mission responsables and members to edit (members now allowed regardless of scope)
                    if ($role === 'responsable' || $role === 'member') {
                        return true;
                    }

                    return false;
                }),
                Actions\DeleteAction::make()
                    ->visible(fn ($record) => (
                        ($user = Auth::user()) && (
                            (!($user->roles()->count() === 1 && $user->roles()->pluck('name')->contains('Simple')) && (
                                $roles = $user->roles()->pluck('name')->toArray() and (
                                    in_array('Admin', $roles) || (
                                        ($userId = $user->id) && ($record->mission) && ($u = $record->mission->users()->where('users.id', $userId)->first()) && (($u->pivot->role_mission ?? null) === 'responsable')
                                    )
                                )
                            ))
                        ) && (
                            // Prevent deletion if rapport is valide
                            $record->statut !== 'valide'
                        )
                    )),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()->visible(fn () => (
                    $isResponsible() && $this->getOwnerRecord()?->status !== 'valide'
                )),
            ])
            ->modifyQueryUsing(function ($query) use ($userRoles, $privileged) {
                $authUser = Auth::user();
                if (! $authUser) {
                    return $query->whereRaw('0 = 1');
                }
                // Permettre à 'Validateur special' de voir tous les rapports comme un user 'Simple'
                if (in_array('Valider demande', $userRoles) || in_array('Validateur special', $userRoles)) {
                    return $query;
                }
                if (count($userRoles) === 1 && $userRoles[0] === 'Simple') {
                    return $query;
                }

                if (! empty(array_intersect($userRoles, $privileged))) {
                    return $query;
                }

                $typeIds = \App\Models\TypeRapport::whereIn('name', $userRoles)->pluck('id')->toArray();
                if (! empty($typeIds)) {
                    $query->whereIn('type_rapport_id', $typeIds);
                } else {
                    $query->whereRaw('0 = 1');
                }

                return $query;
            });

        return $table;
    }

    public function getDefaultActionAuthorizationResponse(Actions\Action $action): ?Response
    {
        // Prevent deletion of rapports with valide statut
        if ($action instanceof Actions\DeleteAction) {
            $record = $action->getRecord();
            if ($record && $record->statut === 'valide') {
                return Response::deny('Les rapports validés ne peuvent pas être supprimés.');
            }
            
            // Prevent deletion if mission is validated
            $owner = $this->getOwnerRecord();
            if ($owner?->status === 'valide') {
                return Response::deny('Les rapports d\'une mission validée ne peuvent pas être supprimés.');
            }
        }

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
            'Valide demande', 'Autre',
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
            'Valider demande', 'Valide demande', 'Autre',
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
            'Valider demande', 'Valide demande', 'Autre',
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
