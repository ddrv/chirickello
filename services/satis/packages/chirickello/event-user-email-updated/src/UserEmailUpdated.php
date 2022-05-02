<?php

declare(strict_types=1);

namespace Chirickello\Package\Event;

class UserEmailUpdated
{
    private string $userId;
    private string $email;

    public function __construct(string $userId, string $email)
    {
        $this->userId = $userId;
        $this->email = $email;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
