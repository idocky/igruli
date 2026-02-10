<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lobby extends Model
{
    /** @use HasFactory<\Database\Factories\LobbyFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
    ];

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
