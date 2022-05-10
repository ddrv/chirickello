<?php

declare(strict_types=1);

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use Chirickello\Package\Consumer\RabbitMQ\Consumer;
use Chirickello\Package\ConsumerEventHandler\ConsumerEventHandler;
use Chirickello\Package\Event\SalaryPaid\SalaryPaid;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\EventPacker\EventPacker;
use Chirickello\Package\EventSchemaRegistry\EventSchemaRegistry;
use Chirickello\Sender\Listener\SendDailySalaryReportListener;
use Chirickello\Sender\Listener\SetMailSender;
use Chirickello\Sender\Listener\SaveCreatedUserListener;
use Chirickello\Sender\Repo\UserRepo\UserPdoRepo;
use Chirickello\Sender\Repo\UserRepo\UserRepo;
use Ddrv\Container\Container;
use Ddrv\Env\Env;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$root = __DIR__;
$container = new Container();

$container->value('root', $root);
$container->value('db', implode(DIRECTORY_SEPARATOR, [$root, 'var', 'data', 'database.sqlite3']));

$container->service(Env::class, function () {
    return new Env('');
});

$container->service(PDO::class, function (ContainerInterface $container) {
    $pdo = new PDO('sqlite:' . $container->get('db'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
});

$container->service(UserPdoRepo::class, function (ContainerInterface $container) {
    return new UserPdoRepo(
        $container->get(PDO::class)
    );
});
$container->bind(UserRepo::class, UserPdoRepo::class);

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

$container->service(SaveCreatedUserListener::class, function (ContainerInterface $container) {
    return new SaveCreatedUserListener(
        $container->get(UserRepo::class)
    );
});

$container->service(SendDailySalaryReportListener::class, function (ContainerInterface $container) {
    return new SendDailySalaryReportListener(
        $container->get(UserRepo::class),
        $container->get(Environment::class),
        $container->get(Mailer::class)
    );
});

$container->service(SetMailSender::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new SetMailSender(
        $env->get('SENDER_EMAIL'),
        $env->get('SENDER_NAME')
    );
});

$container->service(EventDispatcher::class, function () {
    return new EventDispatcher();
});
$container->bind(EventDispatcherInterface::class, EventDispatcher::class);

$container->service(TransportInterface::class, function (ContainerInterface $container) {
    return Transport::fromDsn(
        $container->get(Env::class)->get('MAILER_DSN'),
        $container->get(EventDispatcherInterface::class)
    );
});

$container->service(Mailer::class, function (ContainerInterface $container) {
    return new Mailer($container->get(TransportInterface::class));
});

$container->service(EventSchemaRegistry::class, function () {
    return new EventSchemaRegistry();
});

$container->service(EventPacker::class, function (ContainerInterface $container) {
    return new EventPacker(
        $container->get(EventSchemaRegistry::class)
    );
});

$container->service(Consumer::class, function (ContainerInterface $container) {
    /** @var Env $env */
    $env = $container->get(Env::class);
    return new Consumer(
        $env->get('RABBITMQ_DSN'),
        'sender'
    );
});
$container->bind(ConsumerInterface::class, Consumer::class);

$container->service(ConsumerEventHandler::class, function (ContainerInterface $container) {
    return new ConsumerEventHandler(
        $container->get(EventPacker::class),
        $container->get(EventDispatcherInterface::class),
        [
            UserAdded::class,
        ]
    );
});
$container->bind(ConsumerHandlerInterface::class, ConsumerEventHandler::class);

/** @var EventDispatcher $eventDispatcher */
$eventDispatcher = $container->get(EventDispatcher::class);
$eventDispatcher->addListener(MessageEvent::class, $container->get(SetMailSender::class));
$eventDispatcher->addListener(SalaryPaid::class, $container->get(SendDailySalaryReportListener::class));
$eventDispatcher->addListener(UserAdded::class, $container->get(SaveCreatedUserListener::class));

return $container;