<?php
declare(strict_types=1);
namespace App\Services\Accounting;

use App\Core\Auth;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\AccountingAuditLog;
use App\Models\AccountingPeriodClosure;
use App\Models\AccountingTransaction;
use App\Models\EcontReconciliation;
use App\Models\Invoice;
use App\Models\SiteSetting;
use App\Models\Order;
use Illuminate\Support\Collection;

final class AccountingService
{
    public const METHODS = ['card','bank_transfer','cash_on_delivery'];
    public const SHIPMENT_STATUSES = ['sent','delivered','returned'];

    public function __construct(private readonly AccountingPeriodLock $lock = new AccountingPeriodLock(), private readonly AccountingAuditService $audit = new AccountingAuditService()) {}

    public function filters(array $input): array
    {
        $first = date('Y-m-01'); $last = date('Y-m-t');
        $from = $this->date((string) ($input['date_from'] ?? $first), 'date_from');
        $to = $this->date((string) ($input['date_to'] ?? $last), 'date_to');
        if ($from > $to) throw new ValidationException(['date_to'=>['Крайната дата трябва да е след началната.']]);
        return ['date_from'=>$from,'date_to'=>$to,'order_status'=>$this->choice($input['order_status'] ?? 'all', ['all','pending','confirmed','processing','shipped','delivered','paid','cancelled']), 'payment_method'=>$this->choice($input['payment_method'] ?? 'all', array_merge(['all'], self::METHODS)), 'invoiced'=>$this->choice($input['invoiced'] ?? 'all', ['all','yes','no']), 'paid'=>$this->choice($input['paid'] ?? 'all', ['all','yes','no'])];
    }

    public function dashboard(array $input): array
    {
        $filters = $this->filters($input); $orders = $this->orders($filters); $orderIds = $orders->pluck('id')->map(fn($v)=>(int)$v)->all();
        $invoices = Invoice::query()->whereIn('order_id', $orderIds ?: [0])->where('type', 'invoice')->whereIn('status',['issued','credited'])->whereBetween('issue_date',[$filters['date_from'],$filters['date_to']])->get();
        $salesDocs = $invoices;
        $creditQuery = Invoice::query()->where('type', 'credit_note')->whereIn('status', ['issued', 'credited'])->whereBetween('issue_date', [$filters['date_from'], $filters['date_to']]);
        $this->applyOrderFilters($creditQuery, $filters);
        $credits = $creditQuery->get();
        $covered = $salesDocs->pluck('order_id')->flip(); $turnover=0.0; $net=0.0; $vat=0.0;
        foreach ($orders as $order) {
            $amounts = $this->orderAmounts($order, $salesDocs->firstWhere('order_id', $order->id));
            $turnover += $amounts['gross']; $net += $amounts['base']; $vat += $amounts['vat'];
        }
        foreach ($credits as $credit) {
            // Credit note snapshots currently use negative values. Normalize the
            // sign here so legacy positive snapshots cannot increase the totals.
            $turnover -= abs((float) $credit->total_gross);
            $net -= abs((float) $credit->subtotal_net - (float) $credit->discount_net + (float) $credit->shipping_net);
            $vat -= abs((float) $credit->tax_amount);
        }
        $transactions = AccountingTransaction::query()->whereIn('order_id',$orderIds ?: [0])->where('status','completed')->whereBetween('occurred_at',[$filters['date_from'].' 00:00:00',$filters['date_to'].' 23:59:59'])->get();
        $payments=$transactions->where('type','payment'); $refunds=$transactions->where('type','refund'); $paidIds=$payments->groupBy('order_id')->filter(fn($rows,$id)=>(float)$rows->sum('amount') >= (float)($orders->firstWhere('id',(int)$id)?->total ?? PHP_FLOAT_MAX)-0.009)->keys();
        $paidOrders=$orders->filter(fn(Order $order): bool => $paidIds->contains((int) $order->id)); $unpaidOrders=$orders->reject(fn(Order $order): bool => $paidIds->contains((int) $order->id)); $paidOrderIds=$paidOrders->pluck('id')->all(); $paidCredits=$credits->whereIn('order_id',$paidOrderIds); $paidOrdersAmount=round((float)$paidOrders->sum('total')-$paidCredits->sum(fn(Invoice $credit): float => abs((float)$credit->total_gross)),2); $creditNotesAmount=round($credits->sum(fn(Invoice $credit): float => abs((float)$credit->total_gross)),2); $byMethod=[]; foreach(self::METHODS as $method) $byMethod[$method]=round((float)$payments->where('method',$method)->sum('amount'),2);
        return ['filters'=>$filters,'date_basis'=>self::dateBasis(),'summary'=>['turnover'=>round($turnover,2),'tax_base'=>round($net,2),'vat'=>round($vat,2),'paid_orders'=>$paidOrders->count(),'paid_orders_amount'=>$paidOrdersAmount,'unpaid_orders'=>$unpaidOrders->count(),'refunded_amount'=>round((float)$refunds->sum('amount'),2),'credit_notes_count'=>$credits->count(),'credit_notes_amount'=>$creditNotesAmount,'orders_count'=>$orders->count(),'currency'=>'EUR'],'payment_methods'=>$byMethod,'closures'=>$this->closures()];
    }

