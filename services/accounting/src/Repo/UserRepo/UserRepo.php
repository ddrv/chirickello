<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Repo\UserRepo;

use Chirickello\Accounting\Entity\User;
use Chirickello\Accounting\Exception\StorageException;
use Chirickello\Accounting\Exception\UserNotFoundException;

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

    /**
     * @param User $user
     * @return void
     * @throws StorageException
     */
    public function delete(User $user): void;
}
