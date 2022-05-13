<?php

use Chirickello\Accounting\Consumer\FailedEventHandler;
use Chirickello\Accounting\Consumer\QueueConsumer;
use Chirickello\Accounting\Consumer\TopicRoutedConsumer;
use Chirickello\Accounting\Consumer\TopicRoutedConsumerHandler;
use Chirickello\Accounting\Event\BalanceCollected;
use Chirickello\Accounting\Event\EventFailed;
use Chirickello\Accounting\Event\WorkdayOver;
use Chirickello\Accounting\Handler\TopManagementProfitHandler;
use Chirickello\Accounting\Handler\UserBalanceDetailsHandler;
use Chirickello\Accounting\Handler\UserBalanceHandler;
use Chirickello\Accounting\Listener\AddBalanceListener;
use Chirickello\Accounting\Listener\CreateTaskListener;
use Chirickello\Accounting\Listener\WorkdayClose;
use Chirickello\Accounting\Listener\FailedEventListener;
use Chirickello\Accounting\Listener\LazyLoadListener;
use Chirickello\Accounting\Listener\PayoutListener;
use Chirickello\Accounting\Listener\SaveCreatedUserListener;
use Chirickello\Accounting\Listener\UserRolesAssignListener;
use Chirickello\Accounting\Listener\WriteOffBalanceListener;
use Chirickello\Accounting\Middleware\SaveUser;
use Chirickello\Accounting\Producer\QueueProducer;
use Chirickello\Accounting\Producer\TopicRoutedProducer;
use Chirickello\Accounting\Queue\PdoQueue;
use Chirickello\Accounting\Queue\Queue;
use Chirickello\Accounting\Repo\TaskRepo\TaskPdoRepo;
use Chirickello\Accounting\Repo\TaskRepo\TaskRepo;
use Chirickello\Accounting\Repo\UserRepo\UserPdoRepo;
use Chirickello\Accounting\Repo\UserRepo\UserRepo;
use Chirickello\Accounting\Service\BankSdk\BankSdk;
use Chirickello\Accounting\Service\BankSdk\StuppidBankSdk;
use Chirickello\Accounting\Service\EventDispatcher\EventDispatcherManager;
use Chirickello\Accounting\Service\EventDispatcher\RetryEventDispatcher;
use Chirickello\Accounting\Service\RetryStrategy\FibonacciStrategy;
use Chirickello\Accounting\Service\RetryStrategy\RetryStrategy;
use Chirickello\Accounting\Service\UserBalanceService\UserBalanceService;
use Chirickello\Accounting\Service\Workday\Workday;
use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use Chirickello\Package\Consumer\Kafka\Consumer;
use Chirickello\Package\ConsumerEventHandler\ConsumerEventHandler;
use Chirickello\Package\ConsumerLoggedHandler\ConsumerLoggedHandler;
use Chirickello\Package\Event\SalaryPaid\SalaryPaid;
use Chirickello\Package\Event\TaskAdded\TaskAdded;
use Chirickello\Package\Event\TaskAssigned\TaskAssigned;
use Chirickello\Package\Event\TaskCompleted\TaskCompleted;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\Event\UserRolesAssigned\UserRolesAssigned;
use Chirickello\Package\EventPacker\EventPacker;
use Chirickello\Package\EventPacker\EventTransformerInterface;
use Chirickello\Package\EventSchemaRegistry\EventSchemaRegistry;
use Chirickello\Package\Listener\ProduceEventListener\ProduceEventListener;
use Chirickello\Package\LoggerFile\LoggerFile;
use Chirickello\Package\Middleware\AuthByToken\AuthByTokenMiddleware;
use Chirickello\Package\Middleware\AuthRequired\AuthRequiredMiddleware;
use Chirickello\Package\Middleware\RoleAccess\RoleAccessMiddlewareFactory;
use Chirickello\Package\Middleware\ScopeAccess\ScopeAccessMiddlewareFactory;
use Chirickello\Package\Producer\Kafka\Producer;
use Chirickello\Package\Producer\ProducerInterface;
use Chirickello\Package\Timer\ForcedTimer;
use Chirickello\Package\Timer\RealTimer;
use Chirickello\Package\Timer\TimerInterface;
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
$container->value('db', implode(DIRECTORY_SEPARATOR, [$root, 'var', 'data', 'accounting.sqlite3']));

