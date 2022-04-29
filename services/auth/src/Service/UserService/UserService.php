<?php

declare(strict_types=1);

namespace Chirickello\Auth\Service\UserService;

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\EmailExistsException;
use Chirickello\Auth\Exception\LoginExistsException;
use Chirickello\Auth\Exception\StorageException;
use Chirickello\Auth\Exception\UserNotFoundException;
use Chirickello\Auth\Repo\UserRepo\UserRepo;

class UserService
{
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * @param string $id
     * @return User
     * @throws UserNotFoundException
     */
    public function getById(string $id): User
    {
        return $this->userRepo->getById($id);
    }

    /**
     * @return User[]
     */
    public function getAll(): iterable
    {
        return $this->userRepo->getAll();
    }

    /**
     * @param string $login
     * @return User
     * @throws UserNotFoundException
     */
    public function getByLogin(string $login): User
    {
        return $this->userRepo->getByLogin($login);
    }

    /**
     * @param User $user
     * @return void
     * @throws EmailExistsException
     * @throws LoginExistsException
     * @throws StorageException
     */
    public function save(User $user): void
    {
        $this->userRepo->save($user);
    }
}
