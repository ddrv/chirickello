<?php

declare(strict_types=1);

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\UserNotFoundException;
use Chirickello\Auth\Service\UserService\UserService;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';

/** @var UserService $userService */
$userService = $container->get(UserService::class);

$firstUserRoles = ['admin', 'manager', 'accountant', 'developer'];

for ($i=0; $i < 10; $i++) {
    $login = generateUniqueLogin($userService);
    $email = $login . '@chirickello.inc';
    $user = new User($login, $email);
    if (array_key_exists($i, $firstUserRoles)) {
        $user->addRole($firstUserRoles[$i]);
    } else {
        $roles = generateRoles();
        foreach ($roles as $role) {
            $user->addRole($role);
        }
    }
    try {
        $userService->save($user);
        echo sprintf('created user %s [%s] with roles %s%s', $login, $email, implode(', ', $user->getRoles()), PHP_EOL);
    } catch (Throwable $e) {
    }
}

function generateUniqueLogin(UserService $userService): string
{
    $i = 0;
    do {
        $logins = ['user', 'popug', 'kesha', 'popka', 'parrot', 'argo', 'alf', 'boss', 'penny', 'peggy'];
        $prefix = $logins[array_rand($logins)];
        try {
            $login = $i === 0 ? $prefix : sprintf('%s%d', $prefix, $i);
            $userService->getByLogin($login);
            $exists = true;
        } catch (UserNotFoundException $e) {
            $exists = false;
        }
        $i++;
    } while ($exists);
    return $login;
}

function generateRoles(): array
{
    $roles = [
        'developer',
        'developer',
        'developer',
        'developer',
        'developer',
        'developer',
        'developer',
        'developer',
        'accountant',
        'accountant',
        'accountant',
        'manager',
        'manager',
        'admin',
    ];
    $result = [];
    for ($i = 1; $i <= 3; $i++) {
        $role = $roles[array_rand($roles)];
        if ($role === 'admin') {
            return ['admin'];
        }
        $result[$role] = $role;
    }
    return array_values($result);
}
