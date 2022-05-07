<?php

declare(strict_types=1);

namespace Chirickello\Package\Middleware\ScopeAccess;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ScopeAccessMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;
    private array $scope;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        array $scope
    ) {
        $this->responseFactory = $responseFactory;
        $this->scope = $scope;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = (array)$request->getAttribute('user');

        if (empty($this->scope) || $this->checkScope($user)) {
            return $handler->handle($request);
        }

        return $this->createForbiddenResponse();
    }

    private function checkScope(array $user): bool
    {
        $userScope = ($user['scope'] ?? []);
        if (!is_array($userScope)) {
            return false;
        }
        foreach ($userScope as $item) {
            if (in_array($item, $this->scope)) {
                return true;
            }
        }
        return false;
    }

    private function createForbiddenResponse(): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse(403)
            ->withHeader('Content-Type', ['application/json'])
        ;
        $response->getBody()->write(json_encode([
            'error' => 'Forbidden',
        ]));
        return $response;
    }
}
