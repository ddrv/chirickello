<?php

declare(strict_types=1);

namespace Chirickello\Auth\Repo\UserRepo;

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\UserNotFoundException;

interface UserRepo
{
    /**
     * @param string $login
     * @return User
     * @throws UserNotFoundException
     */
    public function getByLogin(string $login): User;
}
