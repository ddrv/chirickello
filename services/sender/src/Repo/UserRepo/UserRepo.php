<?php

declare(strict_types=1);

namespace Chirickello\Sender\Repo\UserRepo;

use Chirickello\Sender\Entity\User;
use Chirickello\Sender\Exception\StorageException;
use Chirickello\Sender\Exception\UserNotFoundException;

interface UserRepo
{
    /**
     * @param string $id
     * @return User
     * @throws UserNotFoundException
     */
    public function getById(string $id): User;

    /**
     * @param User $user
     * @return void
     * @throws StorageException
     */
    public function save(User $user): void;
}
