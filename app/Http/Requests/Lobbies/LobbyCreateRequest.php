<?php

namespace App\Http\Requests\Lobbies;

use Illuminate\Foundation\Http\FormRequest;

class LobbyCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:1|max:255',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Введите название лобби',
        ];
    }
}
