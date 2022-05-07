<?php

declare(strict_types=1);

namespace Chirickello\Package\Middleware\RoleAccess;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoleAccessMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;
    private array $roles;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        array $roles
    ) {
        $this->responseFactory = $responseFactory;
        $this->roles = $roles;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = (array)$request->getAttribute('user');

        if (empty($this->roles) || $this->checkRole($user)) {
            return $handler->handle($request);
        }

        return $this->createForbiddenResponse();
    }

    private function checkRole(array $user): bool
    {
        $userRoles = ($user['roles'] ?? []);
        if (!is_array($userRoles)) {
            return false;
        }
        if (in_array('admin', $userRoles)) {
            return true;
        }
        foreach ($userRoles as $role) {
            if (in_array($role, $this->roles)) {
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
