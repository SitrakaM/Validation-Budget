<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function site(): BelongsToMany
    {
        return $this->belongsToMany(Site::class);
    }

    public function poste()
    {

        return $this->BelongsTo(Poste::class);

    }
    public function role()
    {

        return $this->BelongsTo(Role::class);

    }
    public function demande()
    {
   
        return $this->hasMany(Demande::class);
   
    }
    public function demandeValidation()
    {
        return $this->belongsToMany(Demande::class,'validation_demandes')
                    ->using(ValidationDemande::class)
                    ->withPivot('estValid')
                    ->withTimestamps();
    }
    public function rapportValidation()
    {
        return $this->belongsToMany(Rapport::class, 'validation_rapports')
                    ->using(ValidationRapport::class)
                    ->withPivot('estValid')
                    ->withTimestamps();
    }
    public function canAccessPanel(Panel $panel):bool{
        return true;
    }
}
