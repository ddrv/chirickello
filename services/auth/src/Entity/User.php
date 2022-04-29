<?php

declare(strict_types=1);

namespace Chirickello\Auth\Entity;

use RuntimeException;

class User
{
    private ?string $id;
    private string $login;
    private string $email;
    private array $roles = [];

    public function __construct(string $login, string $email, ?string $id = null)
    {
        $this->id = $id;
        $this->login = $login;
        $this->email = $email;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        if (!is_null($this->id)) {
            throw new RuntimeException('Can not set id for saved user');
        }
        $this->id = $id;
    }

    public function isNew(): bool
    {
        return is_null($this->id);
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getEmail(): string
    {
        return $this->email;
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
