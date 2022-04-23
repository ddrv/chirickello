<?php

declare(strict_types=1);

namespace Chirickello\Auth\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        switch ($method) {
            case 'OPTIONS':
                return $this->applyHeaders($this->responseFactory->createResponse(), true);
            case 'GET': // no break
            case 'POST':
            return $this->applyHeaders($handler->handle($request), false);
            default:
                return $handler->handle($request);
        }
    }

    private function applyHeaders(ResponseInterface $response, bool $isOptions): ResponseInterface
    {
        $response = $response
//            ->withHeader('Referrer-Policy', ['origin'])
            ->withHeader('Access-Control-Allow-Origin', ['*'])
            ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'OPTIONS'])
            ->withHeader('Access-Control-Allow-Headers', [
                'DNT',
                'User-Agent',
                'X-Requested-With',
                'If-Modified-Since',
                'Cache-Control',
                'Content-Type',
                'Range'
            ])
        ;
        if ($isOptions) {
            $response = $response->withHeader('Access-Control-Max-Age', ['1728000']);
        } else {
            $response = $response->withHeader('Access-Control-Expose-Headers', ['Content-Length', 'Content-Range']);
        }
        return $response;
    }
}
