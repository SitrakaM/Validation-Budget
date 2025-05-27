<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotifRetour extends Model
{
    use HasFactory;
    protected $fillable=[
        "url",
        "rapport_id"
    ];
    public function rapport()
    {

        return $this->BelongsTo(Rapport::class);

    }
}
