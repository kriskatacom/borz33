import { Link } from 'react-router-dom';
import { Eye } from 'lucide-react';
import type { OrderListItem } from '@/api/orders';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { Button } from '@/components/ui/Button';
import { deliveryLabel, OrderStatusBadge } from '@/features/orders/orderFormat';
import { formatDateTime, formatMoney } from '@/lib/format';

const helper = createDataTableHelper<OrderListItem>();

export function getOrdersColumns() {
  return helper.columns([
    helper.accessor('number', { header: 'Поръчка', meta: { sticky: true }, cell: ({ row }) => <Link className="font-bold text-foreground no-underline hover:underline" to={`/orders/${row.original.id}`}>#{row.original.number}</Link> }),
    helper.accessor('customer_name', { header: 'Клиент', cell: ({ row }) => <div><p className="m-0 font-medium">{row.original.customer_name}</p><p className="m-0 text-sm text-muted-foreground">{row.original.email}</p></div> }),
    helper.accessor('status', { header: 'Статус', cell: ({ getValue }) => <OrderStatusBadge status={getValue()} /> }),
    helper.accessor('items_count', { header: 'Артикули', meta: { className: 'text-right' } }),
    helper.accessor('delivery_method', { header: 'Доставка', cell: ({ getValue }) => deliveryLabel(getValue()) }),
    helper.accessor('total', { header: 'Общо', meta: { className: 'text-right' }, cell: ({ getValue }) => <strong>{formatMoney(getValue())}</strong> }),
    helper.accessor('created_at', { header: 'Получена', cell: ({ getValue }) => formatDateTime(getValue()) }),
    helper.display({ id: 'actions', header: '', meta: { className: 'w-16 text-right' }, cell: ({ row }) => <Button asChild size="icon" variant="ghost" aria-label="Преглед на поръчката"><Link to={`/orders/${row.original.id}`}><Eye /></Link></Button> }),
  ]);
}
