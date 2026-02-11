<?php

namespace App\Http\Controllers;

use App\Enums\GamesEnum;
use App\Events\UserRemovedFromLobby;
use App\Http\Requests\Lobbies\LobbyCreateRequest;
use App\Http\Requests\Lobbies\LobbyJoinRequest;
use App\Http\Requests\Lobbies\LobbyRemovePlayerRequest;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LobbyController extends Controller
{
    /**
     * @return array{title: string, team_max_size: int|null, max_teams: int}
     */
    private function gameInfo(): array
    {
        return [
            'title' => GamesEnum::DUSHNILA->value,
            'team_max_size' => 5,
            'max_teams' => 1,
        ];
    }

    public function show(Request $request, Lobby $lobby): Response
    {
        $gameInfo = $this->gameInfo();
        $maxTeams = max(1, (int) $gameInfo['max_teams']);
        $defaultTeamsCount = min(2, $maxTeams);

        $lobby->ensureDefaultTeams(count: $defaultTeamsCount, defaultMaxPlayers: $gameInfo['team_max_size']);

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

        $teams = $lobby->teams()
            ->orderBy('number')
            ->get()
            ->map(function (Team $team) use ($players): array {
                return [
                    'number' => $team->number,
                    'name' => $team->name ?? "Команда {$team->number}",
                    'maxPlayers' => $team->max_players,
                    'players' => $players->where('team', $team->number)->values(),
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
                'teams' => $teams,
                'createdBy' => $createdBy,
                'canManagePlayers' => $canManagePlayers,
            ],
            'gameInfo' => $gameInfo,
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

        $gameInfo = $this->gameInfo();
        $maxTeams = max(1, (int) $gameInfo['max_teams']);
        $defaultTeamsCount = min(2, $maxTeams);

        $lobby->ensureDefaultTeams(count: $defaultTeamsCount, defaultMaxPlayers: $gameInfo['team_max_size']);

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

        $team = $lobby->teams()->where('number', $validated['team'])->firstOrFail();

        if ($team->max_players !== null) {
            $playersInTeam = $lobby->players()->where('team', $team->number)->count();

            if ($playersInTeam >= $team->max_players) {
                return back()->withErrors([
                    'team' => 'Эта команда уже заполнена',
                ]);
            }
        }

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

    public function storeTeam(Lobby $lobby): RedirectResponse
    {
        $gameInfo = $this->gameInfo();
        $maxTeams = max(1, (int) $gameInfo['max_teams']);
        $defaultTeamsCount = min(2, $maxTeams);

        $lobby->ensureDefaultTeams(count: $defaultTeamsCount, defaultMaxPlayers: $gameInfo['team_max_size']);

        $currentTeamsCount = $lobby->teams()->count();
        if ($currentTeamsCount >= $maxTeams) {
            return back()->withErrors([
                'teams' => 'Достигнут лимит команд для этой игры',
            ]);
        }

        $nextNumber = (int) $lobby->teams()->max('number') + 1;

        $lobby->teams()->create([
            'number' => $nextNumber,
            'name' => "Команда {$nextNumber}",
            'max_players' => $gameInfo['team_max_size'],
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