    public function report(string $type, array $input): array
    {
        $filters=$this->filters($input); $orders=$this->orders($filters); $ids=$orders->pluck('id')->all();
        if (in_array($type,['invoices','credit_notes'],true)) {
            $kind=$type==='invoices'?'invoice':'credit_note';
            $query=Invoice::query()->with('order')->where('type',$kind)->whereBetween('issue_date',[$filters['date_from'],$filters['date_to']]);
            $this->applyOrderFilters($query,$filters);
            $rows=$query->orderBy('issue_date')->get()->map(function(Invoice $i) use ($kind){$base=round((float)$i->subtotal_net-(float)$i->discount_net+(float)$i->shipping_net,2); return ['date'=>$i->issue_date?->format('Y-m-d'),'number'=>$i->number,'order'=>$i->order?->number,'client'=>$i->buyer_snapshot['company']??'','eik'=>$i->buyer_snapshot['eik']??'','tax_base'=>$kind==='credit_note'?-abs($base):$base,'vat'=>$kind==='credit_note'?-abs((float)$i->tax_amount):(float)$i->tax_amount,'total'=>$kind==='credit_note'?-abs((float)$i->total_gross):(float)$i->total_gross,'status'=>$i->status];})->all();
            return ['type'=>$type,'filters'=>$filters,'date_basis'=>self::dateBasis(),'columns'=>$this->columns($type),'rows'=>$rows];
        }
        if (in_array($type,['payments','refunds','card','bank_transfer','cash_on_delivery'],true)) {
            $query=AccountingTransaction::query()->with('order')->whereBetween('occurred_at',[$filters['date_from'].' 00:00:00',$filters['date_to'].' 23:59:59']);
            $this->applyOrderFilters($query,$filters);
            if ($type==='refunds') $query->where('type','refund'); else $query->where('type','payment');
            if (in_array($type,self::METHODS,true)) $query->where('method',$type);
            $rows=$query->orderBy('occurred_at')->get()->map(fn(AccountingTransaction $t)=>['date'=>$t->occurred_at?->format('Y-m-d H:i'),'order'=>$t->order?->number,'type'=>$t->type,'method'=>$t->method,'amount'=>(float)$t->amount,'status'=>$t->status,'reference'=>$t->external_reference])->all();
            return ['type'=>$type,'filters'=>$filters,'date_basis'=>self::dateBasis(),'columns'=>$this->columns($type),'rows'=>$rows];
        }
        if ($type==='deliveries') {
            $query=EcontReconciliation::query()->with('order')->whereBetween('received_at',[$filters['date_from'].' 00:00:00',$filters['date_to'].' 23:59:59']);
            $this->applyOrderFilters($query,$filters);
            $rows=$query->orderBy('received_at')->get()->map(fn(EcontReconciliation $e)=>['order'=>$e->order?->number,'tracking_number'=>$e->tracking_number_snapshot,'status'=>$e->shipment_status,'cod_amount'=>(float)$e->cod_amount,'received_amount'=>(float)$e->company_received_amount,'difference'=>round((float)$e->cod_amount-(float)$e->company_received_amount,2),'received_at'=>$e->received_at?->format('Y-m-d H:i')])->all();
            return ['type'=>$type,'filters'=>$filters,'date_basis'=>self::dateBasis(),'columns'=>$this->columns($type),'rows'=>$rows];
        }
        if ($type!=='sales') throw new ValidationException(['report'=>['Невалиден вид справка.']]);
        $rows=$orders->map(function(Order $o){ $invoice=$o->invoices->first(fn(Invoice $i)=>$i->type==='invoice'&&$i->status!=='cancelled'); $amounts=$this->orderAmounts($o,$invoice); return ['date'=>$o->created_at?->format('Y-m-d'),'order'=>$o->number,'customer'=>trim($o->first_name.' '.$o->last_name),'status'=>$o->status,'payment_method'=>$o->payment_method,'invoiced'=>(bool)$invoice,'tax_base'=>$amounts['base'],'vat'=>$amounts['vat'],'total'=>$amounts['gross']]; })->all();
        $creditQuery=Invoice::query()->with('order')->where('type','credit_note')->whereIn('status',['issued','credited'])->whereBetween('issue_date',[$filters['date_from'],$filters['date_to']]); $this->applyOrderFilters($creditQuery,$filters);
        foreach($creditQuery->orderBy('issue_date')->get() as $credit){$order=$credit->order; $rows[]=['date'=>$credit->issue_date?->format('Y-m-d'),'order'=>$order?->number,'customer'=>$credit->buyer_snapshot['company']??($order !== null ? trim($order->first_name.' '.$order->last_name) : ''),'status'=>$credit->status,'payment_method'=>$order?->payment_method,'invoiced'=>true,'tax_base'=>-abs(round((float)$credit->subtotal_net-(float)$credit->discount_net+(float)$credit->shipping_net,2)),'vat'=>-abs((float)$credit->tax_amount),'total'=>-abs((float)$credit->total_gross)];}
        usort($rows,static fn(array $left,array $right): int => strcmp((string)($left['date']??''),(string)($right['date']??'')));
        return ['type'=>$type,'filters'=>$filters,'date_basis'=>self::dateBasis(),'columns'=>$this->columns($type),'rows'=>$rows];
    }

