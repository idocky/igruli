<?php

use App\Models\Lobby;

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

    expect($lobby)->not->toBeNull()
        ->and($lobby->title)->toBe('My Lobby')
        ->and($lobby->code)->toHaveLength(6);

    $response->assertRedirect(route('lobby.show', $lobby));
});

test('lobby page renders successfully', function () {
    $lobby = Lobby::factory()->create();

    $this->get(route('lobby.show', $lobby))->assertOk();
});

test('lobby code is unique', function () {
    $lobby1 = Lobby::factory()->create();
    $lobby2 = Lobby::factory()->create();

    expect($lobby1->code)->not->toBe($lobby2->code);
});

test('user can join a team in a lobby', function () {
    $lobby = Lobby::factory()->create();

    $response = $this->postJson(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
        'team' => 1,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user_id', 'username', 'team'])
        ->assertJson([
            'username' => 'TestPlayer',
            'team' => 1,
        ]);
});

test('join stores guest info in session', function () {
    $lobby = Lobby::factory()->create();

    $this->postJson(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
        'team' => 1,
    ])->assertOk();

    $guest = session('lobby_guest');

    expect($guest)->not->toBeNull()
        ->and($guest['username'])->toBe('TestPlayer')
        ->and($guest['team'])->toBe(1)
        ->and($guest['lobby_code'])->toBe($lobby->code)
        ->and($guest['user_id'])->toBeString();
});

test('user can join team 2', function () {
    $lobby = Lobby::factory()->create();

    $response = $this->postJson(route('lobby.join', $lobby), [
        'username' => 'Player2',
        'team' => 2,
    ]);

    $response->assertOk()
        ->assertJson([
            'username' => 'Player2',
            'team' => 2,
        ]);
});

test('join requires username', function () {
    $lobby = Lobby::factory()->create();

    $this->postJson(route('lobby.join', $lobby), [
        'team' => 1,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

test('join requires valid team', function () {
    $lobby = Lobby::factory()->create();

    $this->postJson(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
        'team' => 3,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['team']);
});

test('join requires team field', function () {
    $lobby = Lobby::factory()->create();

    $this->postJson(route('lobby.join', $lobby), [
        'username' => 'TestPlayer',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['team']);
});

test('username max length is 30 characters', function () {
    $lobby = Lobby::factory()->create();

    $this->postJson(route('lobby.join', $lobby), [
        'username' => str_repeat('a', 31),
        'team' => 1,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

test('create lobby requires title', function () {
    $this->postJson(route('lobby.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});
