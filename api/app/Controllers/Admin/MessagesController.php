<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\ContactMessageResource;
use App\Services\Messages\ContactMessageService;
use App\Services\Messages\ContactReplyNotificationService;
use App\Models\ContactMessageReply;
use App\Core\Auth;
use App\Exceptions\ValidationException;

class MessagesController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $messages = new ContactMessageService(),
        private readonly ContactReplyNotificationService $notifications = new ContactReplyNotificationService()
    ) {}

    public function index(): never { $this->ok($this->messages->paginate(Request::query()), 'Списък със съобщения.'); }

    public function show(string $id): never
    {
        $message = $this->messages->mark($this->messages->find($this->id($id)), true)->load(['replies.admin', 'replies.sender']);
        $this->ok(['message' => ContactMessageResource::toDetailArray($message)]);
    }

    public function reply(string $id): never
    {
        $message = $this->messages->find($this->id($id));
        $body = trim((string) Request::input('body', ''));
        if (mb_strlen($body) < 2 || mb_strlen($body) > 10000) throw new ValidationException(['body' => ['Отговорът трябва да бъде между 2 и 10 000 знака.']]);

        $reply = ContactMessageReply::query()->create([
            'contact_message_id' => $message->id,
            'admin_user_id' => Auth::user()?->id,
            'sender_type' => 'admin',
            'body' => $body,
            'email_sent' => false,
        ]);
        $sent = $this->notifications->send($message, $reply);
        $reply->email_sent = $sent;
        $reply->save();
        $this->messages->mark($message, true);
        $message->load(['replies.admin', 'replies.sender']);

        $this->ok(['message' => ContactMessageResource::toDetailArray($message), 'email_sent' => $sent], $sent ? 'Отговорът е изпратен.' : 'Отговорът е записан, но имейлът не можа да бъде изпратен.');
    }

    public function update(string $id): never
    {
        $read = filter_var(Request::input('read', true), FILTER_VALIDATE_BOOLEAN);
        $message = $this->messages->mark($this->messages->find($this->id($id)), $read)->load(['replies.admin', 'replies.sender']);
        $this->ok(['message' => ContactMessageResource::toDetailArray($message)], $read ? 'Съобщението е отбелязано като прочетено.' : 'Съобщението е отбелязано като непрочетено.');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) $this->error('Съобщението не е намерено.', 404);
        return (int) $id;
    }
}
