import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Download, FileMinus, XCircle } from 'lucide-react';
import { cancelInvoice, createCreditNote, downloadInvoice, getInvoice, type Invoice } from '@/api/invoices';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Field } from '@/components/ui/Field';
import { formatMoney } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';

const invoiceStatusLabels = { draft: 'Чернова', issued: 'Издадена', cancelled: 'Анулирана', credited: 'Кредитирана' } as const;
const creditNoteStatusLabels = { draft: 'Чернова', issued: 'Издадено', cancelled: 'Анулирано', credited: 'Кредитирано' } as const;

export function InvoiceDetailsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const id = Number(useParams().id);
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [reason, setReason] = useState('');
  const [selected, setSelected] = useState<Record<number, number>>({});
  const [refundShipping, setRefundShipping] = useState(false);
  const [confirmCancel, setConfirmCancel] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => { void getInvoice(token, id).then((response) => setInvoice(response.data.invoice)).catch((error) => toastError(error, 'Фактурата не можа да се зареди.')); }, [token, id]);

  const preview = useMemo(() => {
    if (!invoice) return { net: 0, tax: 0, gross: 0 };
    let net = 0; let tax = 0; let gross = 0;
    Object.entries(selected).forEach(([rawIndex, qty]) => {
      const item = invoice.items[Number(rawIndex)];
      if (!item || qty < 1) return;
      const lineGross = Number(item.unit_gross) * qty;
      const lineNet = lineGross / (1 + Number(item.tax_rate) / 100);
      gross += lineGross; net += lineNet; tax += lineGross - lineNet;
    });
    if (refundShipping) {
      const shippingNet = Number(invoice.shipping_net);
      const shippingTax = shippingNet * Number(invoice.seller.vat_rate ?? 0) / 100;
      net += shippingNet; tax += shippingTax; gross += shippingNet + shippingTax;
    }
    return { net: -net, tax: -tax, gross: -gross };
  }, [invoice, refundShipping, selected]);

  if (!invoice) return <div className="page" aria-busy="true" />;
  const invoiceId = invoice.id;

  function remainingQty(index: number) { return invoice?.creditable_items.find((item) => item.index === index)?.remaining_qty ?? 0; }
  function toggleItem(index: number, checked: boolean) { setSelected((current) => { const next = { ...current }; if (checked) next[index] = 1; else delete next[index]; return next; }); }
  function setQty(index: number, qty: number) { setSelected((current) => ({ ...current, [index]: Math.min(remainingQty(index), Math.max(1, qty || 1)) })); }

  async function credit() {
    setBusy(true);
    try {
      const items = Object.entries(selected).map(([index, qty]) => ({ index: Number(index), qty }));
      await createCreditNote(token, invoiceId, reason, items, refundShipping);
      setInvoice((await getInvoice(token, id)).data.invoice);
      setReason(''); setSelected({}); setRefundShipping(false);
      toast.success('Кредитното известие е издадено за избраните позиции.');
    } catch (error) { toastError(error, 'Кредитното известие не можа да бъде издадено.'); }
    finally { setBusy(false); }
  }

  async function cancel() {
    setBusy(true);
    try { setInvoice((await cancelInvoice(token, invoiceId, reason)).data.invoice); setConfirmCancel(false); toast.success('Документът е анулиран.'); }
    catch (error) { toastError(error, 'Документът не можа да бъде анулиран.'); }
    finally { setBusy(false); }
  }

  const canCredit = invoice.type === 'invoice' && invoice.status === 'issued';
  const hasSelection = Object.keys(selected).length > 0 || refundShipping;
  const isCreditNote = invoice.type === 'credit_note';
  const documentLabel = isCreditNote ? 'Кредитно известие' : 'Фактура';
  const listLabel = isCreditNote ? 'Кредитни известия' : 'Фактури';
  const listRoute = isCreditNote ? routes.creditNotes : routes.invoices;

  return <div className="page">
    <PageHeader title={`${documentLabel} ${invoice.number ?? '—'}`} help={`Към поръчка #${invoice.order_number} · ${invoice.issue_date ?? 'чернова'}`} crumbs={[{ label: 'Табло', to: routes.home }, { label: listLabel, to: listRoute }, { label: invoice.number ?? 'Чернова' }]} actions={<><Button asChild variant="outline"><Link to={listRoute}><ArrowLeft />Назад</Link></Button>{invoice.has_pdf ? <Button onClick={() => void downloadInvoice(token, invoice.id, `${invoice.type}-${invoice.number}.pdf`).catch((error) => toastError(error, 'Файлът не можа да бъде изтеглен.'))}><Download />PDF</Button> : null}</>} />
    <div className="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(20rem,1fr)]">
      <section className="border border-border bg-card">
        <div className="grid gap-4 border-b border-border p-4 md:grid-cols-2"><div><p className="m-0 text-sm text-muted-foreground">Доставчик</p><strong>{invoice.seller.company}</strong><p className="m-0 mt-1">ЕИК {invoice.seller.eik}<br />{invoice.seller.address}</p></div><div><p className="m-0 text-sm text-muted-foreground">Получател</p><strong>{invoice.buyer.company}</strong><p className="m-0 mt-1">ЕИК {invoice.buyer.eik}<br />{invoice.buyer.vat_number ? `ДДС № ${invoice.buyer.vat_number}` : null}<br />{invoice.buyer.address}<br />МОЛ {invoice.buyer.mol}</p></div></div>
        <div className="overflow-x-auto"><table className="w-full border-collapse"><thead><tr className="border-b border-border bg-muted/40 text-left"><th className="p-3">Артикул</th><th className="p-3 text-right">Кол.</th><th className="p-3 text-right">Основа</th><th className="p-3 text-right">ДДС</th><th className="p-3 text-right">Общо</th></tr></thead><tbody>{invoice.items.map((item, index) => <tr key={`${item.sku}-${index}`} className="border-b border-border"><td className="p-3"><strong>{item.name}</strong><div className="text-sm text-muted-foreground">{item.sku}</div></td><td className="p-3 text-right">{item.qty}</td><td className="p-3 text-right">{formatMoney(item.net_total)}</td><td className="p-3 text-right">{item.tax_rate}% · {formatMoney(item.tax)}</td><td className="p-3 text-right font-bold">{formatMoney(item.gross_total)}</td></tr>)}</tbody></table></div>
        <div className="ml-auto grid max-w-sm gap-2 p-4"><p className="m-0 flex justify-between"><span>Данъчна основа</span><strong>{formatMoney(Number(invoice.subtotal_net) + Number(invoice.shipping_net) - Number(invoice.discount_net))}</strong></p><p className="m-0 flex justify-between"><span>ДДС</span><strong>{formatMoney(invoice.tax_amount)}</strong></p><p className="m-0 flex justify-between border-t border-border pt-2 text-lg"><span>Общо</span><strong>{formatMoney(invoice.total_gross)}</strong></p></div>
      </section>
      <aside className="grid content-start gap-4">
        <section className="border border-border bg-card p-4"><h2 className="m-0 text-lg">Архивен статус</h2><p className="mt-2 font-bold">{(invoice.type === 'credit_note' ? creditNoteStatusLabels : invoiceStatusLabels)[invoice.status]}</p><p className="text-sm text-muted-foreground">След издаване данните и сумите не могат да се редактират.</p></section>
        {canCredit ? <section className="border border-border bg-card p-4"><h2 className="m-0 mb-1 flex items-center gap-2 text-lg"><FileMinus className="size-5" />Кредитно известие</h2><p className="mt-0 text-sm text-muted-foreground">Изберете конкретните върнати позиции и количество.</p><div className="grid gap-2">{invoice.items.map((item, index) => { const remaining = remainingQty(index); const checked = selected[index] !== undefined; return <div key={`${item.sku}-${index}`} className={`border p-3 ${checked ? 'border-primary bg-primary/5' : 'border-border'}`}><label className="flex cursor-pointer items-start gap-3"><input className="mt-1 size-4" type="checkbox" checked={checked} disabled={remaining < 1} onChange={(event) => toggleItem(index, event.target.checked)} /><span className="min-w-0 flex-1"><strong className="block">{item.name}</strong><small className="text-muted-foreground">{formatMoney(item.unit_gross)} · налични за кредитиране: {remaining}</small></span></label>{checked ? <label className="mt-3 flex items-center justify-between gap-3 text-sm"><span>Количество</span><input className="h-10 w-20 border border-input bg-background px-2 text-right" type="number" min="1" max={remaining} value={selected[index]} onChange={(event) => setQty(index, Number(event.target.value))} /></label> : null}</div>; })}</div>{invoice.shipping_creditable ? <label className={`mt-3 flex cursor-pointer items-start gap-3 border p-3 ${refundShipping ? 'border-primary bg-primary/5' : 'border-border'}`}><input className="mt-1 size-4" type="checkbox" checked={refundShipping} onChange={(event) => setRefundShipping(event.target.checked)} /><span><strong className="block">Възстанови доставката</strong><small className="text-muted-foreground">Добавя доставката като отделен ред. Не се избира автоматично.</small></span></label> : null}<div className="my-3 grid gap-1 border-y border-border py-3 text-sm"><p className="m-0 flex justify-between"><span>Основа</span><strong>{formatMoney(preview.net)}</strong></p><p className="m-0 flex justify-between"><span>ДДС</span><strong>{formatMoney(preview.tax)}</strong></p><p className="m-0 flex justify-between text-base"><span>Общо</span><strong>{formatMoney(preview.gross)}</strong></p></div><Field id="credit-reason" label="Основание" value={reason} onChange={(event) => setReason(event.target.value)} placeholder="Връщане или възстановяване" /><Button className="mt-3 w-full" disabled={busy || reason.trim().length < 3 || !hasSelection} onClick={() => void credit()}><FileMinus />Издай за избраните позиции</Button></section> : null}
        {['draft', 'issued'].includes(invoice.status) ? <section className="border border-border bg-card p-4"><h2 className="m-0 mb-2 text-lg">Анулиране</h2><p className="m-0 mb-3 text-sm text-muted-foreground">{isCreditNote ? 'Анулирането е необратимо и документът няма да може да бъде използван повторно.' : 'Анулирането е необратимо и фактурата няма да може да бъде използвана повторно.'}</p><Button variant="outline" className="w-full" disabled={busy} onClick={() => setConfirmCancel(true)}><XCircle />Анулирай документа</Button></section> : null}
        {!isCreditNote && invoice.credit_notes.length ? <section className="border border-border bg-card p-4"><h2 className="m-0 mb-2 text-lg">Кредитни известия</h2>{invoice.credit_notes.map((credit) => <Link key={credit.id} className="block py-2" to={`/credit-notes/${credit.id}`}>№ {credit.number} · {formatMoney(credit.total_gross)}</Link>)}</section> : null}
      </aside>
    </div>
    {confirmCancel ? <ConfirmDialog title={`Анулиране на ${documentLabel.toLowerCase()}`} message={`Сигурни ли сте, че искате да анулирате ${documentLabel.toLowerCase()} № ${invoice.number ?? '—'}?`} description="Документът ще бъде отбелязан като анулиран и няма да може да бъде използван повторно. Няма да участва в счетоводните справки и няма да променя приходите или разходите." confirmLabel="Анулирай документа" busy={busy} onCancel={() => setConfirmCancel(false)} onConfirm={() => void cancel()} /> : null}
  </div>;
}
