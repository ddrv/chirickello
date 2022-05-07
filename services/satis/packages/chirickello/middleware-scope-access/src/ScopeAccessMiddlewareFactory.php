<?php

declare(strict_types=1);

namespace Chirickello\Package\Middleware\ScopeAccess;

use Psr\Http\Message\ResponseFactoryInterface;

class ScopeAccessMiddlewareFactory
{
    private ResponseFactoryInterface $responseFactory;
    private array $cache = [];

    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    public function make(array $scope): ScopeAccessMiddleware
    {
        sort($scope);
        $key = implode(' ', $scope);
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = new ScopeAccessMiddleware(
                $this->responseFactory,
                $scope
            );
        }
        return $this->cache[$key];
    }
}
