<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$dir = $container->get('data');
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

if (!file_exists($container->get('db'))) {
    createDatabase($container);
}

function createDatabase(ContainerInterface $container)
{
    /** @var PDO $pdo */
    $pdo = $container->get(PDO::class);
    $sql = <<<SQL
CREATE TABLE users (
    id TEXT NOT NULL,
    login TEXT NOT NULL,
    email TEXT NOT NULL,
    roles TEXT NULL,
    CONSTRAINT pk PRIMARY KEY (id),
    CONSTRAINT login_unique UNIQUE (login),
    CONSTRAINT email_unique UNIQUE (email)
);
SQL;

    $pdo->exec($sql);
}

