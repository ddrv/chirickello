<?php

/** @var ContainerInterface $container */

use Chirickello\Auth\Handler\Api\V1\MeEndpoint;
use Chirickello\Auth\Handler\OAuth\OAuth2Authorize;
use Chirickello\Auth\Handler\OAuth\OAuth2Token;
use Chirickello\Auth\Middleware\AuthRequiredApiMiddleware;
use Chirickello\Auth\Middleware\CorsMiddleware;
use Chirickello\Auth\Middleware\TokenMiddleware;
use Ddrv\ServerRequestWizard\FileReader;
use Ddrv\ServerRequestWizard\ServerRequestWizard;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;

$container = require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

/** @var ServerRequestWizard $wizard */
$wizard = $container->get(ServerRequestWizard::class);

/** @var App $app */
$app = $container->get(App::class);

$router = $app->getRouteCollector();
$router->group('', function (RouteCollectorProxyInterface $router) {
    $router->group('', function (RouteCollectorProxyInterface $router) {
        $router->get('/oauth/v2/authorize', OAuth2Authorize::class)->setName('oauth.form');
        $router->post('/oauth/v2/authorize', OAuth2Authorize::class)->setName('oauth.handler');
    });
});

$router->group('', function (RouteCollectorProxyInterface $router) {
    $router->options('/oauth/v2/token', OAuth2Token::class);
    $router->post('/oauth/v2/token', OAuth2Token::class);

    $router->group('/api/v1', function (RouteCollectorProxyInterface $router) {
        $router->group('', function (RouteCollectorProxyInterface $router) {
            $router->options('/me', MeEndpoint::class);
            $router->get('/me', MeEndpoint::class);
        })->add(AuthRequiredApiMiddleware::class);
    })->add(TokenMiddleware::class);
})->add(CorsMiddleware::class);

$request = $wizard->create(
    $_GET,
    $_POST,
    $_SERVER,
    $_COOKIE,
    $_FILES,
    new FileReader('php://input')
);

$app->run($request);
