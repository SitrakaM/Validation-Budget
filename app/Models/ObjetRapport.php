<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjetRapport extends Model
{
    use HasFactory;
    protected $fillable=[
        "nomObjet"
    ];
    public function rapport()
    {
   
        return $this->hasMany(Rapport::class);
   
    }
}