// ENV

$container->service(Env::class, function (ContainerInterface $container) {
    return new Env(
        $container->get('root') . DIRECTORY_SEPARATOR . '.env'
    );
});

// LOGGER
$container->service(LoggerFile::class, function (ContainerInterface $container) {
    return new LoggerFile(
        implode(DIRECTORY_SEPARATOR, ['', 'var', 'log', 'app', 'app.log']),
        'accounting',
        $container->get(TimerInterface::class)
    );
});
$container->bind(LoggerInterface::class, LoggerFile::class);

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

$container->service(Workday::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new Workday(
        $container->get(TimerInterface::class),
        $env->get('WORKDAY_OVER'),
        $env->get('TIMEZONE', 'UTC'),
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

// EVENT SCHEMA REGISTRY
$container->service(EventSchemaRegistry::class, function (ContainerInterface $container) {
    $root = $container->get('root');
    $registry = new EventSchemaRegistry();
    $registry->addDirectory(implode(DIRECTORY_SEPARATOR, [$root, 'event-schemas']));
    return $registry;
});

// EVENT PACKER
$container->service(EventPacker::class, function (ContainerInterface $container) {
    $packer = new EventPacker(
        $container->get(EventSchemaRegistry::class)
    );
    $packer->addTransformer('event.failed', new class implements EventTransformerInterface {
        public function transform(object $event): object
        {
            $data = $event->data;
            return new EventFailed($data->payload, $data->reason, $data->attempt);
        }
    });
    $packer->addTransformer('workday.over', new class implements EventTransformerInterface {
        public function transform(object $event): object
        {
            $data = $event->data;
            return new WorkdayOver(DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $data->time));
        }
    });
    $packer->addTransformer('balance.collected', new class implements EventTransformerInterface {
        public function transform(object $event): object
        {
            $data = $event->data;
            return new BalanceCollected($data->userId);
        }
    });
    return $packer;
});

// LISTENER
$container->service(FailedEventListener::class, function (ContainerInterface $container) {
    return new FailedEventListener(
        $container->get(Queue::class),
        $container->get(RetryStrategy::class),
        $container->get(EventPacker::class),
        'failed'
    );
});

$container->service(SaveCreatedUserListener::class, function (ContainerInterface $container) {
    return new SaveCreatedUserListener(
        $container->get(UserRepo::class)
    );
});

$container->service(UserRolesAssignListener::class, function (ContainerInterface $container) {
    return new UserRolesAssignListener(
        $container->get(UserRepo::class)
    );
});

$container->service(WorkdayClose::class, function (ContainerInterface $container) {
    /** @var EventDispatcherManager $eventDispatcherManager */
    $eventDispatcherManager = $container->get(EventDispatcherManager::class);
    return new WorkdayClose(
        $container->get(UserBalanceService::class),
        $eventDispatcherManager->producing(),
    );
});

$container->service(PayoutListener::class, function (ContainerInterface $container) {
    /** @var EventDispatcherManager $eventDispatcherManager */
    $eventDispatcherManager = $container->get(EventDispatcherManager::class);
    return new PayoutListener(
        $container->get(UserBalanceService::class),
        $container->get(Workday::class),
        $container->get(TimerInterface::class),
        $container->get(BankSdk::class),
        $eventDispatcherManager->producing(),
    );
});

$container->service(CreateTaskListener::class, function (ContainerInterface $container) {
    return new CreateTaskListener($container->get(TaskRepo::class));
});

$container->service(WriteOffBalanceListener::class, function (ContainerInterface $container) {
    return new WriteOffBalanceListener(
        $container->get(UserRepo::class),
        $container->get(TaskRepo::class),
        $container->get(UserBalanceService::class),
        $container->get(TimerInterface::class)
    );
});

$container->service(AddBalanceListener::class, function (ContainerInterface $container) {
    return new AddBalanceListener(
        $container->get(UserRepo::class),
        $container->get(TaskRepo::class),
        $container->get(UserBalanceService::class),
        $container->get(TimerInterface::class)
    );
});

$container->service(ProduceEventListener::class, function (ContainerInterface $container) {
    $listener = new ProduceEventListener(
        $container->get(LoggerInterface::class),
        $container->get(EventPacker::class),
        $container->get(ProducerInterface::class),
        $container->get(TimerInterface::class)
    );
    $listener->bindEventToTopic(WorkdayOver::class, 'schedule');
    $listener->bindEventToTopic(BalanceCollected::class, 'finances');
    $listener->bindEventToTopic(SalaryPaid::class, 'notifications');
    return $listener;
});

// EVENT DISPATCHER
$container->service(EventDispatcherManager::class, function (ContainerInterface $container) {
    $producing = new EventDispatcher();
    $produceEventListener = new LazyLoadListener($container, ProduceEventListener::class);
    $producing->addListener(WorkdayOver::class, $produceEventListener);
    $producing->addListener(SalaryPaid::class, $produceEventListener);
    $producing->addListener(BalanceCollected::class, $produceEventListener);

    $listening = new EventDispatcher();
    $listening->addListener(EventFailed::class, new LazyLoadListener($container, FailedEventListener::class));
    $listening->addListener(TaskAdded::class, new LazyLoadListener($container, CreateTaskListener::class));
    $listening->addListener(UserAdded::class, new LazyLoadListener($container, SaveCreatedUserListener::class));
    $listening->addListener(UserRolesAssigned::class, new LazyLoadListener($container, UserRolesAssignListener::class));
    $listening->addListener(WorkdayOver::class, new LazyLoadListener($container, WorkdayClose::class));
    $listening->addListener(TaskAssigned::class, new LazyLoadListener($container, WriteOffBalanceListener::class));
    $listening->addListener(TaskCompleted::class, new LazyLoadListener($container, AddBalanceListener::class));
    $listening->addListener(BalanceCollected::class, new LazyLoadListener($container, PayoutListener::class));

    $retrying = new RetryEventDispatcher($listening, $container->get(EventPacker::class));
    return new EventDispatcherManager($producing, $listening, $retrying);
});

// SERVICES
$container->service(UserBalanceService::class, function (ContainerInterface $container) {
    return new UserBalanceService(
        $container->get(PDO::class)
    );
});

$container->service(FibonacciStrategy::class, function (ContainerInterface $container) {
    return new FibonacciStrategy(
        $container->get(TimerInterface::class)
    );
});
$container->bind(RetryStrategy::class, FibonacciStrategy::class);

$container->service(StuppidBankSdk::class, function () {
    return new StuppidBankSdk(15.0);
});
$container->bind(BankSdk::class, StuppidBankSdk::class);

// QUEUE
$container->service(PdoQueue::class, function (ContainerInterface $container) {
    return new PdoQueue(
        $container->get(PDO::class),
        $container->get(TimerInterface::class)
    );
});
$container->bind(Queue::class, PdoQueue::class);

// CONSUMER
$container->service(ConsumerHandlerInterface::class, function (ContainerInterface $container) {
    /** @var EventDispatcherManager $eventDispatcherManager */
    $eventDispatcherManager = $container->get(EventDispatcherManager::class);
    $eventHandler = new ConsumerEventHandler(
        $container->get(EventPacker::class),
        $eventDispatcherManager->retrying(),
        [
            UserAdded::class,
            UserRolesAssigned::class,
            TaskAdded::class,
            TaskAssigned::class,
            TaskCompleted::class,
            WorkdayOver::class,
            BalanceCollected::class,
        ]
    );

    $eventHandlerLogged = new ConsumerLoggedHandler(
        $eventHandler,
        $container->get(LoggerInterface::class)
    );

    $failedEventHandler = new FailedEventHandler(
        $container->get(EventPacker::class),
        $eventDispatcherManager->listening()
    );

    $failedEventHandlerLogged = new ConsumerLoggedHandler(
        $failedEventHandler,
        $container->get(LoggerInterface::class)
    );

    $routedHandler = new TopicRoutedConsumerHandler(
        $eventHandlerLogged,
        'user-stream',
        'roles',
        'task-stream',
        'task-workflow',
        'schedule',
        'finances'
    );
    $routedHandler->addHandler($failedEventHandlerLogged, 'failed');
    return $routedHandler;
});

$container->service(QueueConsumer::class, function (ContainerInterface $container) {
    return new QueueConsumer(
        $container->get(Queue::class)
    );
});

$container->service(Consumer::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new Consumer(
        $env->get('KAFKA_DSN'),
        'accounting'
    );
});

