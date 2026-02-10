<?php

namespace App\Http\Controllers;

use App\Events\UserRemovedFromLobby;
use App\Http\Requests\Lobbies\LobbyCreateRequest;
use App\Http\Requests\Lobbies\LobbyJoinRequest;
use App\Http\Requests\Lobbies\LobbyRemovePlayerRequest;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LobbyController extends Controller
{
    public function show(Request $request, Lobby $lobby): Response
    {
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
        }

        $createdBy = null;
        if ($lobby->guest_id) {
            $createdBy = $lobby->guest_id;
        } elseif ($lobby->user_id) {
            $createdBy = (string) $lobby->user_id;
        }

        $canManagePlayers = false;
        if ($lobby->user_id && $request->user() && (int) $request->user()->getAuthIdentifier() === (int) $lobby->user_id) {
            $canManagePlayers = true;
        } elseif ($lobby->guest_id) {
            /** @var array<string, string> $creators */
            $creators = $request->session()->get('lobby_creators', []);

            $canManagePlayers = ($creators[$lobby->code] ?? null) === $lobby->guest_id;
        }

        return Inertia::render('Lobby', [
            'lobby' => [
                'id' => $lobby->id,
                'title' => $lobby->title,
                'code' => $lobby->code,
                'players' => $players,
                'createdBy' => $createdBy,
                'canManagePlayers' => $canManagePlayers,
            ],
            'currentPlayer' => $currentPlayer,
        ]);
    }

    public function store(LobbyCreateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $guestCreatorId = null;
        if (! auth()->check()) {
            $guestCreatorId = uniqid('creator_', true);
        }

        $lobby = Lobby::query()->create([
            'title' => $validated['title'],
            'code' => Lobby::generateUniqueCode(),
            'user_id' => auth()->id() ?? null,
            'guest_id' => $guestCreatorId,
        ]);

        if ($guestCreatorId) {
            /** @var array<string, string> $creators */
            $creators = $request->session()->get('lobby_creators', []);
            $creators[$lobby->code] = $guestCreatorId;
            $request->session()->put('lobby_creators', $creators);
        }

        return redirect()->route('lobby.show', $lobby);
    }

    public function join(Lobby $lobby, LobbyJoinRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $userId = uniqid('guest_', true);

        $request->session()->put('lobby_guest', [
            'user_id' => $userId,
            'username' => $validated['username'],
            'team' => $validated['team'],
            'lobby_code' => $lobby->code,
        ]);

        LobbyPlayer::create([
            'guest_id' => $userId,
            'username' => $validated['username'],
            'team' => $validated['team'],
            'lobby_id' => $lobby->id,
        ]);

        return redirect()->route('lobby.show', $lobby);
    }

    public function destroyPlayer(LobbyRemovePlayerRequest $request, Lobby $lobby): RedirectResponse
    {
        $canManagePlayers = false;

        if ($lobby->user_id && $request->user() && (int) $request->user()->getAuthIdentifier() === (int) $lobby->user_id) {
            $canManagePlayers = true;
        } elseif ($lobby->guest_id) {
            /** @var array<string, string> $creators */
            $creators = $request->session()->get('lobby_creators', []);
            $canManagePlayers = ($creators[$lobby->code] ?? null) === $lobby->guest_id;
        }

        abort_unless($canManagePlayers, 403);

        $validated = $request->validated();

        $playerToRemove = LobbyPlayer::query()
            ->where('lobby_id', $lobby->id)
            ->where('guest_id', $validated['guest_id'])
            ->firstOrFail();

        $playerToRemove->delete();

        broadcast(new UserRemovedFromLobby(
            userId: $playerToRemove->guest_id,
            lobbyCode: $lobby->code,
        ));

        $guest = $request->session()->get('lobby_guest');
        if ($guest && $guest['lobby_code'] === $lobby->code && $guest['user_id'] === $playerToRemove->guest_id) {
            $request->session()->forget('lobby_guest');
        }

        return redirect()->route('lobby.show', $lobby);
    }
}
