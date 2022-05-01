<?php

declare(strict_types=1);

namespace Chirickello\Sender\Listener;

use Chirickello\Package\Event\UserEmailUpdated;
use Chirickello\Sender\Entity\User;
use Chirickello\Sender\Exception\UserNotFoundException;
use Chirickello\Sender\Repo\UserRepo\UserRepo;

class SetUserEmailListener
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
        if (!$event instanceof UserEmailUpdated) {
            return;
        }

        try {
            $user = $this->userRepo->getById($event->getUserId());
        } catch (UserNotFoundException $e) {
            $user = new User($event->getUserId());
        }
        $user->setEmail($event->getEmail());
        $this->userRepo->save($user);
    }
}
