import { Link } from 'react-router-dom';
import { FolderTree, MoreHorizontal, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import type { CategoryListItem } from '@/api/categories';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { Button } from '@/components/ui/Button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDateTime, formatRelativeTime } from '@/lib/format';
import { categoryTreePrefix } from '@/features/categories/categoryTree';

const helper = createDataTableHelper<CategoryListItem>();

type CategoriesColumnsOptions = {
  onRestore: (category: CategoryListItem) => void;
  onDelete: (category: CategoryListItem) => void;
  depthById?: Record<number, number>;
};

function CategoryThumb({ category }: { category: CategoryListItem }) {
  const url = category.media?.url;
  const alt = category.media?.alt || category.name;

  return (
    <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-[6px] border border-border bg-muted">
      {url ? (
        <img src={url} alt={alt} className="size-full object-cover" />
      ) : (
        <FolderTree className="size-5 text-muted-foreground" aria-hidden />
      )}
    </div>
  );
}

function CategoriesRowActions({
  category,
  onRestore,
  onDelete,
}: {
  category: CategoryListItem;
  onRestore: (category: CategoryListItem) => void;
  onDelete: (category: CategoryListItem) => void;
}) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button type="button" variant="ghost" size="icon" aria-label="Още опции">
          <MoreHorizontal />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {category.deleted_at ? (
          <DropdownMenuItem onSelect={() => onRestore(category)}>
            <RotateCcw />
            Възстанови
          </DropdownMenuItem>
        ) : (
          <>
            <DropdownMenuItem asChild>
              <Link to={`/categories/${category.id}`}>
                <Pencil />
                Редакция
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant="destructive" onSelect={() => onDelete(category)}>
              <Trash2 />
              Изтрий
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export function getCategoriesColumns({ onRestore, onDelete, depthById = {} }: CategoriesColumnsOptions) {
  return helper.columns([
    helper.display({
      id: 'image',
      header: 'Снимка',
      enableSorting: false,
      meta: { className: 'w-24', help: 'Изображение на категорията. Ако няма, се показва икона.' },
      cell: ({ row }) => <CategoryThumb category={row.original} />,
    }),
    helper.accessor('name', {
      header: 'Категория',
      sortFn: 'text',
      meta: { sticky: true, help: 'Име и адрес. Децата са с дълги тирета според нивото.' },
      cell: ({ row }) => {
        const category = row.original;
        const prefix = categoryTreePrefix(depthById[category.id] ?? 0);
        const name = (
          <span className="whitespace-pre">
            {prefix}
            {category.name}
          </span>
        );

        return (
          <div className="min-w-48">
            {category.deleted_at ? (
              <p className="m-0 font-bold text-foreground">{name}</p>
            ) : (
              <Link to={`/categories/${category.id}`} className="font-bold text-foreground no-underline hover:underline">
                {name}
              </Link>
            )}
            <p className="m-0 mt-1 flex items-center gap-1.5 text-muted-foreground">
              <FolderTree className="size-3.5 shrink-0" aria-hidden />
              /{category.slug}
            </p>
          </div>
        );
      },
    }),
    helper.accessor((category) => category.parent?.name ?? '', {
      id: 'parent',
      header: 'Родител',
      sortFn: 'text',
      meta: { help: 'Родителската категория. Празно означава категория на първо ниво.' },
      cell: ({ row }) => row.original.parent?.name || '—',
    }),
    helper.accessor('products_count', {
      header: 'Продукти',
      sortFn: 'alphanumeric',
      meta: { help: 'Брой продукти в тази категория.' },
    }),
    helper.accessor(
      (category) => (category.deleted_at ? 'Изтрита' : category.is_active ? 'Активна' : 'Неактивна'),
      {
        id: 'status',
        header: 'Статус',
        sortFn: 'text',
        meta: {
          help: 'Активна може да се показва в каталога. Неактивна е скрита. Изтрита може да се възстанови.',
        },
        cell: ({ row }) => {
          const category = row.original;
          const label = category.deleted_at ? 'Изтрита' : category.is_active ? 'Активна' : 'Неактивна';

          return (
            <span className={`badge ${category.deleted_at ? 'warn' : category.is_active ? 'ok' : 'idle'}`}>
              {label}
            </span>
          );
        },
      }
    ),
    helper.accessor('sort_order', {
      header: 'Ред',
      sortFn: 'alphanumeric',
      meta: { help: 'По-малък номер е по-нагоре в списъка.' },
    }),
    helper.accessor((category) => category.updated_at ?? '', {
      id: 'updated',
      header: 'Обновена',
      sortFn: 'text',
      meta: { help: 'Последна промяна. Точната дата се вижда при посочване.' },
      cell: ({ row }) => {
        const value = row.original.updated_at;

        return <span title={value ? formatDateTime(value) : undefined}>{formatRelativeTime(value)}</span>;
      },
    }),
    helper.display({
      id: 'actions',
      header: 'Действия',
      enableSorting: false,
      meta: {
        className: 'text-right',
        help: 'Редакция, изтриване или възстановяване на категорията.',
      },
      cell: ({ row }) => (
        <div className="flex justify-end">
          <CategoriesRowActions category={row.original} onRestore={onRestore} onDelete={onDelete} />
        </div>
      ),
    }),
  ]);
}