    public function createTransaction(array $input): AccountingTransaction
    {
        $order=Order::query()->find((int)($input['order_id']??0)); if(!$order) throw new ValidationException(['order_id'=>['Изберете валидна поръчка.']]);
        $type=$this->choice($input['type']??'', ['payment','refund']); $method=$this->choice($input['method']??$order->payment_method,self::METHODS); $status=$this->choice($input['status']??'completed',['pending','completed','failed','cancelled']);
        $amount=round((float)($input['amount']??0),2); if($amount<=0) throw new ValidationException(['amount'=>['Сумата трябва да е по-голяма от нула.']]);
        if($status==='completed'){
            $payments=(float)$order->accountingTransactions()->where('type','payment')->where('status','completed')->sum('amount');
            $refunded=(float)$order->accountingTransactions()->where('type','refund')->where('status','completed')->sum('amount');
            $credits=(float)$order->invoices()->where('type','credit_note')->whereIn('status',['issued','credited'])->sum('total_gross');
            $creditAmount=abs($credits);
            if($type==='payment' && $amount>$order->total-$payments+0.009) throw new ValidationException(['amount'=>['Плащането надвишава оставащата сума по поръчката.']]);
            if($type==='refund' && $amount>$payments-$refunded-$creditAmount+0.009) throw new ValidationException(['amount'=>['Възстановяването надвишава наличната сума след плащанията и кредитните известия.']]);
        }
        $occurred=(string)($input['occurred_at']??date('Y-m-d H:i:s')); $this->lock->assertUnlocked($occurred);
        $tx=AccountingTransaction::query()->create(['order_id'=>$order->id,'type'=>$type,'method'=>$method,'status'=>$status,'amount'=>$amount,'currency'=>$order->currency,'external_reference'=>$this->nullable($input['external_reference']??null),'notes'=>$this->nullable($input['notes']??null),'occurred_at'=>$occurred,'created_by'=>Auth::user()?->id]);
        $this->audit->write('transaction.created','accounting_transaction',(int)$tx->id,null,$tx->toArray()); return $tx->load('order');
    }

