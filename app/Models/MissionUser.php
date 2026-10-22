<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionUser extends Pivot
{
    use HasFactory;

    protected $table = 'mission_users';

    protected $fillable = [
        'mission_id',
        'user_id',
        'scope',
        'role_mission',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
