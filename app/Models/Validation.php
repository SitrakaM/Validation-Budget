<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Rapport;
use App\Models\Demande;

class Validation extends Model
{
    use HasFactory;

    protected $table = 'validations';

    protected $fillable = [
        'validable_type',
        'validable_id',
        'id_validateur',
        'decision',
        'commentaire',
        'fichier_commentaire',
        'validated_at',
    ];

    protected $casts = [
        'fichier_commentaire' => 'array',
        'validated_at' => 'datetime',
    ];

    public const DECISION_VALID = 'valide';
    public const DECISION_CORRECTION = 'correction';
    public const DECISION_REJECT = 'rejete';
    public const DECISION_PENDING = 'en_cours';

    public function validable(): MorphTo
    {
        return $this->morphTo();
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_validateur');
    }

    /**
     * Apply this decision to the validated model (Demande/Rapport/Mission).
     */
    public function applyDecision(): void
    {
        $this->validated_at = $this->validated_at ?? now();
        $this->save();

        $model = $this->validable;
        if (! $model) {
            return;
        }

        // Cas spécial : validateur spécial force la validation immédiate d'une Demande
        // Suppression de la logique validateur spécial (déléguée à Demande)

        // Get all decisions for this model
        $decisions = $model->validations()->pluck('decision')->toArray();

        // If any validator rejected, overall is 'refuse'
        if (in_array(self::DECISION_REJECT, $decisions, true)) {
            $model->statut = 'refuse';
            $model->save();
            // If it's a rapport that became refused, update linked demandes
            if ($model instanceof Rapport) {
                $this->revalidateDemandes($model);
            }
            return;
        }

        // If any validator requested correction, set model to 'correction'
        if (in_array(self::DECISION_CORRECTION, $decisions, true)) {
            $model->statut = 'correction';
            $model->save();
            // If it's a rapport that got correction, update linked demandes
            if ($model instanceof Rapport) {
                $this->revalidateDemandes($model);
            }
            return;
        }

        // Check if all validators have decided (no pending decisions)
        if ($model instanceof Demande) {
            // For Demande: get all required validators (those with 'Valider demande' role)
            $requiredValidators = User::whereHas('roles', fn($q) => $q->where('name', 'Valider demande'))->pluck('id')->toArray();
            $requiredCount = count($requiredValidators);
            $validationsCount = $model->validations()
                ->where('decision', self::DECISION_VALID)
                ->whereIn('id_validateur', $requiredValidators)
                ->distinct('id_validateur')
                ->count('id_validateur');

            if ($requiredCount > 0 && $validationsCount >= $requiredCount) {
                // All required validators approved
                if ($this->allMissionRapportsAreValid($model)) {
                    $model->statut = 'valide';
                } else {
                    $model->statut = 'en attente des rapports';
                }
                $model->save();
            } else {
                // Some validators still pending
                $model->statut = 'en_cours';
                $model->save();
            }
        } else if ($model instanceof Rapport) {
            // For Rapport: get all required validators (those with privileged roles or matching type role)
            $privilegedRoles = ['Validateur', 'ValidateurRapport', 'Special', 'Budget', 'Valide demande', 'Autre'];
            $typeRole = $model->typeRapport?->name;
            
            // Get users with privileged roles
            $privilegedValidators = User::whereHas('roles', fn($q) => 
                $q->whereIn('name', $privilegedRoles)
            )->pluck('id')->toArray();
            
            // Get users with type role
            $typeValidators = [];
            if ($typeRole) {
                $typeValidators = User::whereHas('roles', fn($q) => 
                    $q->where('name', $typeRole)
                )->pluck('id')->toArray();
            }
            
            // Combine and unique
            $requiredValidators = array_unique(array_merge($privilegedValidators, $typeValidators));
            $requiredCount = count($requiredValidators);
            $validationsCount = $model->validations()
                ->where('decision', self::DECISION_VALID)
                ->whereIn('id_validateur', $requiredValidators)
                ->distinct('id_validateur')
                ->count('id_validateur');

            if ($requiredCount > 0 && $validationsCount >= $requiredCount) {
                // All required validators approved
                $model->statut = 'valide';
                $model->save();
                // Revalidate linked demandes
                $this->revalidateDemandes($model);
            } else {
                // Some validators still pending
                $model->statut = 'en_cours';
                $model->save();
            }
        }
    }

    /**
     * Check if ALL rapports linked to a demande's mission are validated.
     *
     * Important: any rapport in 'non_soumis' should block the Demande from
     * becoming 'valide' — the Demande must wait for all rapports to be
     * submitted and validated.
     */
    private function allMissionRapportsAreValid(Demande $demande): bool
    {
        if (! $demande->mission_id) {
            return true; // No mission, consider it valid
        }

        // Count rapports for the mission that are not yet 'valide'. This includes
        // rapports in 'non_soumis', 'en_cours', 'correction', 'refuse', etc.
        $notValidatedCount = Rapport::where('mission_id', $demande->mission_id)
            ->where('statut', '!=', 'valide')
            ->count();

        // Only when there are zero non-valid rapports do we consider all rapports valid
        return $notValidatedCount === 0;
    }

    /**
     * Revalidate all demandes linked to a rapport's mission when rapport status changes
     */
    private function revalidateDemandes(Rapport $rapport): void
    {
        if (! $rapport->mission_id) {
            return;
        }

        $demandes = Demande::where('mission_id', $rapport->mission_id)->get();

        foreach ($demandes as $demande) {
            // Get all required validators for this demande (those with 'Valider demande' role)
            $requiredValidators = User::whereHas('roles', fn($q) => $q->where('name', 'Valider demande'))->pluck('id')->toArray();
            $requiredCount = count($requiredValidators);
            $validationsCount = $demande->validations()
                ->where('decision', self::DECISION_VALID)
                ->whereIn('id_validateur', $requiredValidators)
                ->distinct('id_validateur')
                ->count('id_validateur');

            // If all required validators approved
            if ($requiredCount > 0 && $validationsCount >= $requiredCount) {
                // Check if all rapports for this mission are valid
                $allSubmittedRapportsValid = $this->allMissionRapportsAreValid($demande);
                
                if ($allSubmittedRapportsValid) {
                    // All validators approved AND all rapports are valid
                    $demande->statut = 'valide';
                } else {
                    // All validators approved but some rapports are not yet valid
                    $demande->statut = 'en attente des rapports';
                }
                
                $demande->save();
            }
        }
    }

    public static function record(Model $model, User $user, string $decision, ?string $comment = null, array $files = null, bool $isSpecial = false, $motifSpecial = null): self
    {
        $validation = self::create([
            'validable_type' => get_class($model),
            'validable_id' => $model->id,
            'id_validateur' => $user->id,
            'decision' => $decision,
            'commentaire' => $comment,
            'fichier_commentaire' => $files,
            'validated_at' => now(),
        ]);

        $validation->applyDecision();

        return $validation;
    }
}
