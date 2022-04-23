<?php

declare(strict_types=1);

namespace Chirickello\Auth\Handler\Auth;

use Chirickello\Auth\Exception\UserNotFoundException;
use Chirickello\Auth\Repo\UserRepo\UserRepo;
use Chirickello\Auth\Support\Session\Session;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;

class AuthHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private RouteParserInterface $router;
    private UserRepo $userRepo;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RouteParserInterface $router,
        UserRepo $userRepo
    ) {
        $this->responseFactory = $responseFactory;
        $this->router = $router;
        $this->userRepo = $userRepo;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Session $session */
        $session = $request->getAttribute('__session__');
        $post = $request->getParsedBody();
        $login = (string)($post['login'] ?? '');
        $return = (string)($post['return'] ?? '');
        if (empty($return) || strpos($return, '/') !== 0) {
            $return = '/';
        }
        $session->flash('auth.form.old.login', $login);

        try {
            $user = $this->userRepo->getByLogin($login);
        } catch (UserNotFoundException $exception) {
            $session->flash('errors', ['parrot not found']);
            return $this->responseFactory->createResponse(302)
                ->withHeader('Location', [$this->router->urlFor('auth.form')])
                ;
        }

        $session['login'] = $user->getLogin();
        return $this->responseFactory->createResponse(302)
            ->withHeader('Location', [$return])
            ;
    }
}
