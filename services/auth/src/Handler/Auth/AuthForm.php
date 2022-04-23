<?php

declare(strict_types=1);

namespace Chirickello\Auth\Handler\Auth;

use Chirickello\Auth\Support\Session\Session;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteParserInterface;
use Twig\Environment;

class AuthForm implements RequestHandlerInterface
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
        /** @var Session $session */
        $session = $request->getAttribute('__session__');
        $return = $request->getQueryParams()['return'] ?? '/';
        if (!is_string($return)) {
            $return = '/';
        }
        $errors = $session['errors'] ?? [];
        $old = [
            'login' => $session['auth.form.old.login'] ?? '',
        ];
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', ['text/html; charset=utf-8'])
        ;
        $body = $response->getBody();
        $body->write(
            $this->render->render('auth/form.twig', [
                'action' => $this->router->urlFor('auth.handler'),
                'return' => $return,
                'errors' => $errors,
                'old' => $old,
            ])
        );
        return $response;
    }
}
