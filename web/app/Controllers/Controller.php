<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Core\Auth;
use App\Exceptions\AuthException;
use Store\Core\StoreAuth;
use Store\Core\View;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $name, array $data = []): never
    {
        View::render($name, $data);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    protected function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    protected function assertCsrf(): void
    {
        $token = (string) (\App\Core\Request::input('_token') ?? '');

        if (!StoreAuth::checkCsrf($token)) {
            throw new AuthException('Сесията изтече. Презаредете страницата и опитайте отново.', 419);
        }
    }

    protected function requireUser(): \App\Models\User
    {
        $user = Auth::user();

        if ($user === null) {
            $this->redirect('/login');
        }

        return $user;
    }

    /** @param array<string, mixed> $errors */
    protected function fieldError(array $errors, string $key): ?string
    {
        $value = $errors[$key] ?? null;

        if (is_array($value)) {
            $first = $value[0] ?? null;

            return is_string($first) ? $first : null;
        }

        return is_string($value) ? $value : null;
    }
}
