<?php

/** @var ContainerInterface $container */

use Ddrv\ServerRequestWizard\FileReader;
use Ddrv\ServerRequestWizard\ServerRequestWizard;
use Psr\Container\ContainerInterface;
use Slim\App;

$container = require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

/** @var ServerRequestWizard $wizard */
$wizard = $container->get(ServerRequestWizard::class);

/** @var App $app */
$app = $container->get(App::class);

$request = $wizard->create(
    $_GET,
    $_POST,
    $_SERVER,
    $_COOKIE,
    $_FILES,
    new FileReader('php://input')
);

$app->run($request);
