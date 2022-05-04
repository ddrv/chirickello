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
    login NULL,
    roles NULL,
    CONSTRAINT pk PRIMARY KEY (id)
);
SQL;
    $pdo->exec($sql);

    $sql = <<<SQL
CREATE TABLE tasks (
    id TEXT NOT NULL,
    description TEXT NOT NULL,
    is_completed INTEGER NOT NULL,
    author_id TEXT NOT NULL,
    assigned_to TEXT NOT NULL,
    created_at TEXT NOT NULL,
    CONSTRAINT pk PRIMARY KEY (id),
    CONSTRAINT fk_assigned_to FOREIGN KEY (assigned_to) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_author_id FOREIGN KEY (author_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT
);
SQL;
    $pdo->exec($sql);

    $sql = <<<SQL
CREATE INDEX tasks_is_completed_idx ON tasks (is_completed);
SQL;
    $pdo->exec($sql);

    $sql = <<<SQL
CREATE INDEX tasks_author_id_idx ON tasks (author_id);
SQL;
    $pdo->exec($sql);

    $sql = <<<SQL
CREATE INDEX tasks_assigned_to_idx ON tasks (assigned_to);
SQL;
    $pdo->exec($sql);

    $sql = <<<SQL
CREATE INDEX tasks_created_at_idx ON tasks (created_at);
SQL;
    $pdo->exec($sql);
}

