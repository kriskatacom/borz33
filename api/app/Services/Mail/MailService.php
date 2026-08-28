<?php

declare(strict_types=1);

namespace App\Services\Mail;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService implements MailerInterface
{
    private Mailer $mailer;

    private string $fromAddress;

    private string $fromName;

    public function __construct(?string $dsn = null, ?string $fromAddress = null, ?string $fromName = null)
    {
        $config = require dirname(__DIR__, 4) . '/config/mail.php';

        $this->fromAddress = $fromAddress ?? $config['from_address'];
        $this->fromName = $fromName ?? $config['from_name'];
        $this->mailer = new Mailer(Transport::fromDsn($dsn ?? $config['dsn']));
    }

    public function send(string $to, string $subject, string $html, ?string $text = null): void
    {
        $email = (new Email())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->html($html);

        if ($text !== null && $text !== '') {
            $email->text($text);
        }

        $this->mailer->send($email);
    }
}
