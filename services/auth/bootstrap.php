<?php

use Chirickello\Auth\Handler\Api\V1\MeEndpoint;
use Chirickello\Auth\Handler\OAuth\OAuth2Authorize;
use Chirickello\Auth\Handler\OAuth\OAuth2Token;
use Chirickello\Auth\Middleware\AuthRequiredApiMiddleware;
use Chirickello\Auth\Middleware\CorsMiddleware;
use Chirickello\Auth\Middleware\TokenMiddleware;
use Chirickello\Auth\Repo\ClientRepo\ClientEnvRepo;
use Chirickello\Auth\Repo\ClientRepo\ClientRepo;
use Chirickello\Auth\Repo\UserRepo\UserEnvRepo;
use Chirickello\Auth\Repo\UserRepo\UserRepo;
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
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteParser;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

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

// TEMPLATE

$container->service(FilesystemLoader::class, function (ContainerInterface $container) {
    $directory = $container->get('root') . DIRECTORY_SEPARATOR . 'templates';
    return new FilesystemLoader([$directory]);
});
$container->bind(LoaderInterface::class, FilesystemLoader::class);

$container->service(Environment::class, function (ContainerInterface $container) {
    $options = [];
    /** @var Env $env */
    $env = $container->get(Env::class);
    $debug = (bool)$env->get('DEBUG');
    if (!$debug) {
        $root = $container->get('root');
        $options['cache'] = implode(DIRECTORY_SEPARATOR, [$root, 'var', 'cache', 'twig']);
    }
    return new Environment($container->get(LoaderInterface::class), $options);
});

// REPO

$container->service(UserEnvRepo::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new UserEnvRepo(
        $env->get('ADMINS'),
        $env->get('MANAGERS'),
        $env->get('ACCOUNTANTS'),
        $env->get('DEVELOPERS')
    );
});
$container->bind(UserRepo::class, UserEnvRepo::class);

$container->service(ClientEnvRepo::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new ClientEnvRepo(
        $env->get('OAUTH_CLIENT_ID'),
        $env->get('OAUTH_CLIENT_SECRET'),
        $env->get('OAUTH_CLIENT_REDIRECT')
    );
});
$container->bind(ClientRepo::class, ClientEnvRepo::class);

// MIDDLEWARE

$container->service(TokenMiddleware::class, function (ContainerInterface $container) {
    return new TokenMiddleware(
        $container->get(UserRepo::class)
    );
});

$container->service(CorsMiddleware::class, function (ContainerInterface $container) {
    return new CorsMiddleware(
        $container->get(ResponseFactoryInterface::class)
    );
});

$container->service(AuthRequiredApiMiddleware::class, function (ContainerInterface $container) {
    return new AuthRequiredApiMiddleware(
        $container->get(ResponseFactoryInterface::class)
    );
});

// HANDLERS

$container->service(OAuth2Authorize::class, function (ContainerInterface $container) {
    return new OAuth2Authorize(
        $container->get(ResponseFactoryInterface::class),
        $container->get(RouteParserInterface::class),
        $container->get(Environment::class),
        $container->get(ClientRepo::class),
        $container->get(UserRepo::class)
    );
});

$container->service(OAuth2Token::class, function (ContainerInterface $container) {
    return new OAuth2Token(
        $container->get(ResponseFactoryInterface::class),
        $container->get(ClientRepo::class)
    );
});

$container->service(MeEndpoint::class, function (ContainerInterface $container) {
    return new MeEndpoint(
        $container->get(ResponseFactoryInterface::class)
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