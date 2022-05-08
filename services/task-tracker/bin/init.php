<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$empty = implode(DIRECTORY_SEPARATOR, [$root, 'dist', 'empty-database.sqlite3']);
$file = implode(DIRECTORY_SEPARATOR, [$root, 'var', 'data', 'database.sqlite3']);
if (!file_exists($file)) {
    $dir = pathinfo($file)['dirname'];
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    copy($empty, $file);
    chmod($file, 0777);
}
