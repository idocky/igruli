<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lobbies\LobbyCreateRequest;
use App\Http\Requests\Lobbies\LobbyJoinRequest;
use App\Models\Lobby;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LobbyController extends Controller
{
    public function show(Lobby $lobby): Response
    {

        return Inertia::render('Lobby', [
            'lobby' => [
                'id' => $lobby->id,
                'title' => $lobby->title,
                'code' => $lobby->code,
            ],
        ]);
    }

    public function store(LobbyCreateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $lobby = Lobby::query()->create([
            'title' => $validated['title'],
            'code' => Lobby::generateUniqueCode(),
        ]);

        return redirect()->route('lobby.show', $lobby);
    }

    public function join(Lobby $lobby, LobbyJoinRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $userId = uniqid('guest_', true);

        $request->session()->put('lobby_guest', [
            'user_id' => $userId,
            'username' => $validated['username'],
            'team' => $validated['team'],
            'lobby_code' => $lobby->code,
        ]);

        return response()->json([
            'user_id' => $userId,
            'username' => $validated['username'],
            'team' => $validated['team'],
        ]);
    }
}
