<?php

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use Chirickello\Package\Consumer\Kafka\Consumer;
use Chirickello\Package\ConsumerEventHandler\ConsumerEventHandler;
use Chirickello\Package\ConsumerLoggedHandler\ConsumerLoggedHandler;
use Chirickello\Package\Event\TaskAdded\TaskAdded;
use Chirickello\Package\Event\TaskAssigned\TaskAssigned;
use Chirickello\Package\Event\TaskCompleted\TaskCompleted;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\Event\UserRolesAssigned\UserRolesAssigned;
use Chirickello\Package\EventPacker\EventPacker;
use Chirickello\Package\EventSchemaRegistry\EventSchemaRegistry;
use Chirickello\Package\Listener\ProduceEventListener\ProduceEventListener;
use Chirickello\Package\LoggerFile\LoggerFile;
use Chirickello\Package\Middleware\AuthByToken\AuthByTokenMiddleware;
use Chirickello\Package\Middleware\AuthRequired\AuthRequiredMiddleware;
use Chirickello\Package\Middleware\RoleAccess\RoleAccessMiddlewareFactory;
use Chirickello\Package\Middleware\ScopeAccess\ScopeAccessMiddlewareFactory;
use Chirickello\Package\Producer\ProducerInterface;
use Chirickello\Package\Producer\Kafka\Producer;
use Chirickello\Package\Timer\ForcedTimer;
use Chirickello\Package\Timer\RealTimer;
use Chirickello\Package\Timer\TimerInterface;
use Chirickello\TaskTracker\Handler\TaskCreateHandler;
use Chirickello\TaskTracker\Handler\TaskShowHandler;
use Chirickello\TaskTracker\Handler\TasksListHandler;
use Chirickello\TaskTracker\Handler\TasksShuffleHandler;
use Chirickello\TaskTracker\Handler\TaskUpdateHandler;
use Chirickello\TaskTracker\Listener\UserAddListener;
use Chirickello\TaskTracker\Listener\UserRolesAssignListener;
use Chirickello\TaskTracker\Middleware\SaveUser;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskPdoRepo;
use Chirickello\TaskTracker\Repo\TaskRepo\TaskRepo;
use Chirickello\TaskTracker\Repo\UserRepo\UserPdoRepo;
use Chirickello\TaskTracker\Repo\UserRepo\UserRepo;
use Chirickello\TaskTracker\Transformer\TaskTransformer;
use Ddrv\Container\Container;
use Ddrv\Env\Env;
use Ddrv\Http\Client\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
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

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$container = new Container();

$root = __DIR__;
$container->value('root', $root);
$container->value('db', implode(DIRECTORY_SEPARATOR, [$root, 'var', 'data', 'task-tracker.sqlite3']));

// ENV
$container->service(Env::class, function (ContainerInterface $container) {
    return new Env(
        $container->get('root') . DIRECTORY_SEPARATOR . '.env'
    );
});

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

$container->service(TaskPdoRepo::class, function (ContainerInterface $container) {
    return new TaskPdoRepo(
        $container->get(PDO::class)
    );
});
$container->bind(TaskRepo::class, TaskPdoRepo::class);

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

// HTTP-CLIENT
$container->service(Client::class, function (ContainerInterface $container) {
    return new Client(
        $container->get(ResponseFactoryInterface::class),
        10
    );
});
$container->bind(ClientInterface::class, Client::class);

// MIDDLEWARE
$container->service(AuthByTokenMiddleware::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    $authHost = (string)$env->get('AUTH_HOST');
    return new AuthByTokenMiddleware(
        $container->get(RequestFactoryInterface::class),
        $container->get(UriFactoryInterface::class),
        $container->get(ClientInterface::class),
        $authHost
    );
});

$container->service(AuthRequiredMiddleware::class, function (ContainerInterface $container) {
    return new AuthRequiredMiddleware(
        $container->get(RequestFactoryInterface::class)
    );
});

$container->service(ScopeAccessMiddlewareFactory::class, function (ContainerInterface $container) {
    return new ScopeAccessMiddlewareFactory(
        $container->get(ResponseFactoryInterface::class)
    );
});

$container->service(RoleAccessMiddlewareFactory::class, function (ContainerInterface $container) {
    return new RoleAccessMiddlewareFactory(
        $container->get(ResponseFactoryInterface::class)
    );
});

$container->service(SaveUser::class, function (ContainerInterface $container) {
    return new SaveUser(
        $container->get(UserRepo::class)
    );
});

// TRANSFORMERS
$container->service(TaskTransformer::class, function(ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new TaskTransformer(
        $container->get(UserRepo::class),
        $env->get('TIMEZONE', 'UTC')
    );
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
        'task-tracker',
        $container->get(TimerInterface::class)
    );
});
$container->bind(LoggerInterface::class, LoggerFile::class);

// CONSUMER
$container->service(Consumer::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new Consumer(
        $env->get('KAFKA_DSN'),
        'task-tracker'
    );
});
$container->bind(ConsumerInterface::class, Consumer::class);

$container->service(ConsumerEventHandler::class, function (ContainerInterface $container) {
    return new ConsumerEventHandler(
        $container->get(EventPacker::class),
        $container->get(EventDispatcherInterface::class),
        [
            UserAdded::class,
            UserRolesAssigned::class,
        ]
    );
});
$container->service(ConsumerHandlerInterface::class, function (ContainerInterface $container) {
    return new ConsumerLoggedHandler(
        $container->get(ConsumerEventHandler::class),
        $container->get(LoggerInterface::class)
    );
});

