<?php

namespace App\Http\Requests\Lobbies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LobbyRemovePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var \App\Models\Lobby $lobby */
        $lobby = $this->route('lobby');

        return [
            'guest_id' => [
                'required',
                'string',
                Rule::exists('lobby_players', 'guest_id')->where('lobby_id', $lobby->id),
            ],
        ];
    }
}
