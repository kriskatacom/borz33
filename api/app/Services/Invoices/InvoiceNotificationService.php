<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;

final class InvoiceNotificationService
{
    public function __construct(private readonly MailerInterface $mailer = new MailService()) {}

    public function send(Invoice $invoice): bool
    {
        $invoice->loadMissing('order');
        if ($invoice->type !== 'credit_note' && !$this->isRequested($invoice)) return false;
        $email = trim((string) ($invoice->buyer_snapshot['email'] ?? $invoice->order?->email ?? ''));
        $root = dirname(__DIR__, 4);
        $path = $invoice->pdf_path ? $root . '/' . ltrim($invoice->pdf_path, '/') : '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $invoice->number === null || !is_file($path)) return false;

        $credit = $invoice->type === 'credit_note';
        $document = $credit ? 'кредитното известие' : 'фактурата';
        $filename = ($credit ? 'credit-note-' : 'invoice-') . $invoice->number . '.pdf';

        try {
            $this->mailer->sendTemplateWithAttachments(
                $email,
                ucfirst($document) . ' Ви · ' . $invoice->number,
                'invoice-document',
                ['invoice' => $invoice, 'title' => ucfirst($document) . ' Ви', 'preheader' => 'Прикачваме ' . $document . ' № ' . $invoice->number . '.'],
                [['path' => $path, 'name' => $filename, 'content_type' => 'application/pdf']],
                'Здравейте, прикачваме ' . $document . ' № ' . $invoice->number . ' към поръчка ' . ($invoice->order?->number ?? '') . '.'
            );
            return true;
        } catch (\Throwable $exception) {
            error_log(sprintf('Invoice email failed [invoice=%s]: %s', $invoice->number, $exception->getMessage()));
            return false;
        }
    }

    public function isRequested(Invoice $invoice): bool
    {
        $invoice->loadMissing('order');
        return (bool) ($invoice->order?->invoice_requested ?? false);
    }
}
