<?php

namespace Database\Factories;

use App\Models\Lobby;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lobby> */
class LobbyFactory extends Factory
{
    protected $model = Lobby::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->lexify('??????')),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Lobby $lobby) {
            if ($lobby->teams()->exists()) {
                return;
            }

            Team::factory()->for($lobby)->number(1)->create();
            Team::factory()->for($lobby)->number(2)->create();
        });
    }
}
