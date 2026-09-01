<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Notifications\AdminNotificationService;

final class NotificationsController extends Controller
{
    public function __construct(private readonly AdminNotificationService $notifications = new AdminNotificationService()) {}
    public function index(): never { $this->ok($this->notifications->list((int) Request::query('page', 1), (int) Request::query('per_page', 20), Request::wantsTrue('archived'))); }
    public function show(string $id): never
    {
        $notification = $this->notifications->find((int) $id);
        if ($notification->read_at === null) {
            $notification = $this->notifications->markRead((int) $notification->id, true);
        }
        $this->ok(['notification' => $this->notifications->toArray($notification)]);
    }
    public function markRead(string $id): never
    {
        $this->ok(['notification' => $this->notifications->toArray($this->notifications->markRead((int) $id, true))]);
    }
    public function update(string $id): never { $this->ok(['notification' => $this->notifications->toArray($this->notifications->markRead((int) $id, Request::wantsTrue('read')))]); }
    public function readAll(): never { $this->notifications->markAllRead(); $this->ok([], 'Всички известия са прочетени.'); }
    public function archive(string $id): never { $this->notifications->archive((int) $id); $this->ok([], 'Известието е архивирано.'); }
    public function delete(string $id): never { $this->notifications->delete((int) $id); $this->ok([], 'Известието е изтрито.'); }
    public function archiveAll(): never { $this->notifications->archiveAll(); $this->ok([], 'Всички известия са архивирани.'); }
    public function deleteAll(): never { $this->notifications->deleteAll(Request::wantsTrue('archived')); $this->ok([], 'Всички известия са изтрити.'); }
}
