<?php

namespace Database\Factories;

use App\Models\Lobby;
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
}
