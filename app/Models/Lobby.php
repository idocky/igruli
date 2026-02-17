<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string|null $title
 * @property string $code
 * @property mixed $players
 * @property mixed $user_id
 */
class Lobby extends Model
{
    /** @use HasFactory<\Database\Factories\LobbyFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'user_id',
        'guest_id',
        'game',
        'started_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
        ];
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function players(): HasMany
    {
        return $this->hasMany(LobbyPlayer::class, 'lobby_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'lobby_id');
    }

    public function ensureDefaultTeams(int $count = 2, ?int $defaultMaxPlayers = null): void
    {
        if ($this->teams()->exists()) {
            return;
        }

        $teams = [];

        for ($i = 1; $i <= $count; $i++) {
            $teams[] = [
                'number' => $i,
                'name' => "Команда {$i}",
                'max_players' => $defaultMaxPlayers,
            ];
        }

        $this->teams()->createMany($teams);
    }
}
