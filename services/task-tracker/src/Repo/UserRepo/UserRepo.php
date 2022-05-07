<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Repo\UserRepo;

use Chirickello\TaskTracker\Entity\User;
use Chirickello\TaskTracker\Exception\StorageException;
use Chirickello\TaskTracker\Exception\UserNotFoundException;

interface UserRepo
{
    /**
     * @param string $id
     * @return User
     * @throws UserNotFoundException
     */
    public function getById(string $id): User;

    /**
     * @param string[] $ids
     * @return User[]
     */
    public function getByIds(array $ids): iterable;

    /**
     * @param string $role
     * @return User[]
     */
    public function getByRole(string $role): iterable;

    /**
     * @param User $user
     * @return void
     * @throws StorageException
     */
    public function save(User $user): void;
}
