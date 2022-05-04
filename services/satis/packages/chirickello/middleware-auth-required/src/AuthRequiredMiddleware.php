<?php

declare(strict_types=1);

namespace Chirickello\Package\Middleware\AuthRequired;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthRequiredMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (is_null($user)) {
            return $this->createUnauthorizedResponse();
        }
        return $handler->handle($request);
    }

    private function createUnauthorizedResponse(): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse(401)
            ->withHeader('Content-Type', ['application/json'])
        ;
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
        ]));
        return $response;
    }
}
