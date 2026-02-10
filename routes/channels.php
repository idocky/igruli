<?php

use App\Broadcasting\GuestBroadcastUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('lobby.{code}', function (Authenticatable $user, string $code) {
    if ($user instanceof GuestBroadcastUser && $user->lobbyCode === $code) {
        return [
            'id' => $user->getAuthIdentifier(),
            'username' => $user->username,
            'team' => $user->team,
        ];
    }

    return false;
});
