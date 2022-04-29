<?php

declare(strict_types=1);

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Service\UserService\UserService;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

do {
    $login = trim((string)readline('Enter login: '));
} while (!checkLogin($login));

do {
    $default = $login . '@chirickello.inc';
    $email = trim((string)readline('Enter email [' . $default . ']: '));
    if ($email === '') {
        $email = $default;
    }
} while (!checkEmail($email));

do {
    $isAdmin = strtolower(trim((string)readline('Is admin? [y/N]: ')));
    if ($isAdmin === '') {
        $isAdmin = 'n';
    }
} while (!checkBool($isAdmin));
$isAdmin = toBool($isAdmin);
$isManager = false;
$isAccountant = false;
$isDeveloper = false;

if (!$isAdmin) {
    do {
        $isManager = strtolower(trim((string)readline('Is manager? [y/N]: ')));
        if ($isManager === '') {
            $isManager = 'n';
        }
    } while (!checkBool($isManager));
    $isManager = toBool($isManager);

    do {
        $isAccountant = strtolower(trim((string)readline('Is accountant? [y/N]: ')));
        if ($isAccountant === '') {
            $isAccountant = 'n';
        }
    } while (!checkBool($isAccountant));
    $isAccountant = toBool($isAccountant);

    if ($isManager || $isAccountant) {
        do {
            $isDeveloper = strtolower(trim((string)readline('Is developer? [Y/n]: ')));
            if ($isDeveloper === '') {
                $isDeveloper = 'y';
            }
        } while (!checkBool($isDeveloper));
        $isDeveloper = toBool($isDeveloper);
    } else {
        $isDeveloper = true;
    }
}

/** @var UserService $userService */
$userService = $container->get(UserService::class);

$user = new User($login, $email);
if ($isAdmin) {
    $user->addRole('admin');
}
if ($isManager) {
    $user->addRole('manager');
}
if ($isAccountant) {
    $user->addRole('accountant');
}
if ($isDeveloper) {
    $user->addRole('developer');
}

try {
    $userService->save($user);
    // todo emit event
    echo sprintf('User %s with roles %s created!', $login, implode(', ', $user->getRoles())) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

function checkLogin(string $login): bool
{
    return (bool)preg_match('/^[a-z0-9]{1,32}$/ui', $login);
}

function checkEmail(string $email): bool
{
    $arr = explode('@', $email);
    return count($arr) === 2;
}

function checkBool(string $bool): bool
{
    return in_array($bool, ['y', 'n', 'yes', 'no', '1', '0']);
}

function toBool(string $bool): bool
{
    foreach (['y', 'yes', '1',] as $yes) {
        if ($bool === $yes) {
            return true;
        }
    }
    return false;
}