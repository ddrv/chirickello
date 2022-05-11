<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\UserAdded;

use Chirickello\Package\Event\BaseEvent;

class UserAdded extends BaseEvent
{
    private string $userId;
    private string $login;
    private string $email;

    public function __construct(
        string $userId,
        string $login,
        string $email
    ) {
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

    public function getEventName(): string
    {
        return 'user.added';
    }

    /**
     * @inheritDoc
     */
    public function jsonDataSerialize(): object
    {
        return (object)[
            'userId' => $this->userId,
            'login' => $this->login,
            'email' => $this->email,
        ];
    }
}
