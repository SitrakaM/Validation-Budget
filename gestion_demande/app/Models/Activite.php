<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activite extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class, 'mission_activites');
    }

    public function demandes(): BelongsToMany
    {
        return $this->belongsToMany(Demande::class, 'demande_activites');
    }
}
