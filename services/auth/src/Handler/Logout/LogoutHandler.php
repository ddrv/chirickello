<?php

declare(strict_types=1);

namespace Chirickello\Auth\Handler\Logout;

use Chirickello\Auth\Support\Session\Session;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;

class LogoutHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private RouteParserInterface $router;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RouteParserInterface $router
    ) {
        $this->responseFactory = $responseFactory;
        $this->router = $router;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Session $session */
        $session = $request->getAttribute('__session__');
        unset($session['login']);
        return $this->responseFactory->createResponse(302)
            ->withHeader('Location', [$this->router->urlFor('auth.form')])
            ;
    }
}
