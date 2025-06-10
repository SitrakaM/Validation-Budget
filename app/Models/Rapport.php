<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Rapport extends Model
{


    use HasFactory;
    protected $casts = [
        'url' => 'array',
    ];
    protected $fillable=[
        "titre",
        "statut",
        "url",
        "objet_rapport_id",
        "demande_id",
        "activite_id",
        "site_id"
    ];




    public function objetRapport()
    {

        return $this->BelongsTo(ObjetRapport::class);

    }
    public function activite()
    {

        return $this->BelongsTo(Activite::class);

    }
    public function demande()
    {

        return $this->BelongsTo(Demande::class);

    }
    public function motifRetour()
    {
   
        return $this->hasMany(MotifRetour::class);
   
    }
    public function urlRapport()
    {
   
        return $this->hasMany(UrlRapport::class);
   
    }
    public function site()
    {
   
        return $this->BelongsTo(Site::class);
   
    }

    public function userValidationRapport()
    {
        return $this->belongsToMany(User::class,'validation_rapports')
                    ->using(ValidationRapport::class)
                    ->withPivot('estValid')
                    ->withTimestamps();
    }

    protected static function booted(){
        static::created(function($rapport){
            $user = Auth::user();
            if (!$user || in_array($user->role->nomRole, ['Budget'])) {
                return; // on ne fait rien si admin ou budget
            }
            $validateurs = User::whereHas('role',fn($q)=>$q->whereIn('nomRole',['ValidateurRapport']))->get();

            foreach ($validateurs as $validateur) {
                $rapport->userValidationRapport()->attach($validateur->id);
            }


        });
        static::updated(function($rapport){
            $user = Auth::user();
            if (!$user || in_array($user->role->nomRole, ['Budget'])) {
                return; // on ne fait rien si admin ou budget
            }

            if ($rapport->wasChanged()) {
                // On parcourt chaque enregistrement de la table pivot
                foreach ($rapport->userValidationRapport as $validation) {
                    if ($validation->pivot->estValid === 'revision') {
                        $validation->pivot->estValid = 'changer';
                        $validation->pivot->save();
                    }
                }
            }
            


        });
    }
    public function isFullyValidated():bool{
        // return 
        // $this->userValidationRapport()->where('estValid',['en_attente','revision'])->count()===0

        $validateursOnValideAttente = $this->userValidationRapport()->where('estValid',['en_attente'])->count()===0;
        $validateursOnValideRevision = $this->userValidationRapport()->where('estValid',['revision'])->count()===0;
        $validateursOnValideChanger = $this->userValidationRapport()->where('estValid',['changer'])->count()===0;

        return $validateursOnValideAttente && $validateursOnValideRevision && $validateursOnValideChanger;
        ;
    }
 
}
