<?php

declare(strict_types=1);

namespace Chirickello\Auth\Middleware;

use Chirickello\Auth\Entity\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthRequiredApiMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('__user__');
        if ($user instanceof User) {
            return $handler->handle($request);
        }

        $response = $this->responseFactory->createResponse(401)
            ->withHeader('Content-Type', ['application/json'])
        ;

        $body = $response->getBody();
        $body->write(json_encode([
            'error' => 'need authorization',
        ]));
        return $response;
    }
}
