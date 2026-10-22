<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Demande extends Model
{
    use HasFactory;

    protected $attributes = [
        'statut' => 'en_attente',
    ];

    protected static function booted(): void
    {
        // Prevent updates/deletes once a demande is validated
        // "en attente des rapports" is a transient state and can be modified (e.g., to become 'valide')
        // EXCEPTION: Allow modification of 'sortie' field for "Responsable sortie" role
        static::updating(function (Demande $demande) {
            $originalStatut = $demande->getOriginal('statut');
            if ($originalStatut === 'valide') {
                // Only allow modification if ONLY the 'sortie' field changed
                $dirtyAttributes = $demande->getDirty();
                if (count($dirtyAttributes) === 1 && isset($dirtyAttributes['sortie'])) {
                    // Allow: only 'sortie' field changed
                    return;
                }
                throw new \Illuminate\Auth\Access\AuthorizationException('Les demandes validées ne peuvent pas être modifiées.');
            }
        });

        static::deleting(function (Demande $demande) {
            if ($demande->statut === 'valide') {
                throw new \Illuminate\Auth\Access\AuthorizationException('Les demandes validées ne peuvent pas être supprimées.');
            }
        });

        static::created(function (Demande $demande) {
            try {
                $validatorRoleName = 'Valider demande';
                $validators = \App\Models\User::whereHas('roles', function ($q) use ($validatorRoleName) {
                    $q->where('name', $validatorRoleName);
                })->get();
                foreach ($validators as $user) {
                    $validation = \App\Models\Validation::firstOrCreate([
                        'validable_type' => get_class($demande),
                        'validable_id' => $demande->id,
                        'id_validateur' => $user->id,
                    ], [
                        'decision' => \App\Models\Validation::DECISION_PENDING,
                        'commentaire' => null,
                        'fichier_commentaire' => null,
                    ]);
                    try {
                        if (method_exists($user, 'notify')) {
                            $user->notify(new \App\Notifications\DemandeAssignedForValidation($demande, $validation));
                        }
                    } catch (\Throwable $e) {
                        // ignore notification failures
                    }
                }
            } catch (\Throwable $e) {
                // swallow, do not block demande creation for minor issues
            }
        });

        static::saving(function ($demande) {
            if ($demande->validated_by_special && !empty($demande->motif_special)) {
                $demande->statut = 'valide';
            }
        });
    }
    // (supprimé doublon HasFactory)


    protected $fillable = [
        'fichier',
        'statut',
        'validated_by_special',
        'motif_special',
        'type_demande_id',
        'site_id',
        'mission_id',
        'sortie',
    ];

    protected $casts = [
        'fichier' => 'array',
        'validated_by_special' => 'boolean',
        'motif_special' => 'string',
        'sortie' => 'boolean',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function typeDemande(): BelongsTo
    {
        return $this->belongsTo(TypeDemande::class);
    }

    public function activites(): BelongsToMany
    {
        return $this->belongsToMany(Activite::class, 'demande_activites');
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(Validation::class, 'validable');
    }

    // (supprimé : double déclaration booted)
}
