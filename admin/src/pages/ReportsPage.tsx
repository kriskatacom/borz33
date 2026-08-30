import { useEffect, useState } from 'react';
import { BarChart3, CalendarDays, PackageCheck, RefreshCw, ShoppingBag, Truck } from 'lucide-react';
import { generateReport, listReports, type MonthlyRevenueReport } from '@/api/reports';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { MonthPicker } from '@/components/ui/MonthPicker';
import { formatDateTime, formatMoney } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';

const monthFormatter = new Intl.DateTimeFormat('bg-BG', { month: 'long', year: 'numeric' });
const statusLabels: Record<string, string> = { pending: 'Чакащи', confirmed: 'Потвърдени', processing: 'Обработват се', shipped: 'Изпратени', delivered: 'Доставени', cancelled: 'Отказани' };
function monthLabel(report: MonthlyRevenueReport) { return monthFormatter.format(new Date(report.year, report.month - 1, 1)); }
function currentPeriod() { const now = new Date(); return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`; }

export function ReportsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [reports, setReports] = useState<MonthlyRevenueReport[]>([]);
  const [period, setPeriod] = useState(currentPeriod());
  const [busy, setBusy] = useState(true);
  const [generating, setGenerating] = useState(false);
  useGlobalLoading(busy);

  useEffect(() => { let cancelled = false; setBusy(true); void listReports(token).then((response) => { if (!cancelled) setReports(response.data.reports); }).catch((error) => { if (!cancelled) toastError(error, 'Отчетите не можаха да се заредят.'); }).finally(() => { if (!cancelled) setBusy(false); }); return () => { cancelled = true; }; }, [token]);

  async function generate() {
    if (!period) return;
    setGenerating(true);
    try {
      const response = await generateReport(token, period);
      setReports((current) => [response.data.report, ...current.filter((item) => item.id !== response.data.report.id)].sort((a, b) => b.year - a.year || b.month - a.month));
      toast.success(response.message || 'Отчетът е генериран.');
    } catch (error) { toastError(error, 'Отчетът не можа да се генерира.'); }
    finally { setGenerating(false); }
  }

  return <div className="page">
    <PageHeader title="Отчети" help="Запазени месечни отчети за поръчките и признатия приход на магазина." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Отчети' }]} />
    <section className="report-generator" aria-labelledby="report-generator-title"><div><span><BarChart3 /></span><div><h2 id="report-generator-title">Генериране на месечен отчет</h2><p>Приходът включва доставените поръчки, създадени през избрания месец. Повторното генериране обновява съществуващия отчет.</p></div></div><div><label htmlFor="report-period">Месец</label><MonthPicker id="report-period" value={period} max={currentPeriod()} onChange={setPeriod} /><Button type="button" disabled={generating || !period} onClick={() => void generate()}><RefreshCw className={generating ? 'animate-spin' : ''} />{generating ? 'Генериране…' : 'Генерирай отчет'}</Button></div></section>
    {reports.length === 0 && !busy ? <div className="report-empty"><BarChart3 /><h2>Все още няма генерирани отчети</h2><p>Изберете месец и създайте първия отчет.</p></div> : <div className="report-list">{reports.map((report) => <article className="report-card" key={report.id}><header><div><p>Месечен отчет</p><h2>{monthLabel(report)}</h2><small>Генериран от {report.generated_by} · {formatDateTime(report.generated_at)}</small></div><strong>{formatMoney(report.recognized_revenue)}</strong></header><div className="report-metrics"><div><span><ShoppingBag />Признат приход</span><strong>{formatMoney(report.recognized_revenue)}</strong></div><div><span><PackageCheck />Доставени поръчки</span><strong>{report.delivered_orders_count}</strong></div><div><span><CalendarDays />Средна поръчка</span><strong>{formatMoney(report.average_order_value)}</strong></div><div><span><Truck />Приход от доставка</span><strong>{formatMoney(report.shipping_revenue)}</strong></div></div><details><summary>Пълен отчет</summary><div className="report-details"><section><h3>Обобщение</h3><dl><div><dt>Всички поръчки</dt><dd>{report.orders_count}</dd></div><div><dt>Отказани поръчки</dt><dd>{report.cancelled_orders_count}</dd></div><div><dt>Оборот без отказаните</dt><dd>{formatMoney(report.gross_turnover)}</dd></div><div><dt>Приход от продукти</dt><dd>{formatMoney(report.product_revenue)}</dd></div><div><dt>Продадени артикули</dt><dd>{report.items_sold}</dd></div></dl></section><section><h3>Поръчки по статус</h3><dl>{Object.entries(report.status_breakdown).map(([status, metric]) => <div key={status}><dt>{statusLabels[status] ?? status} ({metric.count})</dt><dd>{formatMoney(metric.total)}</dd></div>)}</dl></section><section className="report-top-products"><h3>Водещи продукти</h3>{report.top_products.length === 0 ? <p>Няма доставени продукти за периода.</p> : <ol>{report.top_products.map((product, index) => <li key={`${product.name}-${product.sku}-${index}`}><span><strong>{product.name}</strong><small>{product.sku || 'Без SKU'} · {product.qty} бр.</small></span><b>{formatMoney(product.revenue)}</b></li>)}</ol>}</section></div></details></article>)}</div>}
  </div>;
}
