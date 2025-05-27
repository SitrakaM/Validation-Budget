<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poste extends Model
{
 use HasFactory;
 protected $fillable=[
     "nomPoste"
 ];
 public function user()
 {

     return $this->hasMany(User::class);

 }
}
