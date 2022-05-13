<?php

declare(strict_types=1);

namespace Chirickello\Sender\Listener;

use Chirickello\Package\Event\SalaryPaid\SalaryPaid;
use Chirickello\Sender\Exception\UserNotFoundException;
use Chirickello\Sender\Repo\UserRepo\UserRepo;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SendDailySalaryReportListener
{
    private UserRepo $userRepo;
    private Environment $twig;
    private Mailer $mailer;

    public function __construct(UserRepo $userRepo, Environment $twig, Mailer $mailer)
    {
        $this->userRepo = $userRepo;
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    /**
     * @param object $event
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     */
    public function __invoke(object $event): void
    {
        if (!$event instanceof SalaryPaid) {
            return;
        }

        try {
            $user = $this->userRepo->getById($event->getUserId());
        } catch (UserNotFoundException $e) {
            return;
        }

        $email = $user->getEmail();
        if (is_null($email)) {
            return;
        }
        $login = $user->getLogin();
        $date = $event->getPaymentTime()->format('m/d/Y');

        $amount = (string)$event->getAmount();

        if (strlen($amount) <= 2) {
            $dollars = '0';
            $cents = str_pad($amount, 2, '0', STR_PAD_LEFT);
        } else {
            $dollars = substr($amount, 0, -2);
            $cents = substr($amount, -2);
        }
        $context = [
            'login' => $login ?? 'our friend',
            'date' => $date,
            'amount' => sprintf('$%d.%d', $dollars, $cents),
        ];
        $text = $this->twig->render('mail/payment_daily_report.text.twig', $context);
        $html = $this->twig->render('mail/payment_daily_report.html.twig', $context);

        $recipient = new Address($email, $login);

        $subject = sprintf('salary for %s paid', $date);
        $message = (new Email())
            ->subject($subject)
            ->html($html)
            ->text($text)
            ->to($recipient)
        ;
        $this->mailer->send($message);
    }
}
