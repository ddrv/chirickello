<?php

declare(strict_types=1);

namespace Chirickello\Sender\Listener;

use Chirickello\Package\Event\UserAdded\UserAdded;
use Chirickello\Sender\Entity\User;
use Chirickello\Sender\Exception\UserNotFoundException;
use Chirickello\Sender\Repo\UserRepo\UserRepo;

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
            $user = new User($event->getUserId(), $event->getLogin(), $event->getEmail());
        }
        $user->setLogin($event->getLogin());
        $this->userRepo->save($user);
    }
}
