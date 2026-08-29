<?php

declare(strict_types=1);

namespace Store\Core;

use App\Models\Category;
use Illuminate\Support\Collection;

class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): never
    {
        http_response_code((int) ($data['status'] ?? 200));
        header('Content-Type: text/html; charset=utf-8');

        $data['currentPath'] = \App\Core\Request::path();
        $data['currentUser'] = \App\Core\Auth::user();
        $data['csrf'] = StoreAuth::csrf();
        $data['navCategories'] = self::navCategories();
        $data['content'] = self::capture($view, $data);
        echo self::capture('layout', $data);
        exit;
    }

    /** @param array<string, mixed> $data */
    public static function renderError(string $message, int $status, mixed $errors = null): never
    {
        if ($status === 404) {
            $message = 'Страницата не е намерена.';
        }

        self::render('errors/generic', [
            'status' => $status,
            'title' => $status === 404 ? 'Страницата не е намерена' : 'Грешка',
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    /** @return Collection<int, Category> */
    private static function navCategories(): Collection
    {
        try {
            return Category::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->orderBy('id')
                ->with(['children' => static function ($query): void {
                    $query->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->orderBy('id');
                }])
                ->get();
        } catch (\Throwable) {
            return new Collection();
        }
    }

    /** @param array<string, mixed> $data */
    private static function capture(string $name, array $data): string
    {
        $file = dirname(__DIR__, 2) . '/views/' . $name . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException('Липсва изглед: ' . $name);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
