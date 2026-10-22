<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Role;

class TypeRapport extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function rapports(): HasMany
    {
        return $this->hasMany(Rapport::class);
    }

    protected static function booted(): void
    {
        static::created(function (TypeRapport $typeRapport) {
            Role::firstOrCreate([
                'name' => $typeRapport->name,
            ]);
        });

        static::updated(function (TypeRapport $typeRapport) {
            $original = $typeRapport->getOriginal('name');
            $current = $typeRapport->name;

            if ($original && $original !== $current) {
                $role = Role::where('name', $original)->first();
                if ($role) {
                    $role->name = $current;
                    $role->save();
                }
            }
        });

        static::deleted(function (TypeRapport $typeRapport) {
            $role = Role::where('name', $typeRapport->name)->first();
            if ($role) {
                $role->delete();
            }
        });
    }
}
