<?php

declare(strict_types=1);

namespace Chirickello\Auth\Entity;

class User
{
    private string $login;
    private array $roles = [];

    public function __construct(string $login)
    {
        $this->login = $login;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function addRole(string $role): void
    {
        $role = trim(strtolower($role));
        $this->roles[$role] = true;
    }

    public function getRoles(): array
    {
        return array_keys($this->roles);
    }
}
