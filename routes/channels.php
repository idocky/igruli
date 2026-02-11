<?php

use App\Broadcasting\GuestBroadcastUser;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use App\Models\User;
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

    if ($user instanceof User) {
        $lobby = Lobby::query()->where('code', $code)->first();
        if (! $lobby) {
            return false;
        }

        $player = LobbyPlayer::query()
            ->where('lobby_id', $lobby->id)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if (! $player) {
            return false;
        }

        return [
            'id' => $player->guest_id ?: 'user_'.$user->getAuthIdentifier(),
            'username' => $player->username,
            'team' => $player->team,
        ];
    }

    return false;
});
