<?php

declare(strict_types=1);

namespace App\Services\Mail;

class EmailRenderer
{
    public function render(string $template, array $data = []): string
    {
        $data['company'] = require dirname(__DIR__, 4) . '/config/company.php';
        $data['content'] = $this->renderFile($template, $data);

        return $this->renderFile('layout', $data);
    }

    /** @param array<string, mixed> $data */
    private function renderFile(string $name, array $data): string
    {
        $file = dirname(__DIR__, 4) . '/resources/emails/' . $name . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException('Липсва имейл шаблон: ' . $name);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
