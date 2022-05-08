<?php

declare(strict_types=1);

namespace Chirickello\TaskTracker\Listener;

use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\TaskTracker\Entity\User;
use Chirickello\TaskTracker\Exception\UserNotFoundException;
use Chirickello\TaskTracker\Repo\UserRepo\UserRepo;

class UserAddListener
{
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * @param object $event
     * @return void
     */
    public function __invoke(object $event): void
    {
        if (!$event instanceof UserAdded) {
            return;
        }

        try {
            $user = $this->userRepo->getById($event->getUserId());
        } catch (UserNotFoundException $e) {
            $user = new User($event->getUserId(), $event->getLogin(), null);
        }
        $user->setLogin($event->getLogin());
        $this->userRepo->save($user);
    }
}
