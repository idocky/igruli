<?php

namespace App\Enums;

enum GamesEnum: string
{
    case DUSHNILA = 'dushnila';

    public static function allValues(): array
    {
        return collect(GamesEnum::cases())->pluck('value')->toArray();
    }
}
