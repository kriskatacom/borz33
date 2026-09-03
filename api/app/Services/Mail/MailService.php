<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Services\Company\CompanyProfile;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService implements MailerInterface
{
    private Mailer $mailer;

    private string $fromAddress;

    private string $fromName;

    public function __construct(
        ?string $dsn = null,
        ?string $fromAddress = null,
        ?string $fromName = null,
        private readonly EmailRenderer $renderer = new EmailRenderer()
    ) {
        $config = require dirname(__DIR__, 4) . '/config/mail.php';

        $company = CompanyProfile::get();
        $companyEmail = trim((string) ($company['email'] ?? ''));
        $this->fromAddress = $fromAddress ?? (filter_var($companyEmail, FILTER_VALIDATE_EMAIL) ? $companyEmail : $config['from_address']);
        $this->fromName = $fromName ?? (trim((string) ($company['name'] ?? '')) ?: $config['from_name']);
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

    public function sendTemplate(string $to, string $subject, string $template, array $data, ?string $text = null): void
    {
        $this->send($to, $subject, $this->renderer->render($template, $data), $text);
    }

    public function sendTemplateWithAttachments(string $to, string $subject, string $template, array $data, array $attachments, ?string $text = null): void
    {
        $email = (new Email())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->html($this->renderer->render($template, $data));

        if ($text !== null && $text !== '') $email->text($text);
        foreach ($attachments as $attachment) {
            if (!is_file($attachment['path'])) throw new \RuntimeException('Липсва файлът за прикачване: ' . $attachment['name']);
            $email->attachFromPath($attachment['path'], $attachment['name'], $attachment['content_type'] ?? null);
        }
        $this->mailer->send($email);
    }
}
