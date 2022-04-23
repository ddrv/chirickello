<?php

declare(strict_types=1);

namespace Chirickello\Auth\Handler\Logout;

use Chirickello\Auth\Entity\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Twig\Environment;

class LogoutForm implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private RouteParserInterface $router;
    private Environment $render;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RouteParserInterface $router,
        Environment $render
    ) {
        $this->responseFactory = $responseFactory;
        $this->router = $router;
        $this->render = $render;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User|null $user */
        $user = $request->getAttribute('__user__');

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', ['text/html; charset=utf-8'])
        ;
        $body = $response->getBody();
        $body->write(
            $this->render->render('exit/form.twig', [
                'login' => $user->getLogin(),
                'action' => $this->router->urlFor('exit.handler'),
            ])
        );
        return $response;
    }
}
