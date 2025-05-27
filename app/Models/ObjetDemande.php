<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjetDemande extends Model
{
    use HasFactory;
    protected $fillable=[
        "nomObjet"
    ];
    public function demande()
    {
   
        return $this->hasMany(Demande::class);
   
    }
}
