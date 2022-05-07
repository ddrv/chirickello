<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Entity;

class User
{
    private string $id;
    private ?string $login;
    private ?array $roles;

    public function __construct(string $id, ?string $login, ?array $roles)
    {
        $this->id = $id;
        $this->login = $login;
        $this->roles = $this->normalizeRoles($roles);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $this->normalizeRoles($roles);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    private function normalizeRoles(?array $roles): array
    {

        if (is_null($roles)) {
            return [];
        }
        sort($roles);
        array_unique(array_filter($roles));
        return $roles;
    }
}
