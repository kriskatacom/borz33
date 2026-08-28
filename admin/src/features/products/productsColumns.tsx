import { Link } from 'react-router-dom';
import { Shirt } from 'lucide-react';
import type { ProductListItem } from '@/api/products';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { formatMoney } from '@/lib/format';

const helper = createDataTableHelper<ProductListItem>();

function ProductThumb({ product }: { product: ProductListItem }) {
  const url = product.front_image?.url;
  const alt = product.front_image?.alt || product.name;

  return (
    <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-[6px] border border-border bg-muted">
      {url ? (
        <img src={url} alt={alt} className="size-full object-cover" />
      ) : (
        <Shirt className="size-5 text-muted-foreground" aria-hidden />
      )}
    </div>
  );
}

export function getProductsColumns() {
  return helper.columns([
    helper.display({
      id: 'image',
      header: '',
      enableSorting: false,
      meta: { className: 'w-16' },
      cell: ({ row }) => <ProductThumb product={row.original} />,
    }),
    helper.accessor('name', {
      header: 'Продукт',
      sortFn: 'text',
      meta: { sticky: true, help: 'Име и кратък текст. Отворете реда за пълен преглед.' },
      cell: ({ row }) => {
        const product = row.original;

        return (
          <div className="min-w-48">
            <Link to={`/products/${product.id}`} className="font-bold text-foreground no-underline hover:underline">
              {product.name}
            </Link>
            {product.short_description ? (
              <p className="m-0 mt-1 max-w-md truncate text-muted-foreground">{product.short_description}</p>
            ) : null}
          </div>
        );
      },
    }),
    helper.accessor((product) => product.sku ?? '', {
      id: 'sku',
      header: 'SKU',
      sortFn: 'text',
      meta: { help: 'Базов артикулен номер на продукта.' },
      cell: ({ row }) => row.original.sku || '—',
    }),
    helper.accessor((product) => Number(product.price), {
      id: 'price',
      header: 'Цена',
      sortFn: 'alphanumeric',
      meta: { help: 'Базова цена „от“. Вариантите могат да имат различна цена.' },
      cell: ({ row }) => formatMoney(row.original.price),
    }),
    helper.accessor('variants_count', {
      header: 'Варианти',
      sortFn: 'alphanumeric',
      meta: { help: 'Брой комбинации от размер, цвят и други опции.' },
    }),
    helper.accessor(
      (product) => (product.deleted_at ? 'Изтрит' : product.is_active ? 'Активен' : 'Неактивен'),
      {
        id: 'status',
        header: 'Статус',
        sortFn: 'text',
        meta: { help: 'Активен се вижда в каталога. Неактивен е скрит. Изтрит може да се възстанови по-късно.' },
        cell: ({ row }) => {
          const product = row.original;
          const label = product.deleted_at ? 'Изтрит' : product.is_active ? 'Активен' : 'Неактивен';

          return (
            <span className={`badge ${product.deleted_at ? 'warn' : product.is_active ? 'ok' : 'idle'}`}>
              {label}
            </span>
          );
        },
      }
    ),
    helper.accessor(
      (product) => (product.personalization_enabled ? 'Да' : 'Не'),
      {
        id: 'personalization',
        header: 'Персонализация',
        sortFn: 'text',
        meta: { help: 'Дали клиентът може да въведе текст преди добавяне в количката.' },
        cell: ({ row }) =>
          row.original.personalization_enabled ? <span className="badge info">Да</span> : '—',
      }
    ),
  ]);
}
