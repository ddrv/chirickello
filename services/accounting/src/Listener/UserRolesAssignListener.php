<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Package\Event\UserRolesAssigned\UserRolesAssigned;
use Chirickello\Accounting\Exception\UserNotFoundException;
use Chirickello\Accounting\Repo\UserRepo\UserRepo;

class UserRolesAssignListener
{
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * @param object $event
     * @return void
     * @throws UserNotFoundException
     */
    public function __invoke(object $event): void
    {
        if (!$event instanceof UserRolesAssigned) {
            return;
        }

        $user = $this->userRepo->getById($event->getUserId());
        $user->setRoles($event->getRoles());
        $this->userRepo->save($user);
    }
}
