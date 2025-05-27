<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;
    protected $fillable=[
        "nomSite"
    ];
    public function user(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    public function demande()
    {
   
        return $this->hasMany(Demande::class);
   
    }
    public function rapport()
    {
   
        return $this->hasMany(Rapport::class);
   
    }
}
