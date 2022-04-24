<?php

declare(strict_types=1);

namespace Chirickello\Auth\Handler\OAuth;

use Chirickello\Auth\Entity\Client;
use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\UserNotFoundException;
use Chirickello\Auth\Repo\ClientRepo\ClientRepo;
use Chirickello\Auth\Repo\UserRepo\UserRepo;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Twig\Environment;

class OAuth2Authorize implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private RouteParserInterface $router;
    private Environment $render;
    private ClientRepo $clientRepo;
    private UserRepo $userRepo;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RouteParserInterface $router,
        Environment $render,
        ClientRepo $clientRepo,
        UserRepo $userRepo
    ) {
        $this->responseFactory = $responseFactory;
        $this->router = $router;
        $this->render = $render;
        $this->clientRepo = $clientRepo;
        $this->userRepo = $userRepo;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'GET') {
            return $this->get($request);
        }
        return $this->post($request);
    }

    private function get(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $user */
        $user = $request->getAttribute('__user__');
        $query = $request->getQueryParams();
        /**
         * @var Client $client
         * @var string $scope
         * @var string $redirectUri
         * @var string $state
         */
        [$client, $scope, $redirectUri, $state] = $this->checkOauthParams($query);
        unset($redirectUri, $state);

        $users = [];
        foreach ($this->userRepo->getAll() as $user) {
            $users[] = [
                'login' => $user->getLogin(),
                'roles' => implode(', ', $user->getRoles()),
            ];
        }
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', ['text/html; charset=utf-8'])
        ;
        $body = $response->getBody();
        $body->write(
            $this->render->render('oauth/form.twig', [
                'login' => $user->getLogin(),
                'action' => $this->router->urlFor('oauth.handler'),
                'users' => $users,
                'oauth' => $query,
                'client' => $client->getName(),
                'scope' => empty($scope) ? null : explode(' ', $scope),
            ])
        );
        return $response;
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $user */
        $user = $request->getAttribute('__user__');
        $post = $request->getParsedBody();
        $login = (string)($post['login'] ?? '');
        try {
            $user = $this->userRepo->getByLogin($login);
        } catch (UserNotFoundException $e) {
            //todo error
        }

        /**
         * @var Client $client
         * @var string $scope
         * @var string $redirectUri
         * @var string|null $state
         */
        [$client, $scope, $redirectUri, $state] = $this->checkOauthParams($post);
        unset($client);

        $token = implode(':', [$user->getLogin(), $scope]);

        $redirectUriString = $redirectUri;
        $redirectQueryString = '';
        if (strpos($redirectUri, '?') !== false) {
            [$redirectUriString, $redirectQueryString] = explode('?', $redirectUri, 2);
        }
        $redirectQuery = [];
        parse_str($redirectQueryString, $redirectQuery);
        $redirectQuery['code'] = rtrim(base64_encode(uniqid($token . ':', true)), '=');
        if (!is_null($state)) {
            $redirectQuery['state'] = $state;
        }
        $redirect = $redirectUriString . '?' . http_build_query($redirectQuery);

        return $this->responseFactory->createResponse(301)
            ->withHeader('Location', [$redirect])
        ;
    }

    private function checkOauthParams(array $params): array
    {
        $clientId = $params['client_id'] ?? '';
        $client = $this->clientRepo->getById($clientId);
        $scope = $this->prepareScope((string)($params['scope'] ?? ''));
        $redirectUri = $params['redirect_uri'] ?? '';
        $allowedRedirect = $client->getRedirect();
        if (strpos($redirectUri, $allowedRedirect) !== 0) {
            // todo throws
        }
        $state = $params['state'] ?? null;
        if (!is_string($state)) {
            $state = null;
        }
        return [$client, $scope, $redirectUri, $state];
    }

    private function prepareScope(string $scope): string
    {
        $allowed = ['tasks' => true, 'analytics' => true];
        $arr = explode(' ', $scope);
        $result = [];
        foreach ($arr as $item) {
            if (!array_key_exists($item, $allowed)) {
                continue;
            }
            $result[$item] = $item;
        }
        return implode(' ', $result);
    }
}