// PRODUCER
$container->service(Producer::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new Producer(
        $env->get('KAFKA_DSN'),
        'task-tracker'
    );
});
$container->bind(ProducerInterface::class, Producer::class);

// HANDLERS
$container->service(TaskCreateHandler::class, function (ContainerInterface $container) {
    return new TaskCreateHandler(
        $container->get(ResponseFactoryInterface::class),
        $container->get(EventDispatcherInterface::class),
        $container->get(TimerInterface::class),
        $container->get(TaskRepo::class),
        $container->get(UserRepo::class),
        $container->get(TaskTransformer::class)
    );
});
$container->service(TaskShowHandler::class, function (ContainerInterface $container) {
    return new TaskShowHandler(
        $container->get(ResponseFactoryInterface::class),
        $container->get(TaskRepo::class),
        $container->get(TaskTransformer::class)
    );
});
$container->service(TasksListHandler::class, function (ContainerInterface $container) {
    return new TasksListHandler(
        $container->get(ResponseFactoryInterface::class),
        $container->get(TaskRepo::class),
        $container->get(TaskTransformer::class)
    );
});
$container->service(TasksShuffleHandler::class, function (ContainerInterface $container) {
    return new TasksShuffleHandler(
        $container->get(ResponseFactoryInterface::class),
        $container->get(EventDispatcherInterface::class),
        $container->get(TimerInterface::class),
        $container->get(TaskRepo::class),
        $container->get(UserRepo::class)
    );
});
$container->service(TaskUpdateHandler::class, function (ContainerInterface $container) {
    return new TaskUpdateHandler(
        $container->get(ResponseFactoryInterface::class),
        $container->get(EventDispatcherInterface::class),
        $container->get(TimerInterface::class),
        $container->get(TaskRepo::class),
        $container->get(TaskTransformer::class)
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

    /** @var ScopeAccessMiddlewareFactory $mwScopeFactory */
    $mwScopeFactory = $container->get(ScopeAccessMiddlewareFactory::class);
    /** @var RoleAccessMiddlewareFactory $mwRoleFactory */
    $mwRoleFactory = $container->get(RoleAccessMiddlewareFactory::class);

    $router->group('', function (RouteCollectorProxyInterface $router) use ($mwScopeFactory, $mwRoleFactory) {
        $router->group('', function (RouteCollectorProxyInterface $router) {
            $router->get('/tasks', TasksListHandler::class)->setName('task.list');
            $router->post('/tasks', TaskCreateHandler::class)->setName('task.create');
            $router->get('/tasks/{id}', TaskShowHandler::class)->setName('task.show');
            $router->patch('/tasks/{id}', TaskUpdateHandler::class)->setName('task.update');
        });

        $router->group('', function (RouteCollectorProxyInterface $router) {
            $router->post('/processes/tasks_shuffle', TasksShuffleHandler::class)->setName('process.shuffle');
        })->addMiddleware($mwRoleFactory->make(['admin']));
    })
        ->add(SaveUser::class)
        ->addMiddleware($mwScopeFactory->make(['tasks']))
        ->add(AuthRequiredMiddleware::class)
        ->add(AuthByTokenMiddleware::class)
    ;
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
    $app->addBodyParsingMiddleware();
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

// EVENT PACKER
$container->service(EventSchemaRegistry::class, function () {
    return new EventSchemaRegistry();
});

$container->service(EventPacker::class, function (ContainerInterface $container) {
    return new EventPacker(
        $container->get(EventSchemaRegistry::class)
    );
});

// EVENT LISTENERS
$container->service(UserAddListener::class, function (ContainerInterface $container) {
    return new UserAddListener(
        $container->get(UserRepo::class)
    );
});

$container->service(UserRolesAssignListener::class, function (ContainerInterface $container) {
    return new UserRolesAssignListener(
        $container->get(UserRepo::class)
    );
});

$container->service(ProduceEventListener::class, function (ContainerInterface $container) {
    $listener = new ProduceEventListener(
        $container->get(LoggerInterface::class),
        $container->get(EventPacker::class),
        $container->get(ProducerInterface::class),
        $container->get(TimerInterface::class)
    );
    $listener->bindEventToTopic(TaskAdded::class, 'task-stream');
    $listener->bindEventToTopic(TaskAssigned::class, 'task-workflow');
    $listener->bindEventToTopic(TaskCompleted::class, 'task-workflow');
    return $listener;
});

// EVENT DISPATCHER
$container->service(EventDispatcher::class, function () {
    return new EventDispatcher();
});
$container->bind(EventDispatcherInterface::class, EventDispatcher::class);

/** @var EventDispatcher $eventDispatcher */
$eventDispatcher = $container->get(EventDispatcher::class);
$eventDispatcher->addListener(UserAdded::class, $container->get(UserAddListener::class));
$eventDispatcher->addListener(UserRolesAssigned::class, $container->get(UserRolesAssignListener::class));
$eventDispatcher->addListener(TaskAdded::class, $container->get(ProduceEventListener::class));
$eventDispatcher->addListener(TaskAssigned::class, $container->get(ProduceEventListener::class));
$eventDispatcher->addListener(TaskCompleted::class, $container->get(ProduceEventListener::class));

return $container;