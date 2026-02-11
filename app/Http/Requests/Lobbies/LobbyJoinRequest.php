<?php

namespace App\Http\Requests\Lobbies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LobbyJoinRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:30'],
            'team' => [
                'required',
                'integer',
                Rule::exists('teams', 'number')->where('lobby_id', $lobby->id),
            ],
        ];
    }
}
