<?php

declare(strict_types=1);

namespace App\Services\Mail;

interface MailerInterface
{
    public function send(string $to, string $subject, string $html, ?string $text = null): void;

    /** @param array<string, mixed> $data */
    public function sendTemplate(string $to, string $subject, string $template, array $data, ?string $text = null): void;
}
