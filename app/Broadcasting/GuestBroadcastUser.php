<?php

namespace App\Broadcasting;

use Illuminate\Contracts\Auth\Authenticatable;

class GuestBroadcastUser implements Authenticatable
{
    public function __construct(
        public string $id,
        public string $username,
        public int $team,
        public string $lobbyCode
    ) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return '';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }
}
