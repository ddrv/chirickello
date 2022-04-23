<?php

declare(strict_types=1);

namespace Chirickello\Auth\Middleware;

use Chirickello\Auth\Exception\UserNotFoundException;
use Chirickello\Auth\Repo\UserRepo\UserRepo;
use Chirickello\Auth\Support\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DefineUserMiddleware implements MiddlewareInterface
{
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute('__session__');
        if (!$session instanceof Session) {
            return $handler->handle($request);
        }

        $login = array_key_exists('login', $session) ? (string)$session['login'] : null;
        if (empty($login)) {
            return $handler->handle($request);
        }

        try {
            $user = $this->userRepo->getByLogin($login);
        } catch (UserNotFoundException $exception) {
            return $handler->handle($request);
        }

        return $handler->handle($request->withAttribute('__user__', $user));
    }
}
