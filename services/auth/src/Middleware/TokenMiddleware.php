<?php

declare(strict_types=1);

namespace Chirickello\Auth\Middleware;

use Chirickello\Auth\Exception\UserNotFoundException;
use Chirickello\Auth\Repo\UserRepo\UserRepo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TokenMiddleware implements MiddlewareInterface
{
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine('Authorization');
        preg_match('/^bearer\s(?<token>.*)$/ui', $authorization, $matches);
        if (!array_key_exists('token', $matches)) {
            return $handler->handle($request);
        }

        $raw = explode(':', base64_decode($matches['token']));
        if (count($raw) !== 2) {
            return $handler->handle($request);
        }

        [$login, $scope] = $raw;
        if (empty($login)) {
            return $handler->handle($request);
        }

        try {
            $user = $this->userRepo->getByLogin($login);
        } catch (UserNotFoundException $exception) {
            return $handler->handle($request);
        }

        $request = $request
            ->withAttribute('__user__', $user)
            ->withAttribute('__scope__', empty($scope) ? [] : explode(' ', $scope));
        ;
        return $handler->handle($request);
    }
}
