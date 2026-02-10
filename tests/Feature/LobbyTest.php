<?php

use App\Events\UserRemovedFromLobby;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Support\Facades\Event;

test('dashboard page renders successfully', function () {
    $this->get(route('home'))->assertOk();
});

test('dashboard shows existing lobbies', function () {
    $lobby = Lobby::factory()->create(['title' => 'Test Lobby']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('lobbies', 1)
        );
});

test('guest can create a lobby', function () {
    $response = $this->post(route('lobby.store'), [
        'title' => 'My Lobby',
    ]);

    $lobby = Lobby::query()->first();
    /** @var array<string, string>|null $creators */
    $creators = session('lobby_creators');

    expect($lobby)->not->toBeNull()
        ->and($lobby->title)->toBe('My Lobby')
        ->and($lobby->code)->toHaveLength(6)
        ->and($lobby->guest_id)->not->toBeNull()
        ->and($creators)->not->toBeNull()
        ->and($creators[$lobby->code] ?? null)->toBe($lobby->guest_id);

    $response->assertRedirect(route('lobby.show', $lobby));
});

test('lobby page renders successfully', function () {
    $lobby = Lobby::factory()->create();

    $this->get(route('lobby.show', $lobby))->assertOk();
});

test('lobby page includes existing players in inertia props', function () {
    $lobby = Lobby::factory()->create();

    LobbyPlayer::factory()->for($lobby)->create([
        'username' => 'Alice',
        'team' => 1,
        'guest_id' => 'guest_alice',
    ]);

    LobbyPlayer::factory()->for($lobby)->create([
        'username' => 'Bob',
        'team' => 2,
        'guest_id' => 'guest_bob',
    ]);

    $this->get(route('lobby.show', $lobby))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Lobby')
            ->has('lobby.players', 2)
            ->where('lobby.players.0', [
                'userId' => 'guest_alice',
                'username' => 'Alice',
                'team' => 1,
            ])
            ->where('lobby.players.1', [
                'userId' => 'guest_bob',
                'username' => 'Bob',
                'team' => 2,
            ])
        );
});

test('lobby page includes current player when guest session matches lobby', function () {
    $lobby = Lobby::factory()->create();

    $this->withSession([
        'lobby_guest' => [
            'user_id' => 'guest_test',
            'username' => 'TestPlayer',
            'team' => 1,
            'lobby_code' => $lobby->code,
        ],
    ])->get(route('lobby.show', $lobby))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Lobby')
            ->where('currentPlayer', [
                'userId' => 'guest_test',
                'username' => 'TestPlayer',
                'team' => 1,
            ])
        );
});

test('lobby code is unique', function () {
    $lobby1 = Lobby::factory()->create();
    $lobby2 = Lobby::factory()->create();

    expect($lobby1->code)->not->toBe($lobby2->code);
});

test('user can join a team in a lobby', function () {
    $lobby = Lobby::factory()->create();

    $response = $this->post(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
        'team' => 1,
    ]);

    $response->assertRedirect(route('lobby.show', $lobby));

    expect(LobbyPlayer::query()
        ->where('lobby_id', $lobby->id)
        ->where('username', 'TestPlayer')
        ->where('team', 1)
        ->exists()
    )->toBeTrue();
});

test('join stores guest info in session', function () {
    $lobby = Lobby::factory()->create();

    $this->post(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
        'team' => 1,
    ])->assertRedirect(route('lobby.show', $lobby));

    $guest = session('lobby_guest');

    expect($guest)->not->toBeNull()
        ->and($guest['username'])->toBe('TestPlayer')
        ->and($guest['team'])->toBe(1)
        ->and($guest['lobby_code'])->toBe($lobby->code)
        ->and($guest['user_id'])->toBeString();
});

test('user can join team 2', function () {
    $lobby = Lobby::factory()->create();

    $response = $this->post(route('lobby.join', $lobby), [
        'username' => 'Player2',
        'team' => 2,
    ]);

    $response->assertRedirect(route('lobby.show', $lobby));

    expect(LobbyPlayer::query()
        ->where('lobby_id', $lobby->id)
        ->where('username', 'Player2')
        ->where('team', 2)
        ->exists()
    )->toBeTrue();
});

test('join requires username', function () {
    $lobby = Lobby::factory()->create();

    $this->post(route('lobby.join', $lobby), [
        'team' => 1,
    ])->assertSessionHasErrors(['username']);
});

test('join requires valid team', function () {
    $lobby = Lobby::factory()->create();

    $this->post(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
        'team' => 3,
    ])->assertSessionHasErrors(['team']);
});

test('join requires team field', function () {
    $lobby = Lobby::factory()->create();

    $this->post(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
    ])->assertSessionHasErrors(['team']);
});

test('username max length is 30 characters', function () {
    $lobby = Lobby::factory()->create();

    $this->post(route('lobby.join', $lobby), [
        'username' => str_repeat('a', 31),
        'team' => 1,
    ])->assertSessionHasErrors(['username']);
});

test('create lobby requires title', function () {
    $this->postJson(route('lobby.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

test('lobby creator can remove a player and broadcasts an event', function () {
    Event::fake([UserRemovedFromLobby::class]);

    $this->post(route('lobby.store'), [
        'title' => 'My Lobby',
    ])->assertRedirect();

    $lobby = Lobby::query()->firstOrFail();

    $player = LobbyPlayer::factory()->for($lobby)->create([
        'guest_id' => 'guest_to_remove',
    ]);

    $this->delete(route('lobby.players.destroy', $lobby), [
        'guest_id' => $player->guest_id,
    ])->assertRedirect(route('lobby.show', $lobby));

    expect(LobbyPlayer::query()->whereKey($player->id)->exists())->toBeFalse();

    Event::assertDispatched(UserRemovedFromLobby::class, function (UserRemovedFromLobby $event) use ($player, $lobby) {
        return $event->userId === $player->guest_id && $event->lobbyCode === $lobby->code;
    });
});

test('non creator cannot remove players', function () {
    $lobby = Lobby::factory()->create([
        'guest_id' => 'creator_guest_id',
        'user_id' => null,
    ]);

    $player = LobbyPlayer::factory()->for($lobby)->create([
        'guest_id' => 'guest_to_remove',
    ]);

    $this->delete(route('lobby.players.destroy', $lobby), [
        'guest_id' => $player->guest_id,
    ])->assertForbidden();
});