    public function reconcileEcont(array $input): EcontReconciliation
    {
        if (!(bool) (SiteSetting::query()->firstOrCreate([])->econt_operations_enabled)) {
            throw new ValidationException(['econt' => ['Товарителниците и заявяването на куриер са изключени от Настройки.']]);
        }
        $order=Order::query()->find((int)($input['order_id']??0)); if(!$order) throw new ValidationException(['order_id'=>['Изберете валидна поръчка.']]);
        $this->lock->assertUnlocked($order->created_at); $status=$this->choice($input['shipment_status']??'',self::SHIPMENT_STATUSES);
        $before=EcontReconciliation::query()->where('order_id',$order->id)->first(); $beforeData=$before?->toArray();
        $row=EcontReconciliation::query()->updateOrCreate(['order_id'=>$order->id],['shipment_status'=>$status,'tracking_number_snapshot'=>$this->nullable($input['tracking_number']??$order->tracking_number),'cod_amount'=>max(0,round((float)($input['cod_amount']??0),2)),'company_received_amount'=>max(0,round((float)($input['company_received_amount']??0),2)),'received_at'=>$this->nullable($input['received_at']??null),'notes'=>$this->nullable($input['notes']??null),'updated_by'=>Auth::user()?->id]);
        $this->audit->write('econt.reconciled','econt_reconciliation',(int)$row->id,$beforeData,$row->toArray()); return $row->load('order');
    }

