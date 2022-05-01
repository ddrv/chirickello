<?php

declare(strict_types=1);

namespace Chirickello\Package\Event;

class UserCreated
{
    private string $userId;
    private string $login;

    public function __construct(string $userId, string $login)
    {
        $this->userId = $userId;
        $this->login = $login;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getLogin(): string
    {
        return $this->login;
    }
}
