import { Link } from 'react-router-dom';
import { AlertTriangle, Eye, MoreHorizontal, Pencil, RotateCcw, Shirt, Trash2 } from 'lucide-react';
import type { ProductListItem } from '@/api/products';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { formatMoney } from '@/lib/format';
import { Button } from '@/components/ui/Button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

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

type ProductActions = {
  onDelete: (product: ProductListItem) => void;
  onRestore: (product: ProductListItem) => void;
  onForceDelete: (product: ProductListItem) => void;
};

export function getProductsColumns({ onDelete, onRestore, onForceDelete }: ProductActions) {
  return helper.columns([
    helper.display({
      id: 'image',
      header: 'Снимка',
      enableSorting: false,
      meta: { className: 'w-24', help: 'Предното изображение. Липсваща снимка показва икона.' },
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
              <div className="m-0 mt-1 max-w-md truncate text-muted-foreground" dangerouslySetInnerHTML={{ __html: product.short_description }} />
            ) : null}
            {product.low_stock ? (
              <span
                className="badge warn mt-2 inline-flex items-center gap-1"
                title="Поне един вариант е под минималната наличност от настройките."
              >
                <AlertTriangle className="size-3.5" aria-hidden />
                Ниска наличност
                {product.low_stock_variants_count > 1 ? ` · ${product.low_stock_variants_count} варианта` : ''}
              </span>
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
    helper.accessor((product) => product.category?.name ?? '', {
      id: 'category',
      header: 'Категория',
      sortFn: 'text',
      meta: { help: 'Категорията на продукта. Празно означава без категория.' },
      cell: ({ row }) => row.original.category?.name || '—',
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
    helper.display({
      id: 'actions',
      header: 'Действия',
      enableSorting: false,
      meta: { className: 'w-20 text-right', help: 'Преглед, редакция и управление на изтриването.' },
      cell: ({ row }) => {
        const product = row.original;
        return <DropdownMenu>
          <DropdownMenuTrigger asChild><Button type="button" variant="ghost" size="icon" aria-label="Действия за продукта"><MoreHorizontal /></Button></DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem asChild><Link to={`/products/${product.id}`}><Eye />Преглед</Link></DropdownMenuItem>
            <DropdownMenuItem asChild><Link to={`/products/${product.id}/edit`}><Pencil />Редакция</Link></DropdownMenuItem>
            <DropdownMenuSeparator />
            {product.deleted_at ? <>
              <DropdownMenuItem onSelect={() => onRestore(product)}><RotateCcw />Възстанови</DropdownMenuItem>
              <DropdownMenuItem variant="destructive" onSelect={() => onForceDelete(product)}><Trash2 />Изтрий завинаги</DropdownMenuItem>
            </> : <DropdownMenuItem variant="destructive" onSelect={() => onDelete(product)}><Trash2 />Изтрий</DropdownMenuItem>}
          </DropdownMenuContent>
        </DropdownMenu>;
      },
    }),
  ]);
}
