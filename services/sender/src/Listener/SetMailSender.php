<?php

declare(strict_types=1);

namespace Chirickello\Sender\Listener;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SetMailSender
{
    private Address $sender;

    public function __construct(string $email, string $name)
    {
        $this->sender = new Address($email, $name);
    }

    public function __invoke(object $event)
    {
        if (!$event instanceof MessageEvent) {
            return;
        }

        $email = $event->getMessage();
        if (!$email instanceof Email) {
            return;
        }
        $email->from($this->sender);
    }
}
