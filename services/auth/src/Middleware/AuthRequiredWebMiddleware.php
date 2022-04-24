<?php

declare(strict_types=1);

namespace Chirickello\Auth\Middleware;

use Chirickello\Auth\Entity\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;

class AuthRequiredWebMiddleware implements MiddlewareInterface
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('__user__');
        if ($user instanceof User) {
            return $handler->handle($request);
        }

        $query = [];
        $return = (string)$request->getUri()->withHost('')->withScheme('')->withPort(null)->withUserInfo('');
        if ($return !== '/') {
            $query['return'] = $return;
        }
        return $this->responseFactory->createResponse(301)
            ->withHeader('Location', [$this->router->urlFor('auth.form', [], $query)])
            ;
    }
}
