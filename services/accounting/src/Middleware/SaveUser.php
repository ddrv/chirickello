<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Middleware;

use Chirickello\Accounting\Entity\User;
use Chirickello\Accounting\Exception\StorageException;
use Chirickello\Accounting\Repo\UserRepo\UserRepo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SaveUser implements MiddlewareInterface
{
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (is_array($user)) {
            $this->saveUser($user);
        }

        return $handler->handle($request);
    }

    private function saveUser(array $userArray): void
    {
        $id = $userArray['id'] ?? null;
        $login = $userArray['login'] ?? null;
        $roles = $userArray['roles'] ?? [];
        if (!is_string($id) || !is_string($login) || !is_array($roles)) {
            return;
        }
        $id = trim($id);
        if ($id === '') {
            return;
        }
        $login = trim($login);
        if ($login === '') {
            return;
        }
        foreach ($roles as $k => $role) {
            if (!is_string($role)) {
                unset($roles[$k]);
            }
        }
        $user = new User($id, $login, $roles);
        try {
            $this->userRepo->save($user);
        } catch (StorageException $exception) {
        }
    }
}
