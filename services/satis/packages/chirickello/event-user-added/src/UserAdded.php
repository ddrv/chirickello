<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\UserAdded;

use Chirickello\Package\Event\BaseEvent\BaseEvent;

class UserAdded extends BaseEvent
{
    private string $userId;
    private string $login;
    private string $email;

    public function __construct(string $userId, string $login, string $email)
    {
        $this->userId = $userId;
        $this->login = $login;
        $this->email = $email;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'event' => 'user.added',
            'data' => [
                'userId' => $this->userId,
                'login' => $this->login,
                'email' => $this->email,
            ],
        ];
    }
}
