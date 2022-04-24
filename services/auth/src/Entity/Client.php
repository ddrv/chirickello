<?php

declare(strict_types=1);

namespace Chirickello\Auth\Entity;

class Client
{
    private string $id;
    private string $secret;
    private string $name;
    private string $redirect;

    public function __construct(string $id, string $secret, string $name, string $redirect)
    {
        $this->id = $id;
        $this->secret = $secret;
        $this->name = $name;
        $this->redirect = $redirect;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRedirect(): string
    {
        return $this->redirect;
    }
}
