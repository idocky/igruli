<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinedLobby implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $username,
        public string $userId,
        public int $team,
        public string $lobbyCode
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('lobby.'.$this->lobbyCode);
    }
}
