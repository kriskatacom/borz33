<?php

declare(strict_types=1);

namespace App\Services\Messages;

use App\Models\ContactMessage;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;

class ContactNotificationService
{
    private string $adminEmail;
    private string $adminUrl;
    private string $websiteUrl;

    public function __construct(private readonly MailerInterface $mailer = new MailService())
    {
        $config = require dirname(__DIR__, 4) . '/config/mail.php';
        $company = require dirname(__DIR__, 4) . '/config/company.php';
        $this->adminEmail = trim((string) ($config['contact_admin_address'] ?? ''));
        $this->adminUrl = rtrim((string) ($config['admin_url'] ?? ''), '/');
        $this->websiteUrl = rtrim((string) ($company['website'] ?? ''), '/');
    }

    /** @return array{admin: bool, sender: bool} */
    public function send(ContactMessage $message): array
    {
        $result = ['admin' => false, 'sender' => false];

        if ($this->adminEmail !== '') {
            try {
                $this->mailer->sendTemplate($this->adminEmail, 'Контактна форма · ' . str_replace(["\r", "\n"], ' ', (string) $message->subject), 'contact-admin', [
                    'contactMessage' => $message, 'title' => 'Ново съобщение', 'preheader' => $message->subject,
                    'adminConversationUrl' => $this->adminUrl . '/messages/' . $message->id,
                ], implode("\n", ['Ново съобщение от контактната форма', '', 'От: ' . $message->name, 'Имейл: ' . $message->email, 'Тема: ' . $message->subject, '', $message->message, '', 'Разговор: ' . $this->adminUrl . '/messages/' . $message->id]));
                $result['admin'] = true;
            } catch (\Throwable $exception) {
                error_log('Contact admin email failed [message=' . $message->id . ']: ' . $exception->getMessage());
            }
        }

        try {
            $customerUrl = $message->user_id
                ? $this->websiteUrl . '/account/messages?conversation=' . $message->id . '#conversation'
                : $this->websiteUrl . '/login';
            $this->mailer->sendTemplate((string) $message->email, 'Получихме запитването Ви · ' . str_replace(["\r", "\n"], ' ', (string) $message->subject), 'contact-confirmation', [
                'contactMessage' => $message, 'title' => 'Получихме запитването Ви', 'preheader' => $message->subject,
                'customerConversationUrl' => $customerUrl, 'hasConversation' => $message->user_id !== null,
            ], implode("\n", ['Здравейте, ' . $message->name . ',', '', 'Получихме запитването Ви: ' . $message->subject, '', $message->message, '', 'Разговор: ' . $customerUrl]));
            $result['sender'] = true;
        } catch (\Throwable $exception) {
            error_log('Contact confirmation email failed [message=' . $message->id . ']: ' . $exception->getMessage());
        }

        return $result;
    }
}
