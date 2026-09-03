<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/eloquent.php';
require dirname(__DIR__) . '/api/app/Core/Autoloader.php';

use Illuminate\Database\Capsule\Manager as DB;

$invoiceClass = 'App\\Models\\Invoice';
$exportClass = 'App\\Services\\Accounting\\AccountingExportService';
$root = dirname(__DIR__);
$generatedFiles = [];
$generatedArchives = [];

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    DB::connection()->transaction(function () use ($invoiceClass, $exportClass, $root, &$generatedFiles, &$generatedArchives, $assert): void {
        $template = $invoiceClass::query()->where('type', 'invoice')->first();
        $assert($template !== null, 'Необходима е поне една фактура-шаблон за теста.');

        $base = $invoiceClass::query()->create([
            'order_id' => $template->order_id,
            'type' => 'invoice',
            'number' => 'TEST-ACCOUNTING-INVOICE-' . bin2hex(random_bytes(4)),
            'status' => 'issued',
            'issue_date' => '2026-08-15',
            'tax_event_date' => '2026-08-15',
            'currency' => 'EUR',
            'seller_snapshot' => $template->seller_snapshot,
            'buyer_snapshot' => $template->buyer_snapshot,
            'payment_snapshot' => $template->payment_snapshot,
            'items_snapshot' => $template->items_snapshot,
            'subtotal_net' => -20.00,
            'discount_net' => 0,
            'shipping_net' => 0,
            'tax_amount' => 0,
            'total_gross' => -20.00,
        ]);

        $invoiceClass::query()->create([
            'order_id' => $template->order_id,
            'parent_invoice_id' => $base->id,
            'type' => 'credit_note',
            'number' => 'TEST-ACCOUNTING-CREDIT-' . bin2hex(random_bytes(4)),
            'status' => 'issued',
            'issue_date' => '2026-08-20',
            'tax_event_date' => '2026-08-20',
            'currency' => 'EUR',
            'seller_snapshot' => $template->seller_snapshot,
            'buyer_snapshot' => $template->buyer_snapshot,
            'payment_snapshot' => $template->payment_snapshot,
            'items_snapshot' => [],
            'subtotal_net' => -5.00,
            'discount_net' => 0,
            'shipping_net' => 0,
            'tax_amount' => 0,
            'total_gross' => -5.00,
        ]);

        $exports = new $exportClass();
        $mixed = $exports->package('2026-08-01', '2026-08-31', 'test-mixed');
        $empty = $exports->package('2099-01-01', '2099-01-31', 'test-empty');
        $generatedArchives = [$mixed, $empty];

        $mixedEntries = [];
        $emptyEntries = [];
        foreach (['mixed' => $mixed, 'empty' => $empty] as $kind => $relativePath) {
            $path = $root . '/' . ltrim($relativePath, '/');
            $assert(is_file($path), "Липсва генерираният {$kind} ZIP архив.");
            $zip = new ZipArchive();
            $assert($zip->open($path) === true, "{$kind} ZIP архивът е невалиден.");
            $entries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entries[] = $zip->getNameIndex($index);
            }
            $zip->close();
            if ($kind === 'mixed') {
                $mixedEntries = $entries;
            } else {
                $emptyEntries = $entries;
            }
        }

        $mixedPdfs = array_values(array_filter($mixedEntries, static fn (string $entry): bool => str_ends_with($entry, '.pdf')));
        $emptyPdfs = array_values(array_filter($emptyEntries, static fn (string $entry): bool => str_ends_with($entry, '.pdf')));
        $assert(count($mixedPdfs) === 2, 'Смесеният пакет трябва да съдържа точно 2 PDF файла.');
        $assert(count($emptyPdfs) === 0, 'Празният пакет не трябва да съдържа PDF файлове.');
        $assert(count(array_filter($mixedPdfs, static fn (string $entry): bool => str_starts_with($entry, 'invoices/'))) === 1, 'Липсва PDF фактура в смесения пакет.');
        $assert(count(array_filter($mixedPdfs, static fn (string $entry): bool => str_starts_with($entry, 'credit-notes/'))) === 1, 'Липсва PDF кредитно известие в смесения пакет.');

        foreach ($invoiceClass::query()->where('number', 'like', 'TEST-ACCOUNTING-%')->get() as $document) {
            if ($document->pdf_path !== null) {
                $generatedFiles[] = $root . '/' . ltrim((string) $document->pdf_path, '/');
            }
        }

        foreach ($generatedArchives as $relativePath) {
            @unlink($root . '/' . ltrim($relativePath, '/'));
        }
        foreach ($generatedFiles as $path) {
            @unlink($path);
        }

        throw new RuntimeException('ACCOUNTING_EXPORT_TEST_RESULT:' . json_encode([
            'mixed_pdf_count' => count($mixedPdfs),
            'empty_pdf_count' => count($emptyPdfs),
            'mixed_invoice_pdf_count' => count(array_filter($mixedPdfs, static fn (string $entry): bool => str_starts_with($entry, 'invoices/'))),
            'mixed_credit_note_pdf_count' => count(array_filter($mixedPdfs, static fn (string $entry): bool => str_starts_with($entry, 'credit-notes/'))),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    });
} catch (Throwable $exception) {
    if (str_starts_with($exception->getMessage(), 'ACCOUNTING_EXPORT_TEST_RESULT:')) {
        echo substr($exception->getMessage(), strlen('ACCOUNTING_EXPORT_TEST_RESULT:')) . PHP_EOL;
        exit(0);
    }

    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
