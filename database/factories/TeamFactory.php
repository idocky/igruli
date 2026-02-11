<?php

namespace Database\Factories;

use App\Models\Lobby;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lobby_id' => Lobby::factory(),
            'number' => 1,
            'name' => 'Команда 1',
            'max_players' => 1,
        ];
    }

    public function number(int $number): static
    {
        return $this->state(fn () => [
            'number' => $number,
            'name' => "Команда {$number}",
        ]);
    }
}
