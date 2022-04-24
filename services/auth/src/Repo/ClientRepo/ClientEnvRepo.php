<?php

declare(strict_types=1);

namespace Chirickello\Auth\Repo\ClientRepo;

use Chirickello\Auth\Entity\Client;
use Chirickello\Auth\Exception\ClientNotFoundException;

class ClientEnvRepo implements ClientRepo
{
    private array $storage = [];

    public function __construct(string $id, string $secret, string $redirect)
    {
        $this->storage[$id] = new Client($id, $secret, 'official app', $redirect);
    }

    /**
     * @inheritDoc
     */
    public function getById(string $id): Client
    {
        if (!array_key_exists($id, $this->storage)) {
            throw new ClientNotFoundException(sprintf('client %s not found', $id));
        }
        return $this->storage[$id];
    }
}
