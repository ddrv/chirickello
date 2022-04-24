<?php

declare(strict_types=1);

namespace Chirickello\Auth\Repo\UserRepo;

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\UserNotFoundException;

class UserEnvRepo implements UserRepo
{
    private array $users = [];

    public function __construct(string $admins, string $managers, string $accountants, string $developers)
    {
        $all = [
            'admin' => explode(',', $admins),
            'manager' => explode(',', $managers),
            'accountant' => explode(',', $accountants),
            'developer' => explode(',', $developers),
        ];
        foreach ($all as $role => $logins) {
            foreach ($logins as $login) {
                $login = trim($login);
                if (empty($login)) {
                    continue;
                }
                $this->registerUser($login, $role);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getByLogin(string $login): User
    {
        $key = strtolower(trim($login));
        if (!array_key_exists($key, $this->users)) {
            throw new UserNotFoundException(sprintf('user with login %s not found', $login));
        }
        return $this->users[$key];
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return array_values($this->users);
    }

    private function registerUser(string $login, string $role): void
    {
        $key = trim(strtolower($login));
        if (!array_key_exists($key, $this->users)) {
            $this->users[$key] = new User($login);
        }
        $user = $this->users[$key];
        $role = trim(strtolower($role));
        if ($role === '') {
            return;
        }
        $user->addRole($role);
    }
}
