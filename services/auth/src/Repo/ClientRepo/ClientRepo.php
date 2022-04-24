<?php

declare(strict_types=1);

namespace Chirickello\Auth\Repo\ClientRepo;

use Chirickello\Auth\Entity\Client;
use Chirickello\Auth\Exception\ClientNotFoundException;

interface ClientRepo
{
    /**
     * @param string $id
     * @return Client
     * @throws ClientNotFoundException
     */
    public function getById(string $id): Client;
}
