<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Models\AccountingPeriodClosure;
use App\Services\Accounting\AccountingExportService;
use App\Services\Accounting\AccountingService;

final class AccountingController extends Controller
{
    public function __construct(private readonly AccountingService $accounting=new AccountingService(),private readonly AccountingExportService $exports=new AccountingExportService()){}
    public function dashboard(): never { $data=$this->accounting->dashboard(Request::query());$data['audit_log']=$this->accounting->auditLog();$this->ok($data); }
    public function report(string $type): never { $this->ok($this->accounting->report($type,Request::query())); }
    public function transaction(): never { $this->created(['transaction'=>$this->accounting->createTransaction(Request::input())->toArray()],'Операцията е записана.'); }
    public function reconcile(): never { $this->ok(['reconciliation'=>$this->accounting->reconcileEcont(Request::input())->toArray()],'Econt данните са сверени.'); }
    public function export(string $type,string $format): never
    {
        $report=$this->accounting->report($type,Request::query());$content=$format==='csv'?$this->exports->csv($report['rows']):($format==='xlsx'?$this->exports->xlsx($report['rows'],'Счетоводство'):null);if($content===null)$this->error('Невалиден формат.',422);
        header('Content-Type: '.($format==='xlsx'?'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':'text/csv; charset=utf-8'));header('Content-Disposition: attachment; filename="accounting-'.$type.'-'.date('Y-m-d').'.'.$format.'"');header('Content-Length: '.strlen($content));echo $content;exit;
    }
    public function package(): never { $input=Request::input();$filters=$this->accounting->filters($input);$path=$this->exports->package($filters['date_from'],$filters['date_to'],$filters['date_from'].'_'.$filters['date_to']);$this->downloadPath($path,'accounting-package-'.$filters['date_from'].'_'.$filters['date_to'].'.zip'); }
    public function close(): never { $period=(string)Request::input('period','');$closure=$this->accounting->close($period,fn($from,$to,$label)=>$this->exports->package($from,$to,$label));$this->created(['closure'=>$closure->toArray()],'Месецът е приключен, пакетът е генериран и периодът е заключен.'); }
    public function downloadClosure(string $id): never { if(!ctype_digit($id))$this->error('Периодът не е намерен.',404);$row=AccountingPeriodClosure::query()->find((int)$id);if(!$row||!$row->package_path)$this->error('Пакетът не е намерен.',404);$this->downloadPath($row->package_path,'accounting-package-'.$row->period.'.zip'); }
    private function downloadPath(string $relative,string $name): never {$path=dirname(__DIR__,4).'/'.ltrim($relative,'/');if(!is_file($path))$this->error('Файлът не е намерен.',404);header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($path));readfile($path);exit;}
}
