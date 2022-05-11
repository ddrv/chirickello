<?php

declare(strict_types=1);

namespace Chirickello\Package\Event\UserRolesAssigned;

use Chirickello\Package\Event\BaseEvent;

class UserRolesAssigned extends BaseEvent
{
    private string $userId;
    private array $roles;

    /**
     * @param string $userId
     * @param string[] $roles
     */
    public function __construct(
        string $userId,
        array $roles
    ) {
        $this->userId = $userId;
        $this->roles = $roles;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getEventName(): string
    {
        return 'user.roles-assigned';
    }

    public function jsonDataSerialize(): object
    {
        return (object)[
            'userId' => $this->userId,
            'roles' => $this->roles,
        ];
    }
}
