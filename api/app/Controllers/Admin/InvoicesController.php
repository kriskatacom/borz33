<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\InvoiceResource;
use App\Services\Invoices\InvoiceService;
use App\Services\Invoices\InvoiceNotificationService;

final class InvoicesController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices = new InvoiceService(), private readonly InvoiceNotificationService $notifications = new InvoiceNotificationService()) {}

    public function index(): never
    {
        $result = $this->invoices->paginate(Request::query());
        $result['invoices'] = $result['invoices']->map(fn ($invoice) => InvoiceResource::toArray($invoice))->all();
        $this->ok($result);
    }

    public function show(string $id): never
    {
        $this->ok(['invoice' => InvoiceResource::toArray($this->invoices->find($this->id($id)))]);
    }

    public function issue(string $id): never
    {
        $invoice = $this->invoices->issue($this->invoices->find($this->id($id)));
        $sent = $this->notifications->send($invoice);
        $this->ok(['invoice' => InvoiceResource::toArray($invoice), 'email_sent' => $sent], $sent ? 'Фактурата е издадена и изпратена на клиента.' : 'Фактурата е издадена, но имейлът не можа да бъде изпратен.');
    }

    public function credit(string $id): never
    {
        $input = Request::input();
        $amount = isset($input['amount']) && $input['amount'] !== '' ? (float) $input['amount'] : null;
        $credit = $this->invoices->credit($this->invoices->find($this->id($id)), (string) ($input['reason'] ?? ''), $amount);
        $sent = $this->notifications->send($credit);
        $this->created(['invoice' => InvoiceResource::toArray($credit), 'email_sent' => $sent], $sent ? 'Кредитното известие е издадено и изпратено на клиента.' : 'Кредитното известие е издадено, но имейлът не можа да бъде изпратен.');
    }

    public function cancel(string $id): never
    {
        $invoice = $this->invoices->cancel($this->invoices->find($this->id($id)), (string) (Request::input('reason') ?? ''));
        $this->ok(['invoice' => InvoiceResource::toArray($invoice)], 'Документът е анулиран.');
    }

    public function download(string $id): never
    {
        $invoice = $this->invoices->find($this->id($id));
        $root = dirname(__DIR__, 4);
        $path = $invoice->pdf_path ? $root . '/' . ltrim($invoice->pdf_path, '/') : '';
        if ($path === '' || !is_file($path)) $this->error('PDF файлът не е намерен.', 404);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . ($invoice->type === 'credit_note' ? 'credit-note-' : 'invoice-') . $invoice->number . '.pdf"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function export(): never
    {
        $filters = Request::query();
        $filters['per_page'] = 10000;
        $rows = $this->invoices->paginate($filters)['invoices'];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="invoices-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['Тип', 'Номер', 'Дата', 'Поръчка', 'Клиент', 'ЕИК', 'Данъчна основа', 'ДДС', 'Общо', 'Валута', 'Статус'], ';');
        foreach ($rows as $row) {
            fputcsv($out, [$row->type, $row->number, $row->issue_date?->format('Y-m-d'), $row->order?->number, $row->buyer_snapshot['company'] ?? '', $row->buyer_snapshot['eik'] ?? '', $row->subtotal_net + $row->shipping_net - $row->discount_net, $row->tax_amount, $row->total_gross, $row->currency, $row->status], ';');
        }
        fclose($out);
        exit;
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) $this->error('Фактурата не е намерена.', 404);
        return (int) $id;
    }
}
