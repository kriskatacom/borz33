import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, ExternalLink, Mail, MapPin, Package, Phone, Save, Truck } from 'lucide-react';
import { ApiError } from '@/api/client';
import { getOrder, updateOrderStatus, type AdminOrder, type OrderStatus } from '@/api/orders';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { deliveryLabel, ORDER_STATUSES, OrderStatusBadge, paymentLabel } from '@/features/orders/orderFormat';
import { formatDateTime, formatMoney } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';

export function OrderDetailsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const id = Number(useParams().id);
  const [order, setOrder] = useState<AdminOrder | null>(null);
  const [status, setStatus] = useState<OrderStatus>('pending');
  const [trackingNumber, setTrackingNumber] = useState('');
  const [busy, setBusy] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  useGlobalLoading(busy);

  useEffect(() => {
    let cancelled = false;
    setBusy(true);
    void getOrder(token, id).then((response) => {
      if (cancelled) return;
      setOrder(response.data.order);
      setStatus(response.data.order.status);
      setTrackingNumber(response.data.order.tracking_number ?? '');
    }).catch((error) => { if (!cancelled) setMessage(error instanceof ApiError ? error.message : 'Поръчката не можа да се зареди.'); }).finally(() => { if (!cancelled) setBusy(false); });
    return () => { cancelled = true; };
  }, [id, token]);

  async function saveStatus() {
    if (!order || (status === order.status && trackingNumber.trim() === (order.tracking_number ?? ''))) return;
    setSaving(true);
    try {
      const response = await updateOrderStatus(token, order.id, status, trackingNumber.trim());
      setOrder(response.data.order);
      setTrackingNumber(response.data.order.tracking_number ?? '');
      if (!response.data.status_changed || response.data.email_sent) toast.success(response.message || 'Поръчката е обновена.');
      else toast.error(response.message || 'Статусът е обновен, но имейлът не можа да бъде изпратен.');
    } catch (error) {
      toastError(error, 'Статусът не можа да се обнови.');
    } finally { setSaving(false); }
  }

  if (message) return <div className="page"><PageHeader title="Поръчка" crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Поръчки', to: routes.orders }, { label: 'Детайли' }]} /><p className="form-message is-error">{message}</p><Button asChild variant="outline"><Link to={routes.orders}><ArrowLeft />Към поръчките</Link></Button></div>;
  if (!order) return <div className="page" aria-busy="true" />;

  return (
    <div className="page">
      <PageHeader title={`Поръчка #${order.number}`} help={`Получена ${formatDateTime(order.created_at)}`} crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Поръчки', to: routes.orders }, { label: `#${order.number}` }]} actions={<Button asChild variant="outline"><Link to={routes.orders}><ArrowLeft />Назад</Link></Button>} />
      <div className="mb-4 grid gap-3 border border-border bg-card p-4 md:grid-cols-[minmax(15rem,1fr)_minmax(16rem,1fr)_auto] md:items-end">
        <div className="field"><LabelWithHelp htmlFor="order-status" label="Статус на поръчката" help="Променете етапа според реалното изпълнение." /><Select value={status} onValueChange={(value) => setStatus(value as OrderStatus)}><SelectTrigger id="order-status" className="min-h-12 w-full sm:w-72"><SelectValue /></SelectTrigger><SelectContent>{ORDER_STATUSES.map((item) => <SelectItem key={item.value} value={item.value}>{item.label}</SelectItem>)}</SelectContent></Select></div>
        <Field id="tracking-number" label="Номер на товарителница" help="Незадължителен. Ако е въведен, клиентът ще получи линк за проследяване." value={trackingNumber} placeholder="Напр. 5562000542851" onChange={(event) => setTrackingNumber(event.target.value)} />
        <Button disabled={saving || (status === order.status && trackingNumber.trim() === (order.tracking_number ?? ''))} onClick={() => void saveStatus()}><Save />{saving ? 'Запис…' : 'Запази'}</Button>
      </div>
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(18rem,1fr)]">
        <section className="border border-border bg-card"><div className="flex items-center justify-between border-b border-border p-4"><h2 className="m-0 text-xl">Артикули</h2><OrderStatusBadge status={order.status} /></div><div className="divide-y divide-border">{order.items.map((item) => <article key={item.id} className="grid gap-3 p-4 sm:grid-cols-[4.5rem_minmax(0,1fr)_auto] sm:items-start">{item.product_image_url ? <Link to={`/products/${item.product_id}`} className="block overflow-hidden rounded-[6px] border border-border"><img src={item.product_image_url} alt="" className="aspect-square w-[4.5rem] object-cover" /></Link> : <div className="hidden sm:block" />}<div><h3 className="m-0 text-base">{item.product_id ? <Link to={`/products/${item.product_id}`}>{item.name}</Link> : item.name}</h3>{item.product_id ? <Link to={`/products/${item.product_id}`} className="mt-1 inline-block text-sm">Към продукта</Link> : null}{item.sku ? <p className="m-0 mt-1 text-sm text-muted-foreground">SKU: {item.sku}</p> : null}{item.options ? <p className="m-0 mt-1 text-sm">{item.options}</p> : null}{item.notes ? <p className="m-0 mt-2 border-l-2 border-border pl-3 text-sm text-muted-foreground">Персонализация: {item.notes}</p> : null}</div><div className="text-right"><p className="m-0 font-bold">{item.qty} × {formatMoney(item.unit_price)}</p><p className="m-0 mt-1 text-sm text-muted-foreground">{formatMoney(item.total)}</p></div></article>)}</div><div className="ml-auto grid max-w-sm gap-2 border-t border-border p-4"><p className="m-0 flex justify-between gap-8"><span>Междинна сума</span><strong>{formatMoney(order.subtotal)}</strong></p><p className="m-0 flex justify-between gap-8"><span>Доставка</span><strong>{formatMoney(order.shipping_amount)}</strong></p><p className="m-0 flex justify-between gap-8"><span>ДДС{order.vat_enabled ? ` (${Number(order.vat_rate)}%)` : ''}</span><strong>{order.vat_enabled ? formatMoney(Number(order.total) - Number(order.total) / (1 + Number(order.vat_rate) / 100)) : 'Не се начислява'}</strong></p><p className="m-0 flex justify-between gap-8 border-t border-border pt-2 text-lg"><span>Общо</span><strong>{formatMoney(order.total)}</strong></p></div></section>
        <aside className="grid content-start gap-4">
          <section className="border border-border bg-card p-4"><h2 className="m-0 mb-3 text-lg">Клиент</h2><p className="m-0 font-bold">{order.first_name} {order.last_name}</p><p className="m-0 mt-2 flex items-center gap-2"><Mail className="size-4" /><a href={`mailto:${order.email}`}>{order.email}</a></p><p className="m-0 mt-2 flex items-center gap-2"><Phone className="size-4" /><a href={`tel:${order.phone}`}>{order.phone}</a></p>{order.user_id ? <p className="m-0 mt-2 text-sm text-muted-foreground">Регистриран клиент · ID {order.user_id}</p> : <p className="m-0 mt-2 text-sm text-muted-foreground">Поръчка като гост</p>}</section>
          <section className="border border-border bg-card p-4"><h2 className="m-0 mb-3 flex items-center gap-2 text-lg"><Truck className="size-5" />Доставка</h2><p className="m-0 font-bold">{deliveryLabel(order.delivery_method)}</p>{order.econt_office_code ? <p className="m-0 mt-2">Код на Econt локация: {order.econt_office_code}</p> : null}<p className="m-0 mt-2">Платец: <strong>{order.shipping_payer === 'sender' ? 'Магазинът' : 'Клиентът'}</strong></p>{order.econt_quote_snapshot ? <div className="mt-3 border-t border-border pt-3 text-sm"><p className="m-0 text-muted-foreground">Econt калкулация · {order.econt_quote_snapshot.environment === 'demo' ? 'demo' : order.econt_quote_snapshot.environment}</p><p className="m-0 mt-1">Куриерска цена: <strong>{formatMoney(order.econt_quote_snapshot.carrier_amount ?? 0)}</strong></p><p className="m-0 mt-1">Подадени: {order.econt_quote_snapshot.weight_kg ?? 0} kg · стойност {formatMoney(order.econt_quote_snapshot.order_value ?? 0)} · НП {formatMoney(order.econt_quote_snapshot.cod_amount ?? 0)}</p></div> : null}<p className="m-0 mt-2 flex items-start gap-2"><MapPin className="mt-1 size-4 shrink-0" /><span>{order.address_line}<br />{[order.postal_code, order.city].filter(Boolean).join(' ')}, {order.country}</span></p>{order.tracking_number && order.tracking_url ? <div className="mt-4 border-t border-border pt-3"><p className="m-0 text-sm text-muted-foreground">Товарителница</p><p className="m-0 mt-1 font-bold">{order.tracking_number}</p><Button asChild variant="outline" size="sm" className="mt-2"><a href={order.tracking_url} target="_blank" rel="noopener noreferrer"><ExternalLink />Проследи в Еконт</a></Button>{order.shipped_at ? <p className="m-0 mt-2 text-xs text-muted-foreground">Изпратена: {formatDateTime(order.shipped_at)}</p> : null}</div> : null}</section>
          <section className="border border-border bg-card p-4"><h2 className="m-0 mb-3 flex items-center gap-2 text-lg"><Package className="size-5" />Плащане</h2><p className="m-0">{paymentLabel(order.payment_method)}</p></section>
          {order.invoice_requested ? <section className="border border-border bg-card p-4"><h2 className="m-0 mb-3 text-lg">Фактура</h2><p className="m-0 font-bold">{order.invoice_company}</p><p className="m-0 mt-1 text-sm text-muted-foreground">ЕИК {order.invoice_eik}{order.invoice_vat_number ? ` · ДДС № ${order.invoice_vat_number}` : ''}<br />{order.invoice_address}<br />МОЛ {order.invoice_mol}</p><div className="mt-3 grid gap-2">{order.invoices.map((invoice) => <Button key={invoice.id} asChild variant="outline"><Link to={`/invoices/${invoice.id}`}>{invoice.type === 'credit_note' ? 'Кредитно известие' : 'Фактура'} № {invoice.number}</Link></Button>)}</div></section> : null}
          {order.notes ? <section className="border border-border bg-card p-4"><h2 className="m-0 mb-2 text-lg">Бележка от клиента</h2><p className="m-0 whitespace-pre-wrap text-muted-foreground">{order.notes}</p></section> : null}
        </aside>
      </div>
    </div>
  );
}
