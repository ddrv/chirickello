<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UserBalanceHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = ['balance' => true];
        return $this->createResponse($data);
    }

    private function createResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('content-type', 'application/json')
        ;
        $response->getBody()->write(json_encode($data));
        return $response;
    }
}
