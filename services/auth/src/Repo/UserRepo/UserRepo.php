<?php

declare(strict_types=1);

namespace Chirickello\Auth\Repo\UserRepo;

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\EmailExistsException;
use Chirickello\Auth\Exception\LoginExistsException;
use Chirickello\Auth\Exception\StorageException;
use Chirickello\Auth\Exception\UserNotFoundException;

interface UserRepo
{
    /**
     * @param string $id
     * @return User
     * @throws UserNotFoundException
     */
    public function getById(string $id): User;

    /**
     * @param string $login
     * @return User
     * @throws UserNotFoundException
     */
    public function getByLogin(string $login): User;

    /**
     * @return User[]
     */
    public function getAll(): iterable;

    /**
     * @param User $user
     * @return void
     * @throws EmailExistsException
     * @throws LoginExistsException
     * @throws StorageException
     */
    public function save(User $user): void;
}
