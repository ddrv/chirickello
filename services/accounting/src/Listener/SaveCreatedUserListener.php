<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Listener;

use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Accounting\Entity\User;
use Chirickello\Accounting\Exception\UserNotFoundException;
use Chirickello\Accounting\Repo\UserRepo\UserRepo;

class SaveCreatedUserListener
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
            $user = new User($event->getUserId(), $event->getLogin());
        }
        $user->setLogin($event->getLogin());
        $this->userRepo->save($user);
    }
}
