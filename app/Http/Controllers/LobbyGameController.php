<?php

namespace App\Http\Controllers;

use App\Enums\GamesEnum;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LobbyGameController extends Controller
{
    public function show(Request $request, Lobby $lobby, string $game): Response
    {
        abort_unless(in_array($game, GamesEnum::allValues(), true), 404);

        $players = $lobby->players()
            ->orderBy('team')
            ->orderBy('id')
            ->get()
            ->map(function (LobbyPlayer $player): array {
                $userId = $player->guest_id;

                if (! $userId && $player->user_id) {
                    $userId = (string) $player->user_id;
                }

                if (! $userId) {
                    $userId = (string) $player->id;
                }

                return [
                    'userId' => $userId,
                    'username' => $player->username,
                    'team' => $player->team,
                ];
            })
            ->values();

        /** @var array{user_id: string, username: string, team: int, lobby_code: string}|null $guest */
        $guest = $request->session()->get('lobby_guest');

        $currentPlayer = null;

        if ($guest && $guest['lobby_code'] === $lobby->code) {
            $currentPlayer = [
                'userId' => $guest['user_id'],
                'username' => $guest['username'],
                'team' => $guest['team'],
            ];
        } elseif ($request->user()) {
            $player = LobbyPlayer::query()
                ->where('lobby_id', $lobby->id)
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->first();

            if ($player) {
                $currentPlayer = [
                    'userId' => $player->guest_id ?: (string) $player->user_id,
                    'username' => $player->username,
                    'team' => $player->team,
                ];
            }
        }

        $component = match ($game) {
            GamesEnum::DUSHNILA->value => 'games/Dushnila',
            default => abort(404),
        };

        return Inertia::render($component, [
            'lobby' => [
                'id' => $lobby->id,
                'title' => $lobby->title,
                'code' => $lobby->code,
            ],
            'players' => $players,
            'currentPlayer' => $currentPlayer,
            'game' => $game,
        ]);
    }
}
