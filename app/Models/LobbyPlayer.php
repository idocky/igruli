<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LobbyPlayer extends Model
{
    /** @use HasFactory<\Database\Factories\LobbyPlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_id',
        'username',
        'team',
        'lobby_id',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(Lobby::class);
    }
}