    public function close(string $period, callable $packageGenerator): AccountingPeriodClosure
    {
        if(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$period)!==1) throw new ValidationException(['period'=>['Изберете валиден месец.']]);
        if(AccountingPeriodClosure::query()->where('period',$period)->exists()) throw new ValidationException(['period'=>['Периодът вече е приключен.']]);
        [$year,$month]=array_map('intval',explode('-',$period)); $from=sprintf('%04d-%02d-01',$year,$month); $to=date('Y-m-t',strtotime($from)); $snapshot=$this->dashboard(['date_from'=>$from,'date_to'=>$to]);
        $closure=AccountingPeriodClosure::query()->create(['period'=>$period,'status'=>'closed','summary_snapshot'=>$snapshot,'closed_at'=>date('Y-m-d H:i:s'),'closed_by'=>Auth::user()?->id]);
        try { $closure->package_path=$packageGenerator($from,$to,$period); $closure->save(); } catch(\Throwable $e) { $closure->delete(); throw $e; }
        $this->audit->write('period.closed','accounting_period_closure',(int)$closure->id,null,$closure->toArray()); return $closure;
    }

    public function closures(): array { return AccountingPeriodClosure::query()->orderByDesc('period')->get()->map(fn($c)=>['id'=>$c->id,'period'=>$c->period,'status'=>$c->status,'closed_at'=>$c->closed_at?->format('Y-m-d H:i'),'has_package'=>(bool)$c->package_path])->all(); }
    public function auditLog(): array { return AccountingAuditLog::query()->orderByDesc('created_at')->limit(200)->get()->map(fn($l)=>$l->toArray())->all(); }

    private function orders(array $f): Collection
    {
        $q=Order::query()->with(['invoices','accountingTransactions'])->whereBetween('created_at',[$f['date_from'].' 00:00:00',$f['date_to'].' 23:59:59']);
        if($f['order_status']!=='all')$q->where('status',$f['order_status']); if($f['payment_method']!=='all')$q->where('payment_method',$f['payment_method']);
        $rows=$q->orderBy('created_at')->get();
        return $rows->filter(function(Order $o)use($f){$invoiced=$o->invoices->contains(fn(Invoice $i)=>$i->type==='invoice'&&$i->status!=='cancelled'&&$i->issue_date?->format('Y-m-d') >= $f['date_from']&&$i->issue_date?->format('Y-m-d') <= $f['date_to']);$paid=(float)$o->accountingTransactions->where('type','payment')->where('status','completed')->sum('amount') >= (float)$o->total-0.009;return ($f['invoiced']==='all'||($f['invoiced']==='yes')===$invoiced)&&($f['paid']==='all'||($f['paid']==='yes')===$paid);});
    }
    private function applyOrderFilters(object $query,array $f): void
    {
        if($f['order_status']!=='all')$query->whereHas('order',fn($q)=>$q->where('status',$f['order_status']));
        if($f['payment_method']!=='all')$query->whereHas('order',fn($q)=>$q->where('payment_method',$f['payment_method']));
        if($f['invoiced']!=='all')$query->whereHas('order',function($q)use($f){$method=$f['invoiced']==='yes'?'whereHas':'whereDoesntHave';$q->{$method}('invoices',fn($i)=>$i->where('type','invoice')->where('status','!=','cancelled')->whereBetween('issue_date',[$f['date_from'],$f['date_to']]));});
        if($f['paid']!=='all')$query->whereHas('order',function($q)use($f){$q->whereRaw(($f['paid']==='yes'?'':'NOT ').'(SELECT COALESCE(SUM(amount),0) FROM accounting_transactions atx WHERE atx.order_id = orders.id AND atx.type = ? AND atx.status = ?) >= orders.total',['payment','completed']);});
    }
    private function date(string $value,string $field): string { $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$value); if(!$d||$d->format('Y-m-d')!==$value)throw new ValidationException([$field=>['Невалидна дата.']]);return $value; }
    private function choice(mixed $value,array $allowed): string { $value=(string)$value;if(!in_array($value,$allowed,true))throw new ValidationException(['filter'=>['Невалидна стойност.']]);return $value; }
    private function nullable(mixed $value): ?string { $v=trim((string)$value);return $v===''?null:$v; }
    private static function dateBasis(): array { return ['sales'=>'Дата на поръчката','documents'=>'Дата на издаване','payments'=>'Дата на платежната операция','deliveries'=>'Дата на получаване/сверяване']; }
    private function columns(string $type): array
    {
        return match ($type) {
            'sales' => ['date','order','customer','status','payment_method','invoiced','tax_base','vat','total'],
            'invoices' => ['date','number','order','client','eik','tax_base','vat','total','status'],
            'credit_notes' => ['date','number','order','client','eik','tax_base','vat','total','status'],
            'payments', 'refunds', 'card', 'bank_transfer', 'cash_on_delivery' => ['date','order','type','method','amount','status','reference'],
            'deliveries' => ['order','tracking_number','status','cod_amount','received_amount','difference','received_at'],
            default => [],
        };
    }
    private function orderAmounts(Order $order, ?Invoice $invoice): array
    {
        if ($invoice !== null) {
            $gross = round((float) $invoice->total_gross, 2);
            $base = round((float) $invoice->subtotal_net - (float) $invoice->discount_net + (float) $invoice->shipping_net, 2);
            return ['gross' => $gross, 'base' => $base, 'vat' => round($gross - $base, 2)];
        }
        $gross = round((float) $order->total, 2); $rate = $order->vat_enabled ? max(0.0, (float) $order->vat_rate) : 0.0;
        $base = round($gross / (1 + $rate / 100), 2);
        return ['gross' => $gross, 'base' => $base, 'vat' => round($gross - $base, 2)];
    }
}
