<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/eloquent.php';
require dirname(__DIR__) . '/api/app/Core/Autoloader.php';

use Illuminate\Database\Capsule\Manager as DB;

$root = dirname(__DIR__);
$invoiceClass = 'App\\Models\\Invoice';
$accountingClass = 'App\\Services\\Accounting\\AccountingService';
$exportClass = 'App\\Services\\Accounting\\AccountingExportService';
$monthlyClass = 'App\\Services\\Reports\\MonthlyRevenueReportService';
$orderClass = 'App\\Models\\Order';
$settingClass = 'App\\Models\\SiteSetting';
$archives = [];
$files = [];
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$expectFailure = static function (callable $callback, string $message) use ($assert): void {
    try { $callback(); } catch (Throwable) { return; }
    $assert(false, $message);
};

try {
    DB::connection()->transaction(function () use ($root, $invoiceClass, $accountingClass, $exportClass, $monthlyClass, $orderClass, $settingClass, &$archives, &$files, $assert, $expectFailure): void {
        $template = $invoiceClass::query()->where('type', 'invoice')->first();
        $assert($template !== null, 'Няма налична фактура-шаблон.');
        $templateOrder = $orderClass::query()->find($template->order_id);
        $assert($templateOrder !== null, 'Няма налична поръчка-шаблон.');
        $accounting = new $accountingClass();
        $exports = new $exportClass();
        $monthly = new $monthlyClass();
        $filters = ['date_from'=>'2026-08-01','date_to'=>'2026-08-31','order_status'=>'all','payment_method'=>'all','invoiced'=>'all','paid'=>'all'];

        // Credit date and re-generation isolation.
        $base = $invoiceClass::query()->create(['order_id'=>$template->order_id,'parent_invoice_id'=>null,'type'=>'invoice','number'=>'TEST-FULL-BASE-'.bin2hex(random_bytes(4)),'status'=>'issued','issue_date'=>'2026-08-15','tax_event_date'=>'2026-08-15','currency'=>'EUR','seller_snapshot'=>$template->seller_snapshot,'buyer_snapshot'=>$template->buyer_snapshot,'payment_snapshot'=>$template->payment_snapshot,'items_snapshot'=>$template->items_snapshot,'subtotal_net'=>-20,'discount_net'=>0,'shipping_net'=>0,'tax_amount'=>0,'total_gross'=>-20]);
        $credit = $invoiceClass::query()->create(['order_id'=>$template->order_id,'parent_invoice_id'=>$base->id,'type'=>'credit_note','number'=>'TEST-FULL-AUG-'.bin2hex(random_bytes(4)),'status'=>'issued','issue_date'=>'2026-08-20','tax_event_date'=>'2026-08-20','currency'=>'EUR','seller_snapshot'=>$template->seller_snapshot,'buyer_snapshot'=>$template->buyer_snapshot,'payment_snapshot'=>$template->payment_snapshot,'items_snapshot'=>[],'subtotal_net'=>-5,'discount_net'=>0,'shipping_net'=>0,'tax_amount'=>0,'total_gross'=>-5]);
        $later = $credit->replicate(); $later->number='TEST-FULL-SEP-'.bin2hex(random_bytes(4)); $later->issue_date='2026-09-01'; $later->tax_event_date='2026-09-01'; $later->save();
        $dashboard = $accounting->dashboard($filters);
        $assert((int)$dashboard['summary']['credit_notes_count'] === 1, 'Dashboard включва кредит извън периода.');
        $firstMonthly = $monthly->generate('2026-08');
        $secondMonthly = $monthly->generate('2026-08');
        $assert((int)$firstMonthly->credit_notes_count === 1 && (int)$secondMonthly->credit_notes_count === 1, 'Повторното генериране не е стабилно.');

        // Date policy and common amount calculations.
        $sales = $accounting->report('sales', $filters);
        $assert($dashboard['date_basis']['sales'] === 'Дата на поръчката', 'Няма политика за датата на продажбите.');
        $assert($sales['date_basis']['documents'] === 'Дата на издаване', 'Няма политика за датата на документите.');
        $assert(round(array_sum(array_column($sales['rows'],'total')),2) === (float)$dashboard['summary']['turnover'], 'Продажбите и dashboard не съвпадат.');
        $assert(round(array_sum(array_column($sales['rows'],'tax_base')),2) === (float)$dashboard['summary']['tax_base'], 'Данъчните основи не съвпадат.');
        $assert(round(array_sum(array_column($sales['rows'],'vat')),2) === (float)$dashboard['summary']['vat'], 'ДДС стойностите не съвпадат.');

        foreach (['sales','invoices','credit_notes','payments','refunds','card','bank_transfer','cash_on_delivery','deliveries'] as $type) {
            $result = $accounting->report($type, $filters);
            $assert(isset($result['rows']) && isset($result['columns']) && $result['columns'] !== [] && isset($result['date_basis']), "Невалиден резултат за {$type}.");
        }
        $emptyReport = $accounting->report('sales', array_merge($filters, ['date_from'=>'2099-01-01','date_to'=>'2099-01-31']));
        $assert($emptyReport['rows'] === [] && $emptyReport['columns'] === ['date','order','customer','status','payment_method','invoiced','tax_base','vat','total'], 'Празната справка няма предварително зададени колони.');
        $expectFailure(static fn()=>$accounting->dashboard(array_merge($filters,['date_from'=>'2026-09-01','date_to'=>'2026-08-01'])), 'Невалиден период е приет.');
        $expectFailure(static fn()=>$accounting->report('unknown',$filters), 'Невалиден вид справка е приет.');

        // Paid/unpaid consistency.
        $paid = $accounting->dashboard(array_merge($filters,['paid'=>'yes']))['summary'];
        $unpaid = $accounting->dashboard(array_merge($filters,['paid'=>'no']))['summary'];
        $all = $accounting->dashboard($filters)['summary'];
        $assert((int)$paid['paid_orders'] === (int)$paid['orders_count'], 'Филтърът за платени не съвпада.');
        $assert((int)$unpaid['unpaid_orders'] === (int)$unpaid['orders_count'], 'Филтърът за неплатени не съвпада.');
        $assert((int)$all['paid_orders'] + (int)$all['unpaid_orders'] === (int)$all['orders_count'], 'Платени и неплатени не дават общия брой.');

        // Payment/refund guards and the accounting definition of a paid order.
        $testOrder = $templateOrder->replicate();
        $testOrder->number = 'TEST-FULL-PAY-' . bin2hex(random_bytes(4));
        $testOrder->status = 'pending';
        $testOrder->subtotal = 100;
        $testOrder->shipping_amount = 0;
        $testOrder->total = 100;
        $testOrder->created_at = '2026-08-10 10:00:00';
        $testOrder->updated_at = '2026-08-10 10:00:00';
        $testOrder->save();
        $accounting->createTransaction(['order_id'=>$testOrder->id,'type'=>'payment','method'=>'cash_on_delivery','amount'=>40,'occurred_at'=>'2026-08-10 11:00:00']);
        $expectFailure(static fn()=>$accounting->createTransaction(['order_id'=>$testOrder->id,'type'=>'payment','method'=>'cash_on_delivery','amount'=>61,'occurred_at'=>'2026-08-10 12:00:00']),'Надплащане е прието.');
        $accounting->createTransaction(['order_id'=>$testOrder->id,'type'=>'payment','method'=>'card','amount'=>60,'occurred_at'=>'2026-08-10 12:30:00']);
        $accounting->createTransaction(['order_id'=>$testOrder->id,'type'=>'refund','method'=>'card','amount'=>20,'occurred_at'=>'2026-08-10 13:00:00']);
        $creditForTest = $credit->replicate(); $creditForTest->order_id=$testOrder->id; $creditForTest->number='TEST-FULL-PAY-CREDIT-'.bin2hex(random_bytes(4)); $creditForTest->total_gross=-10; $creditForTest->subtotal_net=-10; $creditForTest->save();
        $expectFailure(static fn()=>$accounting->createTransaction(['order_id'=>$testOrder->id,'type'=>'refund','method'=>'card','amount'=>71,'occurred_at'=>'2026-08-10 14:00:00']),'Възстановяване над наличната сума е прието.');
        $accounting->createTransaction(['order_id'=>$testOrder->id,'type'=>'refund','method'=>'card','amount'=>70,'occurred_at'=>'2026-08-10 14:30:00']);
        $paidReport = $monthly->generate('2026-08');
        $assert((int)$paidReport->paid_orders_count > 0, 'Платена поръчка с платежни операции не влиза в месечния отчет.');

        // CSV/XLSX/package behavior.
        $csv = $exports->csv($sales['rows']); $assert(str_contains($csv,'Дата') && !str_contains($csv,'status'), 'CSV не е локализиран.');
        $emptyCsv = $exports->csv([]); $assert(str_contains($emptyCsv,'Информация'), 'Празният CSV няма заглавие.');
        $xlsxPath = tempnam(sys_get_temp_dir(),'accounting-full-xlsx-'); $assert($xlsxPath !== false && file_put_contents($xlsxPath,$exports->xlsx($sales['rows'],'Счетоводство')) !== false, 'XLSX не се създава.');
        $xlsx = new ZipArchive(); $assert($xlsx->open($xlsxPath) === true && $xlsx->locateName('xl/worksheets/sheet1.xml') !== false, 'XLSX е невалиден.'); $xlsx->close(); @unlink($xlsxPath);
        $mixed = $exports->package('2026-08-01','2026-08-31','test-full-mixed'); $empty = $exports->package('2099-01-01','2099-01-31','test-full-empty'); $archives=[$mixed,$empty];
        foreach ([$mixed,$empty] as $relative) { $zip=new ZipArchive(); $path=$root.'/'.ltrim($relative,'/'); $assert(is_file($path) && $zip->open($path)===true,'ZIP пакетът е невалиден.'); $pdfs=[]; for($i=0;$i<$zip->numFiles;$i++){ $entry=$zip->getNameIndex($i); if(is_string($entry)&&str_ends_with($entry,'.pdf'))$pdfs[]=$entry; } $zip->close(); if($relative===$mixed)$assert(count($pdfs) >= 2,'Смесеният пакет няма PDF файлове.'); else $assert(count($pdfs)===0,'Празният пакет съдържа PDF.'); }

        // Econt guard and cleanup validation.
        $setting=$settingClass::query()->firstOrCreate([]); $old=(bool)$setting->econt_operations_enabled; $setting->econt_operations_enabled=false; $setting->save(); $expectFailure(static fn()=>$accounting->reconcileEcont(['order_id'=>$template->order_id,'shipment_status'=>'sent']),'Изключеният Econt е достъпен.'); $setting->econt_operations_enabled=$old; $setting->save();
        foreach($invoiceClass::query()->where('number','like','TEST-FULL-%')->get() as $document) if($document->pdf_path!==null)$files[]=$root.'/'.ltrim((string)$document->pdf_path,'/');
        foreach($archives as $relative) @unlink($root.'/'.ltrim($relative,'/')); foreach($files as $file) @unlink($file);
        throw new RuntimeException('ACCOUNTING_FULL_TEST_RESULT:'.json_encode(['status'=>'passed','reports'=>'passed','filters'=>'passed','exports'=>'passed','empty_package'=>'passed','mixed_package'=>'passed','econt_guard'=>'passed'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    });
} catch (Throwable $exception) {
    if (str_starts_with($exception->getMessage(),'ACCOUNTING_FULL_TEST_RESULT:')) { echo substr($exception->getMessage(),strlen('ACCOUNTING_FULL_TEST_RESULT:')).PHP_EOL; exit(0); }
    fwrite(STDERR,$exception->getMessage().PHP_EOL); exit(1);
}
