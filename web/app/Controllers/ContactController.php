<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Models\ContactMessage;
use App\Services\Messages\ContactNotificationService;

class ContactController extends Controller
{
    public function __construct(private readonly ContactNotificationService $notifications = new ContactNotificationService()) {}

    public function show(): never
    {
        $user = Auth::user();
        $this->renderForm([
            'name' => $user ? trim((string) $user->first_name . ' ' . (string) $user->last_name) : '',
            'email' => (string) ($user?->email ?? ''),
            'phone' => (string) ($user?->phone ?? ''),
            'subject' => '',
            'message' => '',
        ]);
    }

    public function store(): never
    {
        $this->assertCsrf();
        $form = [];
        foreach (['name', 'email', 'phone', 'subject', 'message'] as $key) $form[$key] = trim((string) Request::input($key, ''));
        if (trim((string) Request::input('website', '')) !== '') $this->redirect('/contact?sent=1');

        $last = (int) ($_SESSION['contact_last_sent_at'] ?? 0);
        $errors = [];
        if ($last > 0 && time() - $last < 30) $errors['form'] = 'Изчакайте малко преди да изпратите ново съобщение.';
        if ($form['name'] === '' || mb_strlen($form['name']) > 160) $errors['name'] = 'Въведете име до 160 знака.';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 191) $errors['email'] = 'Въведете валиден имейл адрес.';
        if (mb_strlen($form['phone']) > 40) $errors['phone'] = 'Телефонът е прекалено дълъг.';
        if ($form['subject'] === '' || mb_strlen($form['subject']) > 191) $errors['subject'] = 'Въведете тема до 191 знака.';
        if (mb_strlen($form['message']) < 10 || mb_strlen($form['message']) > 5000) $errors['message'] = 'Съобщението трябва да бъде между 10 и 5000 знака.';
        if ($errors !== []) $this->renderForm($form, $errors, 'Проверете отбелязаните полета.', 422);

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $record = ContactMessage::query()->create([
            ...$form,
            'phone' => $form['phone'] !== '' ? $form['phone'] : null,
            'user_id' => Auth::user()?->id,
            'ip_hash' => $ip !== '' ? hash('sha256', $ip . '|' . session_id()) : null,
            'email_sent' => false,
        ]);
        $sent = $this->notifications->send($record);
        $record->email_sent = $sent['admin'];
        $record->save();
        $_SESSION['contact_last_sent_at'] = time();
        $this->redirect('/contact?sent=1');
    }

    /** @param array<string, string> $form @param array<string, string> $errors */
    private function renderForm(array $form, array $errors = [], ?string $message = null, int $status = 200): never
    {
        $this->view('contact', ['title' => 'Контакти', 'metaDescription' => 'Свържете се с екипа на Borz33.', 'canonicalPath' => '/contact', 'form' => $form, 'errors' => $errors, 'message' => $message, 'sent' => Request::query('sent') === '1', 'status' => $status]);
    }
}
