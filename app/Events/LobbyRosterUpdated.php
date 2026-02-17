<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyRosterUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, array{userId: string, username: string, team: int}>  $players
     */
    public function __construct(
        public string $lobbyCode,
        public array $players,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('lobby.'.$this->lobbyCode.'.public');
    }

    public function broadcastAs(): string
    {
        return 'lobby.roster-updated';
    }
}
