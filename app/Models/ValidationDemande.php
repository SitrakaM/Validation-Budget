<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationDemande extends Pivot
{
    use HasFactory;
    protected $table = 'validation_demandes'; // Ou autre nom de ta table pivot
    protected $casts = [
        'motifRetour' => 'array'
    ];
    protected $fillable=[
        "estValid",
        "commentaire",
        "motifRetour",
        "demande_id",
        "user_id",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class);
    }
    protected static function booted(){
        static::saved(function($validation){
            $demande = $validation->demande;
            if($demande->isFullyValidated()){
                $demande->update(['statut'=>'valide']);
            }
        });
    }

}
