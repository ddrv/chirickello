<?php

declare(strict_types=1);

use Chirickello\Accounting\Listener\WorkdayClose;
use Chirickello\Accounting\Service\EventDispatcher\EventDispatcherManager;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\Event\UserRolesAssigned\UserRolesAssigned;
use Chirickello\Package\EventPacker\EventPacker;
use Chirickello\Package\Timer\TimerInterface;
use Ddrv\Env\Env;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/** @var ContainerInterface $container */
$container = require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

/** @var Env $env */
$env = $container->get(Env::class);
$timezone = new DateTimeZone($env->get('TIMEZONE', 'UTC'));

/** @var TimerInterface $timer */
$timer = $container->get(TimerInterface::class);

echo $timer->now()->setTimezone($timezone)->format('Y-m-d H:i:s') . PHP_EOL;