<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $lobbyCode,
        public string $game,
        public string $url,
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('lobby.'.$this->lobbyCode);
    }

    public function broadcastAs(): string
    {
        return 'lobby.started';
    }
}
