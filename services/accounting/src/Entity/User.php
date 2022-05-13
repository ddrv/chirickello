<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Entity;

class User
{
    private string $id;
    private ?string $login;
    private array $roles;
    private array $original;

    public function __construct(string $id, ?string $login = null, array $roles = [])
    {
        $this->id = $id;
        $this->login = $login;
        $this->roles = $this->normalizeRoles($roles);
        $this->flush();
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

    public function isLoginChanged(): bool
    {
        return $this->login !== $this->original['login'];
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $this->normalizeRoles($roles);
    }

    public function isRolesChanged(): bool
    {
        return implode(' ', $this->roles) !== $this->original['roles'];
    }

    public function isChanged(): bool
    {
        return $this->isLoginChanged() || $this->isRolesChanged();
    }

    public function flush(): void
    {
        $this->original = [
            'login' => $this->login,
            'roles' => implode(' ', $this->roles),
        ];
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
