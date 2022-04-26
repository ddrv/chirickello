<?php

use Ddrv\Container\Container;
use Ddrv\Env\Env;
use Ddrv\Http\Client\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\App;
use Slim\CallableResolver;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteParser;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$container = new Container();

$container->value('root', __DIR__);

// ENV

$container->service(Env::class, function () {
    return new Env('');
});

// HTTP-FACTORY

$container->service(Psr17Factory::class, function() {
    return new Psr17Factory();
});
$container->bind(RequestFactoryInterface::class, Psr17Factory::class);
$container->bind(ResponseFactoryInterface::class, Psr17Factory::class);
$container->bind(ServerRequestFactoryInterface::class, Psr17Factory::class);
$container->bind(StreamFactoryInterface::class, Psr17Factory::class);
$container->bind(UploadedFileFactoryInterface::class, Psr17Factory::class);
$container->bind(UriFactoryInterface::class, Psr17Factory::class);

// HTTP CLIENT

$container->service(Client::class, function (ContainerInterface $container) {
    return new Client(
        $container->get(ResponseFactoryInterface::class),
        5
    );
});
$container->bind(ClientInterface::class, Client::class);

// SLIM

$container->service(RouteParser::class, function (ContainerInterface $container) {
    return new RouteParser(
        $container->get(RouteCollectorInterface::class)
    );
});
$container->bind(RouteParserInterface::class, RouteParser::class);

$container->service(RouteCollector::class, function (ContainerInterface $container) {
    return new RouteCollector(
        $container->get(ResponseFactoryInterface::class),
        $container->get(CallableResolverInterface::class),
        $container,
        $container->get(InvocationStrategyInterface::class)
    );
});
$container->bind(RouteCollectorInterface::class, RouteCollector::class);

$container->service(CallableResolver::class, function (ContainerInterface $container) {
    return new CallableResolver($container);
});
$container->bind(CallableResolverInterface::class, CallableResolver::class);

$container->service(RequestHandler::class, function () {
    return new RequestHandler(true);
});
$container->bind(InvocationStrategyInterface::class, RequestHandler::class);

$container->service(App::class, function (ContainerInterface $container) {
    $app = new App(
        $container->get(ResponseFactoryInterface::class),
        $container,
        $container->get(CallableResolverInterface::class),
        $container->get(RouteCollectorInterface::class)
    );
    $app->addRoutingMiddleware();
    $app->add(ErrorMiddleware::class);
    return $app;
});

$container->service(ErrorMiddleware::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    $debug = (bool)$env->get('DEBUG');
    return new ErrorMiddleware(
        $container->get(CallableResolverInterface::class),
        $container->get(ResponseFactoryInterface::class),
        $debug,
        $debug,
        $debug
    );
});

return $container;