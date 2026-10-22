<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\TypeRapport;
use App\Models\Rapport;
use Illuminate\Support\Facades\Auth;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = ['description', 'site_id', 'status'];

    protected $attributes = [
        'status' => 'en_attente',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }

    public function rapports(): HasMany
    {
        return $this->hasMany(Rapport::class);
    }

    public function activites(): BelongsToMany
    {
        return $this->belongsToMany(Activite::class, 'mission_activites');
    }

    public function users(): BelongsToMany
    {
            return $this->belongsToMany(
                User::class,
                'mission_users'
            )
            ->withPivot(['scope', 'role_mission', 'added_at'])
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::created(function (Mission $mission) {
            // Create a Rapport for each existing TypeRapport for this mission
            $types = TypeRapport::all();
            foreach ($types as $type) {
                Rapport::firstOrCreate([
                    'mission_id' => $mission->id,
                    'type_rapport_id' => $type->id,
                ], [
                    'statut' => 'non_soumis',
                    'physical' => false,
                ]);
            }
            // Attach the creating user to the mission so 'Simple' users can see it
            $user = Auth::user();
            if ($user) {
                $mission->users()->syncWithoutDetaching([
                    $user->id => [
                        'scope' => 'both',
                        'role_mission' => 'responsable',
                        'added_at' => now(),
                    ],
                ]);
            }
        });
    }
}
