<?php

namespace App\Http\Requests\Lobbies;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'username' => ['required', 'string', 'max:30'],
            'team' => ['required', 'integer', 'in:1,2'],
        ];
    }
}
