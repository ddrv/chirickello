<?php

declare(strict_types=1);

namespace Chirickello\Auth\Handler\Api\V1;

use Chirickello\Auth\Entity\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MeEndpoint implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $user */
        $user = $request->getAttribute('__user__');
        /** @var string[] $scope */
        $scope = $request->getAttribute('__scope__');

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', ['application/json'])
        ;

        $body = $response->getBody();
        $body->write(json_encode([
            'id'    => $user->getId(),
            'login' => $user->getLogin(),
            'roles' => $user->getRoles(),
            'scope' => $scope,
        ]));
        return $response;
    }
}