<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$file = $container->get('db');
if (!file_exists($file)) {
    $dir = pathinfo($file)['dirname'];
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    createDatabase($container);
}

function createDatabase(ContainerInterface $container)
{
    /** @var PDO $pdo */
    $pdo = $container->get(PDO::class);
    $sql = <<<SQL
CREATE TABLE users (
    id TEXT NOT NULL,
    login TEXT NULL,
    email TEXT NULL,
    CONSTRAINT pk PRIMARY KEY (id)
);
SQL;

    $pdo->exec($sql);
}

