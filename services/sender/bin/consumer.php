<?php

use Chirickello\Package\Consumer\ConsumerInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

/** @var ConsumerInterface $consumer */
$consumer = $container->get(ConsumerInterface::class);

$topic = $argv[1] ?? null;

if (!is_string($topic)) {
    echo 'use php consumer.php {topic}' . PHP_EOL;
    exit(1);
}
$consumer->consume($topic);
