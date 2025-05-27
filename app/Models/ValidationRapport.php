<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationRapport extends Pivot
{
    use HasFactory;
    protected $table = 'validation_rapports'; // Ou autre nom de ta table pivot

    protected $casts = [
        'motifRetour' => 'array'
    ];
    protected $fillable=[
        "estValid",
        "commentaire",
        "motifRetour",
        "rapport_id",
        "user_id",
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(Rapport::class);
    }
    protected static function booted(){
        static::saved(function($validation){
            $rapport = $validation->rapport;
            if($rapport->isFullyValidated()){
                $rapport->update(['statut'=>'valide']);
                if($rapport->demande->isFullyValidated()){
                    // dd('ok');
                    $rapport->demande->update(['statut'=>'valide']);
                }
            }
        });
    }
}
