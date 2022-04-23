<?php

use App\Handler\Error\ForbiddenHandler;
use App\Handler\Error\NotFoundHandler;
use App\Handler\Error\ServerErrorHandler;
use App\Handler\Error\UnauthorizedHandler;
use Ddrv\Container\Container;
use Ddrv\Env\Env;
use Ddrv\ServerRequestWizard\ServerRequestWizard;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\App;
use Slim\CallableResolver;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
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

$container->service(Env::class, function (ContainerInterface $container) {
    return new Env(
        $container->get('root') . DIRECTORY_SEPARATOR . '.env'
    );
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

// SERVER REQUEST WIZARD

$container->service(ServerRequestWizard::class, function (ContainerInterface $container) {
    return new ServerRequestWizard(
        $container->get(ServerRequestFactoryInterface::class),
        $container->get(StreamFactoryInterface::class),
        $container->get(UploadedFileFactoryInterface::class)
    );
});

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