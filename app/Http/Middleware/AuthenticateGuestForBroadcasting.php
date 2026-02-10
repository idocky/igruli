<?php

namespace App\Http\Middleware;

use App\Broadcasting\GuestBroadcastUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateGuestForBroadcasting
{
    /**
     * Resolve a guest broadcast user from session when no authenticated user exists.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() && $request->is('broadcasting/auth')) {
            /** @var array{user_id: string, username: string, team: int, lobby_code: string}|null $guest */
            $guest = $request->session()->get('lobby_guest');

            if ($guest) {
                $guestUser = new GuestBroadcastUser(
                    id: $guest['user_id'],
                    username: $guest['username'],
                    team: $guest['team'],
                    lobbyCode: $guest['lobby_code'],
                );

                $request->setUserResolver(fn () => $guestUser);
            }
        }

        return $next($request);
    }
}
