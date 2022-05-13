<?php

declare(strict_types=1);

use Chirickello\Accounting\Event\WorkdayOver;
use Chirickello\Accounting\Service\EventDispatcher\EventDispatcherManager;
use Chirickello\Accounting\Service\Workday\Workday;
use Chirickello\Package\Producer\ProducerInterface;
use Chirickello\Package\Timer\ForcedTimer;
use Chirickello\Package\Timer\TimerInterface;
use Ddrv\Env\Env;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$dir = implode(DIRECTORY_SEPARATOR, [$container->get('root'), 'var', 'data']);

/** @var Env $env */
$env = $container->get(Env::class);

/** @var TimerInterface $timer */
$timer = $container->get(TimerInterface::class);

/** @var EventDispatcherManager $eventDispatcherManager */
$eventDispatcherManager = $container->get(EventDispatcherManager::class);
$eventDispatcher = $eventDispatcherManager->producing();

/** @var Workday $workday */
$workday = $container->get(Workday::class);

$id = 'real';
if ($timer instanceof ForcedTimer) {
    $id = sprintf('forced-%sx%d', $timer->getBeginDate()->format('Ymd'), $timer->getSpeed());
}

$file = $dir . DIRECTORY_SEPARATOR . 'yesterday-' . $id . '.time';

echo $timer->now()->format('Y-m-d\TH:i:s') . PHP_EOL;

$end = DateTimeImmutable::createFromFormat(
    'U',
    $workday->workday($timer->now())->end->format('U')
);

$yesterday = null;
$time = time();
if (file_exists($file)) {
    try {
        $yesterday = trim(file_get_contents($file));
    } catch (Throwable $exception) {
    }
}
if (is_null($yesterday)) {
    $yesterday = $timer->now()->format('Y-m-d');
}

while (true) {
    $now = $timer->now();
    $today = $now->format('Y-m-d');
    if ($today !== $yesterday) {
        file_put_contents($file, $yesterday);
        $yesterday = $today;
    }
    if ($now > $end) {
        $workdayOverEvent = new WorkdayOver($now);
        $eventDispatcher->dispatch($workdayOverEvent);
        $end = $end->modify('+1 day');
    }
    usleep(5000);
}
