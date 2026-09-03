<?php

declare(strict_types=1);

namespace App\Services\Messages;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;
use App\Services\Company\CompanyProfile;

class ContactReplyNotificationService
{
    private string $adminEmail;
    private string $adminUrl;
    private string $websiteUrl;

    public function __construct(private readonly MailerInterface $mailer = new MailService())
    {
        $mail = require dirname(__DIR__, 4) . '/config/mail.php';
        $company = CompanyProfile::get();
        $companyEmail = trim((string) ($company['email'] ?? ''));
        $this->adminEmail = filter_var($companyEmail, FILTER_VALIDATE_EMAIL) ? $companyEmail : trim((string) ($mail['contact_admin_address'] ?? ''));
        $this->adminUrl = rtrim((string) ($mail['admin_url'] ?? ''), '/');
        $this->websiteUrl = rtrim((string) ($company['website'] ?? ''), '/');
    }

    public function send(ContactMessage $message, ContactMessageReply $reply): bool
    {
        try {
            $subject = 'Re: ' . str_replace(["\r", "\n"], ' ', (string) $message->subject);
            $this->mailer->sendTemplate(
                (string) $message->email,
                $subject,
                'contact-reply',
                ['contactMessage' => $message, 'reply' => $reply, 'title' => $subject, 'preheader' => (string) $reply->body, 'customerConversationUrl' => $message->user_id ? $this->websiteUrl . '/account/messages?conversation=' . $message->id . '#conversation' : $this->websiteUrl . '/login', 'hasConversation' => $message->user_id !== null, 'attachmentLinks' => $this->attachmentLinks($reply)],
                implode("\n", ['Здравейте, ' . $message->name . ',', '', $reply->body, '', 'Относно: ' . $message->subject])
            );
            return true;
        } catch (\Throwable $exception) {
            error_log('Contact reply email failed [reply=' . $reply->id . ']: ' . $exception->getMessage());
            return false;
        }
    }

    public function sendToAdmin(ContactMessage $message, ContactMessageReply $reply): bool
    {
        if ($this->adminEmail === '') return false;
        try {
            $subject = 'Нов отговор · ' . str_replace(["\r", "\n"], ' ', (string) $message->subject);
            $url = $this->adminUrl . '/messages/' . $message->id;
            $this->mailer->sendTemplate($this->adminEmail, $subject, 'contact-customer-reply', [
                'contactMessage' => $message, 'reply' => $reply, 'title' => $subject, 'preheader' => (string) $reply->body, 'adminConversationUrl' => $url,
            ], implode("\n", [$message->name . ' отговори на запитването „' . $message->subject . '“:', '', $reply->body, '', 'Разговор: ' . $url]));
            return true;
        } catch (\Throwable $exception) {
            error_log('Customer reply email failed [reply=' . $reply->id . ']: ' . $exception->getMessage());
            return false;
        }
    }

    private function attachmentLinks(ContactMessageReply $reply): array
    {
        $links = [];
        foreach ($reply->attachments()->with('file')->get() as $attachment) if ($attachment->file !== null) $links[] = ['name' => $attachment->file->original_name, 'url' => $this->websiteUrl . '/' . ltrim($attachment->file->path, '/')];
        return $links;
    }
}
