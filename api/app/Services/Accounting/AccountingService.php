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
        return ['date_from'=>$from,'date_to'=>$to,'order_status'=>$this->choice($input['order_status'] ?? 'all', ['all','pending','confirmed','processing','shipped','delivered','cancelled']), 'payment_method'=>$this->choice($input['payment_method'] ?? 'all', array_merge(['all'], self::METHODS)), 'invoiced'=>$this->choice($input['invoiced'] ?? 'all', ['all','yes','no']), 'paid'=>$this->choice($input['paid'] ?? 'all', ['all','yes','no'])];
    }

    public function dashboard(array $input): array
    {
        $filters = $this->filters($input); $orders = $this->orders($filters); $orderIds = $orders->pluck('id')->map(fn($v)=>(int)$v)->all();
        $invoices = Invoice::query()->whereIn('order_id', $orderIds ?: [0])->whereIn('status',['issued','credited'])->whereBetween('issue_date',[$filters['date_from'],$filters['date_to']])->get();
        $salesDocs = $invoices->where('type','invoice'); $credits = $invoices->where('type','credit_note');
        $covered = $salesDocs->pluck('order_id')->flip(); $turnover=0.0; $net=0.0; $vat=0.0;
        foreach ($orders as $order) {
            $doc = $salesDocs->firstWhere('order_id',$order->id);
            if ($doc) { $turnover += (float)$doc->total_gross; $net += (float)$doc->subtotal_net-(float)$doc->discount_net+(float)$doc->shipping_net; $vat += (float)$doc->tax_amount; }
            else { $gross=(float)$order->total; $rate=$order->vat_enabled?(float)$order->vat_rate:0.0; $base=round($gross/(1+$rate/100),2); $turnover += $gross; $net += $base; $vat += round($gross-$base,2); }
        }
        foreach ($credits as $credit) { $turnover += (float)$credit->total_gross; $net += (float)$credit->subtotal_net-(float)$credit->discount_net+(float)$credit->shipping_net; $vat += (float)$credit->tax_amount; }
        $transactions = AccountingTransaction::query()->whereIn('order_id',$orderIds ?: [0])->where('status','completed')->whereBetween('occurred_at',[$filters['date_from'].' 00:00:00',$filters['date_to'].' 23:59:59'])->get();
        $payments=$transactions->where('type','payment'); $refunds=$transactions->where('type','refund'); $paidIds=$payments->groupBy('order_id')->filter(fn($rows,$id)=>(float)$rows->sum('amount') >= (float)($orders->firstWhere('id',(int)$id)?->total ?? PHP_FLOAT_MAX)-0.009)->keys();
        $byMethod=[]; foreach(self::METHODS as $method) $byMethod[$method]=round((float)$payments->where('method',$method)->sum('amount'),2);
        return ['filters'=>$filters,'summary'=>['turnover'=>round($turnover,2),'tax_base'=>round($net,2),'vat'=>round($vat,2),'paid_orders'=>$paidIds->count(),'unpaid_orders'=>max(0,$orders->count()-$paidIds->count()),'refunded_amount'=>round((float)$refunds->sum('amount'),2),'credit_notes_count'=>$credits->count(),'credit_notes_amount'=>round(abs((float)$credits->sum('total_gross')),2),'orders_count'=>$orders->count(),'currency'=>'EUR'],'payment_methods'=>$byMethod,'closures'=>$this->closures()];
    }

    public function report(string $type, array $input): array
    {
        $filters=$this->filters($input); $orders=$this->orders($filters); $ids=$orders->pluck('id')->all();
        if (in_array($type,['invoices','credit_notes'],true)) {
            $kind=$type==='invoices'?'invoice':'credit_note';
            $query=Invoice::query()->with('order')->where('type',$kind)->whereBetween('issue_date',[$filters['date_from'],$filters['date_to']]);
            $this->applyOrderFilters($query,$filters);
            $rows=$query->orderBy('issue_date')->get()->map(fn(Invoice $i)=>['date'=>$i->issue_date?->format('Y-m-d'),'number'=>$i->number,'order'=>$i->order?->number,'client'=>$i->buyer_snapshot['company']??'','eik'=>$i->buyer_snapshot['eik']??'','tax_base'=>round((float)$i->subtotal_net-(float)$i->discount_net+(float)$i->shipping_net,2),'vat'=>(float)$i->tax_amount,'total'=>(float)$i->total_gross,'status'=>$i->status])->all();
            return ['type'=>$type,'filters'=>$filters,'rows'=>$rows];
        }
        if (in_array($type,['payments','refunds','card','bank_transfer','cash_on_delivery'],true)) {
            $query=AccountingTransaction::query()->with('order')->whereBetween('occurred_at',[$filters['date_from'].' 00:00:00',$filters['date_to'].' 23:59:59']);
            $this->applyOrderFilters($query,$filters);
            if ($type==='refunds') $query->where('type','refund'); else $query->where('type','payment');
            if (in_array($type,self::METHODS,true)) $query->where('method',$type);
            $rows=$query->orderBy('occurred_at')->get()->map(fn(AccountingTransaction $t)=>['date'=>$t->occurred_at?->format('Y-m-d H:i'),'order'=>$t->order?->number,'type'=>$t->type,'method'=>$t->method,'amount'=>(float)$t->amount,'status'=>$t->status,'reference'=>$t->external_reference])->all();
            return ['type'=>$type,'filters'=>$filters,'rows'=>$rows];
        }
        if ($type==='deliveries') {
            $rows=EcontReconciliation::query()->with('order')->whereIn('order_id',$ids?:[0])->get()->map(fn(EcontReconciliation $e)=>['order'=>$e->order?->number,'tracking_number'=>$e->tracking_number_snapshot,'status'=>$e->shipment_status,'cod_amount'=>(float)$e->cod_amount,'received_amount'=>(float)$e->company_received_amount,'difference'=>round((float)$e->cod_amount-(float)$e->company_received_amount,2),'received_at'=>$e->received_at?->format('Y-m-d H:i')])->all();
            return ['type'=>$type,'filters'=>$filters,'rows'=>$rows];
        }
        if ($type!=='sales') throw new ValidationException(['report'=>['Невалиден вид справка.']]);
        $rows=$orders->map(function(Order $o){ $invoice=$o->invoices->first(fn(Invoice $i)=>$i->type==='invoice'&&$i->status!=='cancelled'); $gross=$invoice?(float)$invoice->total_gross:(float)$o->total; $base=$invoice?round((float)$invoice->subtotal_net-(float)$invoice->discount_net+(float)$invoice->shipping_net,2):round($gross/(1+($o->vat_enabled?(float)$o->vat_rate:0)/100),2); return ['date'=>$o->created_at?->format('Y-m-d'),'order'=>$o->number,'customer'=>trim($o->first_name.' '.$o->last_name),'status'=>$o->status,'payment_method'=>$o->payment_method,'invoiced'=>(bool)$invoice,'tax_base'=>$base,'vat'=>round($gross-$base,2),'total'=>$gross]; })->all();
        return ['type'=>$type,'filters'=>$filters,'rows'=>$rows];
    }

    public function createTransaction(array $input): AccountingTransaction
    {
        $order=Order::query()->find((int)($input['order_id']??0)); if(!$order) throw new ValidationException(['order_id'=>['Изберете валидна поръчка.']]);
        $type=$this->choice($input['type']??'', ['payment','refund']); $method=$this->choice($input['method']??$order->payment_method,self::METHODS); $status=$this->choice($input['status']??'completed',['pending','completed','failed','cancelled']);
        $amount=round((float)($input['amount']??0),2); if($amount<=0) throw new ValidationException(['amount'=>['Сумата трябва да е по-голяма от нула.']]);
        if($type==='refund'&&$status==='completed'){$payments=(float)$order->accountingTransactions()->where('type','payment')->where('status','completed')->sum('amount');$refunded=(float)$order->accountingTransactions()->where('type','refund')->where('status','completed')->sum('amount');if($amount>$payments-$refunded+0.009)throw new ValidationException(['amount'=>['Възстановяването надвишава наличната платена сума по поръчката.']]);}
        $occurred=(string)($input['occurred_at']??date('Y-m-d H:i:s')); $this->lock->assertUnlocked($occurred);
        $tx=AccountingTransaction::query()->create(['order_id'=>$order->id,'type'=>$type,'method'=>$method,'status'=>$status,'amount'=>$amount,'currency'=>$order->currency,'external_reference'=>$this->nullable($input['external_reference']??null),'notes'=>$this->nullable($input['notes']??null),'occurred_at'=>$occurred,'created_by'=>Auth::user()?->id]);
        $this->audit->write('transaction.created','accounting_transaction',(int)$tx->id,null,$tx->toArray()); return $tx->load('order');
    }

    public function reconcileEcont(array $input): EcontReconciliation
    {
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
        return $rows->filter(function(Order $o)use($f){$invoiced=$o->invoices->contains(fn(Invoice $i)=>$i->type==='invoice'&&$i->status!=='cancelled');$paid=(float)$o->accountingTransactions->where('type','payment')->where('status','completed')->sum('amount') >= (float)$o->total-0.009;return ($f['invoiced']==='all'||($f['invoiced']==='yes')===$invoiced)&&($f['paid']==='all'||($f['paid']==='yes')===$paid);});
    }
    private function applyOrderFilters(object $query,array $f): void
    {
        if($f['order_status']!=='all')$query->whereHas('order',fn($q)=>$q->where('status',$f['order_status']));
        if($f['payment_method']!=='all')$query->whereHas('order',fn($q)=>$q->where('payment_method',$f['payment_method']));
        if($f['invoiced']!=='all')$query->whereHas('order',function($q)use($f){$method=$f['invoiced']==='yes'?'whereHas':'whereDoesntHave';$q->{$method}('invoices',fn($i)=>$i->where('type','invoice')->where('status','!=','cancelled'));});
        if($f['paid']!=='all')$query->whereHas('order',function($q)use($f){$q->whereRaw(($f['paid']==='yes'?'':'NOT ').'(SELECT COALESCE(SUM(amount),0) FROM accounting_transactions atx WHERE atx.order_id = orders.id AND atx.type = ? AND atx.status = ?) >= orders.total',['payment','completed']);});
    }
    private function date(string $value,string $field): string { $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$value); if(!$d||$d->format('Y-m-d')!==$value)throw new ValidationException([$field=>['Невалидна дата.']]);return $value; }
    private function choice(mixed $value,array $allowed): string { $value=(string)$value;if(!in_array($value,$allowed,true))throw new ValidationException(['filter'=>['Невалидна стойност.']]);return $value; }
    private function nullable(mixed $value): ?string { $v=trim((string)$value);return $v===''?null:$v; }
}
