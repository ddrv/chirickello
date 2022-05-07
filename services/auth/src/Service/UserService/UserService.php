<?php

declare(strict_types=1);

namespace Chirickello\Auth\Service\UserService;

use Chirickello\Auth\Entity\User;
use Chirickello\Auth\Exception\EmailExistsException;
use Chirickello\Auth\Exception\LoginExistsException;
use Chirickello\Auth\Exception\StorageException;
use Chirickello\Auth\Exception\UserNotFoundException;
use Chirickello\Auth\Repo\UserRepo\UserRepo;
use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Package\Event\UserRolesAssigned\UserRolesAssigned;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserService
{
    private UserRepo $userRepo;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(UserRepo $userRepo, EventDispatcherInterface $eventDispatcher)
    {
        $this->userRepo = $userRepo;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $id
     * @return User
     * @throws UserNotFoundException
     */
    public function getById(string $id): User
    {
        return $this->userRepo->getById($id);
    }

    /**
     * @return User[]
     */
    public function getAll(): iterable
    {
        return $this->userRepo->getAll();
    }

    /**
     * @param string $login
     * @return User
     * @throws UserNotFoundException
     */
    public function getByLogin(string $login): User
    {
        return $this->userRepo->getByLogin($login);
    }

    /**
     * @param User $user
     * @return void
     * @throws EmailExistsException
     * @throws LoginExistsException
     * @throws StorageException
     */
    public function save(User $user): void
    {
        if ($user->isNew()) {
            $this->create($user);
        } else {
            $this->update($user);
        }
    }

    /**
     * @param User $user
     * @return void
     * @throws EmailExistsException
     * @throws LoginExistsException
     * @throws StorageException
     */
    private function create(User $user): void
    {
        $this->userRepo->save($user);
//        $userCreatedEvent = new UserAdded(
//            $user->getId(),
//            $user->getLogin(),
//            $user->getEmail()
//        );
//        $this->eventDispatcher->dispatch($userCreatedEvent);
//        $this->handleUpdatedRoles($user->getId(), $user->getRoles());
    }

    /**
     * @param User $user
     * @return void
     * @throws EmailExistsException
     * @throws LoginExistsException
     * @throws StorageException
     */
    private function update(User $user): void
    {
        $id = $user->getId();
        $newRoles = $user->isRolesChanged() ? $user->getRoles() : null;

        $this->userRepo->save($user);

//        if (!is_null($newRoles)) {
//            $this->handleUpdatedRoles($id, $newRoles);
//        }
    }

    private function handleUpdatedRoles(string $id, array $roles): void
    {
        $userRolesUpdatedEvent = new UserRolesAssigned($id, $roles);
        $this->eventDispatcher->dispatch($userRolesUpdatedEvent);
    }
}
