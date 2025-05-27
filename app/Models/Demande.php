<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Demande extends Model
{

    use HasFactory;
    protected $casts = [
        'url' => 'array',
        'motifSpecial' => 'array'
    ];
    protected $fillable=[
        "titre",
        "url",
        "motifSpecial",
        "statut",
        "sortie",
        "objet_demande_id",
        "user_id",
        "activite_id",
        "site_id"
    ];


    public function objetDemande()
    {

        return $this->BelongsTo(ObjetDemande::class);

    }
    public function activite()
    {

        return $this->BelongsTo(Activite::class);

    }
    public function user()
    {
   
        return $this->BelongsTo(User::class);
   
    }
    public function site()
    {
   
        return $this->BelongsTo(Site::class);
   
    }
    public function rapport()
    {
   
        return $this->hasMany(Rapport::class);
   
    }
    
    public function userValidationDemande()
    {
        return $this->belongsToMany(User::class,'validation_demandes')
                    ->using(ValidationDemande::class)
                    ->withPivot('estValid')
                    ->withTimestamps();
    }

    public function isFullyValidated():bool{
        $rapportsValides = $this->rapport()->where('statut','!=','valide')->count()===0;
        $validateursOnValideAttente = $this->userValidationDemande()->where('estValid',['en_attente'])->count()===0;
        $validateursOnValideRevision = $this->userValidationDemande()->where('estValid',['revision'])->count()===0;
        return $rapportsValides && $validateursOnValideAttente && $validateursOnValideRevision;
        ;
    }

    protected static function booted(){
        static::updated(function($demande){

            if($demande->wasChanged('statut') && $demande->statut === 'valide'){
                $demande->userValidationDemande()->detach();
                foreach($demande->rapport as $rapport){
                    if($rapport->statut !== 'valide'){
                        $rapport->update(['statut'=>'valide']);
                    }
                    $rapport->userValidationRapport()->detach();
                }
            }
           
        });
    }
}