$container->service(TopicRoutedConsumer::class, function (ContainerInterface $container) {
    $consumer = new TopicRoutedConsumer($container->get(QueueConsumer::class), 'failed', 'schedule', 'finances');
    $consumer->addConsumer($container->get(Consumer::class), 'user-stream', 'roles', 'task-stream', 'task-workflow');
    return $consumer;
});
$container->bind(ConsumerInterface::class, TopicRoutedConsumer::class);

// PRODUCER
$container->service(Producer::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new Producer(
        $env->get('KAFKA_DSN'),
        'accounting'
    );
});

$container->service(QueueProducer::class, function (ContainerInterface $container) {
    return new QueueProducer(
        $container->get(Queue::class),
        $container->get(TimerInterface::class),
        'accounting'
    );
});

$container->service(TopicRoutedProducer::class, function (ContainerInterface $container) {
    $queueProducer = $container->get(QueueProducer::class);
    $router = new TopicRoutedProducer($queueProducer, 'schedule', 'finances');
    $router->addProducer($container->get(Producer::class), 'notifications');
    return $router;
});
$container->bind(ProducerInterface::class, TopicRoutedProducer::class);

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

// HANDLERS
$container->service(TopManagementProfitHandler::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new TopManagementProfitHandler(
        $container->get(ResponseFactoryInterface::class),
        $container->get(Workday::class),
        $container->get(UserBalanceService::class),
        $env->get('TIMEZONE', 'UTC')
    );
});
$container->service(UserBalanceHandler::class, function (ContainerInterface $container) {
    return new UserBalanceHandler(
        $container->get(ResponseFactoryInterface::class)
    );
});
$container->service(UserBalanceDetailsHandler::class, function (ContainerInterface $container) {
    return new UserBalanceDetailsHandler(
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
    /** @var ScopeAccessMiddlewareFactory $mwScopeFactory */
    $mwScopeFactory = $container->get(ScopeAccessMiddlewareFactory::class);
    /** @var RoleAccessMiddlewareFactory $mwRoleFactory */
    $mwRoleFactory = $container->get(RoleAccessMiddlewareFactory::class);

    $router->group('', function (RouteCollectorProxyInterface $router) use ($mwScopeFactory, $mwRoleFactory) {
        $router->group('', function (RouteCollectorProxyInterface $router) {
            $router->get('/balance', UserBalanceHandler::class)->setName('balance.show');
            $router->get('/balance-details', UserBalanceHandler::class)->setName('balance-details.show');
        });

        $router->group('', function (RouteCollectorProxyInterface $router) {
            $router->get('/top-management-profit', TopManagementProfitHandler::class)->setName('top-management-profit.show');
        })->addMiddleware($mwRoleFactory->make(['admin', 'accountant']));
    })
        ->add(SaveUser::class)
        ->addMiddleware($mwScopeFactory->make(['accounting']))
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