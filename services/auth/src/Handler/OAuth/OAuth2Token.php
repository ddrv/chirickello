<?php

declare(strict_types=1);

namespace Chirickello\Auth\Handler\OAuth;

use Chirickello\Auth\Exception\ClientNotFoundException;
use Chirickello\Auth\Repo\ClientRepo\ClientRepo;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OAuth2Token implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private ClientRepo $clientRepo;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ClientRepo $clientRepo
    ) {
        $this->responseFactory = $responseFactory;
        $this->clientRepo = $clientRepo;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();
        $grantType = $params['grant_type'] ?? null;
        $clientId = $params['client_id'] ?? null;
        $clientSecret = $params['client_secret'] ?? null;
        if (!is_string($grantType) || !is_string($clientId) || !is_string($clientSecret)) {
            return $this->responseFactory->createResponse(400);
        }
        try {
            $client = $this->clientRepo->getById($clientId);
        } catch (ClientNotFoundException $e) {
            return $this->responseFactory->createResponse(400);
        }
        if ($client->getSecret() !== $clientSecret) {
            return $this->responseFactory->createResponse(400);
        }
        switch ($grantType) {
            case 'authorization_code':
                return $this->authorizationCode($params);
            default:
                return $this->responseFactory->createResponse(400);
        }
    }

    private function authorizationCode(array $params): ResponseInterface
    {
        $code = $params['code'] ?? null;
        if (!is_string($code)) {
            return $this->responseFactory->createResponse(400);
        }
        $raw = explode(':', base64_decode($code));
        if (count($raw) !== 3) {
            return $this->responseFactory->createResponse(400);
        }
        $token = rtrim(base64_encode(implode(':', [$raw[0], time(), $raw[1]])), '=');

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', ['application/json'])
        ;
        $body = $response->getBody();
        $body->write(json_encode([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => null,
            'scope' => $raw[1],
        ]));
        return $response;
    }
}
