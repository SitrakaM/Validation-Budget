<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activite extends Model
{
    use HasFactory;
    protected $fillable=[
        "nomActivite"
    ];
    public function demande()
    {
   
        return $this->hasMany(Demande::class);
   
    }
    public function rapport()
    {
   
        return $this->hasMany(Rapport::class);
   
    }
}
