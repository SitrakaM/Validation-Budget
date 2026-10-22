<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;

class Rapport extends Model
{
    use HasFactory;

    protected $attributes = [
        'statut' => 'non_soumis',
    ];

    protected $fillable = [
        'fichier',
        'statut',
        'physical',
        'type_rapport_id',
        'mission_id',
    ];

    protected $casts = [
        'fichier' => 'array',
        'physical' => 'boolean',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function typeRapport(): BelongsTo
    {
        return $this->belongsTo(TypeRapport::class);
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(Validation::class, 'validable');
    }

    protected static function booted(): void
    {
        static::saving(function (Rapport $rapport) {
            // Determine if report should move from 'non_soumis' to 'en_attente'.
            $hasFichier = !empty(Arr::wrap($rapport->fichier));
            $hasTypeAndMission = (bool) ($rapport->type_rapport_id && $rapport->mission_id);
            $isPhysical = (bool) $rapport->physical;

            // If physical is true OR (type+mission and fichier present), set to en_attente
            $shouldBeEnAttente = $isPhysical || ($hasTypeAndMission && $hasFichier);

            if ($shouldBeEnAttente) {
                if ($rapport->statut === 'non_soumis' || empty($rapport->statut)) {
                    $rapport->statut = 'en_attente';
                }
            }

            // If a previously submitted fichier was removed, revert to 'non_soumis'
            // only when physical is not true.
            $originalFichier = Arr::wrap($rapport->getOriginal('fichier'));
            $originalHasFichier = !empty($originalFichier);
            if ($originalHasFichier && !$hasFichier && !$isPhysical) {
                $rapport->statut = 'non_soumis';
            }

            // If physical was previously true, make the setting irreversible:
            // once physical is true it cannot be set back to false. Enforce server-side
            // by preserving the original true value if an attempt is made to set it false.
            $originalPhysical = (bool) $rapport->getOriginal('physical');
            if ($originalPhysical && !$isPhysical) {
                // Preserve the original true value
                $rapport->physical = true;
                // If there is no fichier and physical forced true, ensure status is en_attente
                if (! $hasFichier) {
                    $rapport->statut = 'en_attente';
                }
            }
        });
        static::created(function (Rapport $rapport) {
            // Attach initial validations for users whose role matches the TypeRapport name
            try {
                $typeName = $rapport->typeRapport?->name;

                if (! $typeName) {
                    return;
                }

                $exceptions = config('rapport.validation_exceptions', []);

                $validators = \App\Models\User::whereHas('roles', function ($q) use ($typeName, $exceptions) {
                    $q->where('name', $typeName);
                    if (! empty($exceptions)) {
                        $q->whereNotIn('name', $exceptions);
                    }
                })->get();

                foreach ($validators as $user) {
                    // Create a validation record in pending state for each validator
                    $validation = \App\Models\Validation::firstOrCreate([
                        'validable_type' => get_class($rapport),
                        'validable_id' => $rapport->id,
                        'id_validateur' => $user->id,
                    ], [
                        'decision' => \App\Models\Validation::DECISION_PENDING,
                        'commentaire' => null,
                        'fichier_commentaire' => null,
                    ]);

                    try {
                        if (method_exists($user, 'notify')) {
                            $user->notify(new \App\Notifications\RapportAssignedForValidation($rapport, $validation));
                        }
                    } catch (\Throwable $e) {
                        // ignore notification failures
                    }
                }
            } catch (\Throwable $e) {
                // swallow, do not block report creation for minor issues
            }
        });
    }
}
