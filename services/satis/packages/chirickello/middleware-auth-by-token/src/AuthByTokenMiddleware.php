<?php

declare(strict_types=1);

namespace Chirickello\Package\Middleware\AuthByToken;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthByTokenMiddleware implements MiddlewareInterface
{
    private RequestFactoryInterface $requestFactory;
    private UriFactoryInterface $uriFactory;
    private ClientInterface $httpClient;
    private string $authHost;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        UriFactoryInterface $uriFactory,
        ClientInterface $httpClient,
        string $authHost
    ) {
        $this->requestFactory = $requestFactory;
        $this->uriFactory = $uriFactory;
        $this->httpClient = $httpClient;
        $this->authHost = $authHost;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = trim($request->getHeaderLine('Authorization'));
        $user = $this->getUser($header);
        $request = $request->withAttribute('user', $user);
        return $handler->handle($request);
    }

    private function getUser(string $header): ?array
    {
        $uri = $this->uriFactory->createUri($this->authHost);
        $uri = $uri
            ->withUserInfo('')
            ->withPath('/api/v1/me')
            ->withQuery('')
            ->withFragment('')
        ;
        $request = $this->requestFactory
            ->createRequest('GET', $uri)
            ->withHeader('Authorization', [$header])
        ;
        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            return null;
        }
        return json_decode($response->getBody()->__toString(), true);
    }
}
