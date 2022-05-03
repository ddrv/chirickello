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
    private array $original;

    public function __construct(string $login, string $email, ?string $id = null)
    {
        $this->id = $id;
        $this->login = $login;
        $this->email = $email;
        $this->flush();
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

    public function isLoginChanged(): bool
    {
        return $this->login === $this->original['login'];
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function isEmailChanged(): bool
    {
        return $this->email === $this->original['email'];
    }

    public function isRolesChanged(): bool
    {
        return implode(' ', array_keys($this->roles)) === $this->original['roles'];
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function addRole(string $role): void
    {
        $role = trim(strtolower($role));
        $this->roles[$role] = true;
        ksort($this->roles);
    }

    public function getRoles(): array
    {
        return array_keys($this->roles);
    }

    public function isChanged(): bool
    {
        if ($this->isEmailChanged()) {
            return true;
        }
        if ($this->isLoginChanged()) {
            return true;
        }
        if ($this->isRolesChanged()) {
            return true;
        }
        return false;
    }

    public function flush(): void
    {
        $this->original = [
            'email' => $this->email,
            'login' => $this->login,
            'roles' => implode(' ', array_keys($this->roles)),
        ];
    }
}
