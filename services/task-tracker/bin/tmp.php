<?php

use Chirickello\Package\Consumer\ConsumerHandlerInterface;
use Chirickello\Package\Consumer\ConsumerInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

/** @var ConsumerInterface $consumer */
$consumer = $container->get(ConsumerInterface::class);
/** @var ConsumerHandlerInterface $handler */
$handler = $container->get(ConsumerHandlerInterface::class);

$handler->handle('{"event":"user.added","data":{"userId":"86f7f517-b723-4006-affe-f53819ce6c5d","login":"popka3","email":"popka3@chirickello.inc"},"version":1}');