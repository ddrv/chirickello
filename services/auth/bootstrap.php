<?php

use Chirickello\Auth\Handler\Api\V1\MeEndpoint;
use Chirickello\Auth\Handler\OAuth\OAuth2Authorize;
use Chirickello\Auth\Handler\OAuth\OAuth2Token;
use Chirickello\Auth\Middleware\AuthRequiredApiMiddleware;
use Chirickello\Auth\Middleware\CorsMiddleware;
use Chirickello\Auth\Middleware\TokenMiddleware;
use Chirickello\Auth\Repo\ClientRepo\ClientEnvRepo;
use Chirickello\Auth\Repo\ClientRepo\ClientRepo;
use Chirickello\Auth\Repo\UserRepo\UserPdoRepo;
use Chirickello\Auth\Repo\UserRepo\UserRepo;
use Chirickello\Auth\Service\UserService\UserService;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\Event\UserRolesAssigned\UserRolesAssigned;
use Chirickello\Package\EventPacker\EventPacker;
use Chirickello\Package\EventSchemaRegistry\EventSchemaRegistry;
use Chirickello\Package\Listener\ProduceEventListener\ProduceEventListener;
use Chirickello\Package\LoggerFile\LoggerFile;
use Chirickello\Package\Producer\ProducerInterface;
use Chirickello\Package\Producer\Kafka\Producer;
use Chirickello\Package\Timer\ForcedTimer;
use Chirickello\Package\Timer\RealTimer;
use Chirickello\Package\Timer\TimerInterface;
use Ddrv\Container\Container;
use Ddrv\Env\Env;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\CallableResolver;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteParser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$container = new Container();

$root = __DIR__;
$container->value('root', __DIR__);
$container->value('db', implode(DIRECTORY_SEPARATOR, [$root, 'var', 'data', 'auth.sqlite3']));

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

// TIMER
$container->service(TimerInterface::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    $debug = (bool)((int)$env->get('DEBUG'));
    $speed = (int)$env->get('TIMER_SPEED');
    if ($speed === 0) {
        $speed = 1;
    }
    $begin = $env->get('TIMER_BEGIN');
    if (!preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/u', $begin)) {
        $debug = false;
    }
    if (!$debug || $speed === 1) {
        return new RealTimer();
    }
    return new ForcedTimer($begin, $speed);
});

// LOGGER
$container->service(LoggerFile::class, function (ContainerInterface $container) {
    return new LoggerFile(
        implode(DIRECTORY_SEPARATOR, ['', 'var', 'log', 'app', 'app.log']),
        'auth',
        $container->get(TimerInterface::class)
    );
});
$container->bind(LoggerInterface::class, LoggerFile::class);

// PRODUCER
$container->service(Producer::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new Producer(
        $env->get('KAFKA_DSN'),
        'auth'
    );
});
$container->bind(ProducerInterface::class, Producer::class);

// EVENT PACKER
$container->service(EventSchemaRegistry::class, function () {
    return new EventSchemaRegistry();
});

$container->service(EventPacker::class, function (ContainerInterface $container) {
    return new EventPacker(
        $container->get(EventSchemaRegistry::class)
    );
});

// EVENT DISPATCHER
$container->service(ProduceEventListener::class, function (ContainerInterface $container) {
    $listener = new ProduceEventListener(
        $container->get(LoggerInterface::class),
        $container->get(EventPacker::class),
        $container->get(ProducerInterface::class),
        $container->get(TimerInterface::class)
    );
    $listener->bindEventToTopic(UserAdded::class, 'user-stream');
    $listener->bindEventToTopic(UserRolesAssigned::class, 'roles');
    return $listener;
});

$container->service(EventDispatcher::class, function () {
    return new EventDispatcher();
});
$container->bind(EventDispatcherInterface::class, EventDispatcher::class);

// DATABASE

$container->service(PDO::class, function (ContainerInterface $container) {
    $pdo = new PDO('sqlite:' . $container->get('db'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
});

// REPO

$container->service(UserPdoRepo::class, function (ContainerInterface $container) {
    return new UserPdoRepo(
        $container->get(PDO::class)
    );
});
$container->bind(UserRepo::class, UserPdoRepo::class);

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

// SERVICE

$container->service(UserService::class, function (ContainerInterface $container) {
    return new UserService(
        $container->get(UserRepo::class),
        $container->get(EventDispatcherInterface::class)
    );
});

// MIDDLEWARE

$container->service(TokenMiddleware::class, function (ContainerInterface $container) {
    return new TokenMiddleware(
        $container->get(UserService::class)
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
        $container->get(UserService::class)
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
    $router = new RouteCollector(
        $container->get(ResponseFactoryInterface::class),
        $container->get(CallableResolverInterface::class),
        $container,
        $container->get(InvocationStrategyInterface::class)
    );
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

    return $router;
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

/** @var EventDispatcher $eventDispatcher */
$eventDispatcher = $container->get(EventDispatcher::class);
$eventDispatcher->addListener(UserAdded::class, $container->get(ProduceEventListener::class));
$eventDispatcher->addListener(UserRolesAssigned::class, $container->get(ProduceEventListener::class));

return $container;