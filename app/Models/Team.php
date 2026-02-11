<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'lobby_id',
        'number',
        'name',
        'max_players',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(Lobby::class);
    }
}
