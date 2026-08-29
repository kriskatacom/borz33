import { Link } from 'react-router-dom';
import { FileText, MoreHorizontal, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import type { PageListItem } from '@/api/pages';
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
import { pageTreePrefix } from '@/features/pages/pageTree';

const helper = createDataTableHelper<PageListItem>();

type PagesColumnsOptions = {
  onRestore: (page: PageListItem) => void;
  onDelete: (page: PageListItem) => void;
  depthById?: Record<number, number>;
};

function PagesRowActions({
  page,
  onRestore,
  onDelete,
}: {
  page: PageListItem;
  onRestore: (page: PageListItem) => void;
  onDelete: (page: PageListItem) => void;
}) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button type="button" variant="ghost" size="icon" aria-label="Още опции">
          <MoreHorizontal />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {page.deleted_at ? (
          <DropdownMenuItem onSelect={() => onRestore(page)}>
            <RotateCcw />
            Възстанови
          </DropdownMenuItem>
        ) : (
          <>
            <DropdownMenuItem asChild>
              <Link to={`/pages/${page.id}`}>
                <Pencil />
                Редакция
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant="destructive" onSelect={() => onDelete(page)}>
              <Trash2 />
              Изтрий
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export function getPagesColumns({ onRestore, onDelete, depthById = {} }: PagesColumnsOptions) {
  return helper.columns([
    helper.accessor('title', {
      header: 'Страница',
      sortFn: 'text',
      meta: { sticky: true, help: 'Заглавие и адрес. Децата са с дълги тирета според нивото.' },
      cell: ({ row }) => {
        const page = row.original;
        const prefix = pageTreePrefix(depthById[page.id] ?? 0);
        const title = (
          <span className="whitespace-pre">
            {prefix}
            {page.title}
          </span>
        );

        return (
          <div className="min-w-48">
            {page.deleted_at ? (
              <p className="m-0 font-bold text-foreground">{title}</p>
            ) : (
              <Link to={`/pages/${page.id}`} className="font-bold text-foreground no-underline hover:underline">
                {title}
              </Link>
            )}
            <p className="m-0 mt-1 flex items-center gap-1.5 text-muted-foreground">
              <FileText className="size-3.5 shrink-0" aria-hidden />
              /{page.slug}
            </p>
          </div>
        );
      },
    }),
    helper.accessor((page) => page.parent?.title ?? '', {
      id: 'parent',
      header: 'Родител',
      sortFn: 'text',
      meta: { help: 'Родителската страница. Празно означава страница на първо ниво.' },
      cell: ({ row }) => row.original.parent?.title || '—',
    }),
    helper.accessor('fields_count', {
      header: 'Полета',
      sortFn: 'alphanumeric',
      meta: { help: 'Брой персонални полета към страницата.' },
    }),
    helper.accessor(
      (page) => (page.deleted_at ? 'Изтрита' : page.is_active ? 'Активна' : 'Неактивна'),
      {
        id: 'status',
        header: 'Статус',
        sortFn: 'text',
        meta: {
          help: 'Активна може да се показва в сайта. Неактивна е скрита. Изтрита може да се възстанови.',
        },
        cell: ({ row }) => {
          const page = row.original;
          const label = page.deleted_at ? 'Изтрита' : page.is_active ? 'Активна' : 'Неактивна';

          return (
            <span className={`badge ${page.deleted_at ? 'warn' : page.is_active ? 'ok' : 'idle'}`}>
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
    helper.accessor((page) => page.updated_at ?? '', {
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
        help: 'Редакция, изтриване или възстановяване на страницата.',
      },
      cell: ({ row }) => (
        <div className="flex justify-end">
          <PagesRowActions page={row.original} onRestore={onRestore} onDelete={onDelete} />
        </div>
      ),
    }),
  ]);
}
