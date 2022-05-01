<?php

declare(strict_types=1);

namespace Chirickello\Sender\Entity;

class User
{
    private string $id;
    private ?string $login;
    private ?string $email;
    private array $original;

    public function __construct(string $id, ?string $login = null, ?string $email = null)
    {
        $this->id = $id;
        $this->login = $login;
        $this->email = $email;
        $this->original = [
            'login' => $login,
            'email' => $email,
        ];
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function isEmailChanged(): bool
    {
        return $this->login !== $this->original['email'];
    }

    public function isChanged(): bool
    {
        return $this->isLoginChanged() || $this->isEmailChanged();
    }
}
