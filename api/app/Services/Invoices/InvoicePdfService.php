<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\SiteSetting;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

final class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        if ($invoice->number === null) throw new RuntimeException('Не може да се генерира PDF за чернова без номер.');

        $root = dirname(__DIR__, 4);
        $template = $root . '/resources/invoices/document.php';
        $invoice->loadMissing(['order', 'parentInvoice']);
        $logo = SiteSetting::query()->with('logo')->first()?->logo;
        $logoPath = null;
        if ($logo?->isImage()) {
            $candidate = $root . '/api/public/' . ltrim((string) $logo->path, '/');
            if (is_file($candidate) && in_array($logo->mime, ['image/jpeg', 'image/png', 'image/gif'], true)) $logoPath = $candidate;
        }
        ob_start();
        require $template;
        $html = (string) ob_get_clean();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->setChroot($root . '/api/public');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $directory = $root . '/storage/invoices/' . $invoice->issue_date->format('Y/m');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('Папката за фактури не може да бъде създадена.');
        $path = $directory . '/' . $invoice->number . '.pdf';
        if (file_put_contents($path, $dompdf->output(), LOCK_EX) === false) throw new RuntimeException('PDF фактурата не може да бъде записана.');
        return ltrim(str_replace($root, '', $path), '/');
    }
}
