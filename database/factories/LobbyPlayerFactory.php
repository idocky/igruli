<?php

namespace Database\Factories;

use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LobbyPlayer>
 */
class LobbyPlayerFactory extends Factory
{
    protected $model = LobbyPlayer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->userName(),
            'user_id' => null,
            'guest_id' => fake()->uuid(),
            'team' => fake()->randomElement([1, 2]),
            'lobby_id' => Lobby::factory(),
        ];
    }
}
