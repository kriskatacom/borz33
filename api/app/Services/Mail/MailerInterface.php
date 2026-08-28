<?php

declare(strict_types=1);

namespace App\Services\Mail;

interface MailerInterface
{
    public function send(string $to, string $subject, string $html, ?string $text = null): void;
}
