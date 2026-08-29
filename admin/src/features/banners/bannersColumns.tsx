import { Link } from 'react-router-dom';
import { Image, MoreHorizontal, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import type { BannerListItem } from '@/api/banners';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { Button } from '@/components/ui/Button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { bannerLayoutLabel } from '@/features/banners/bannerLayouts';
import { formatDateTime, formatRelativeTime } from '@/lib/format';

const helper = createDataTableHelper<BannerListItem>();

type BannersColumnsOptions = {
  onRestore: (banner: BannerListItem) => void;
  onDelete: (banner: BannerListItem) => void;
};

function BannerThumb({ banner }: { banner: BannerListItem }) {
  const url = banner.media?.url;
  const alt = banner.media?.alt || banner.title;

  return (
    <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-[6px] border border-border bg-muted">
      {url ? (
        <img src={url} alt={alt} className="size-full object-cover" />
      ) : (
        <Image className="size-5 text-muted-foreground" aria-hidden />
      )}
    </div>
  );
}

function BannersRowActions({
  banner,
  onRestore,
  onDelete,
}: {
  banner: BannerListItem;
  onRestore: (banner: BannerListItem) => void;
  onDelete: (banner: BannerListItem) => void;
}) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button type="button" variant="ghost" size="icon" aria-label="Още опции">
          <MoreHorizontal />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {banner.deleted_at ? (
          <DropdownMenuItem onSelect={() => onRestore(banner)}>
            <RotateCcw />
            Възстанови
          </DropdownMenuItem>
        ) : (
          <>
            <DropdownMenuItem asChild>
              <Link to={`/banners/${banner.id}`}>
                <Pencil />
                Редакция
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant="destructive" onSelect={() => onDelete(banner)}>
              <Trash2 />
              Изтрий
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export function getBannersColumns({ onRestore, onDelete }: BannersColumnsOptions) {
  return helper.columns([
    helper.display({
      id: 'image',
      header: 'Снимка',
      enableSorting: false,
      meta: { className: 'w-24', help: 'Изображението на банера.' },
      cell: ({ row }) => <BannerThumb banner={row.original} />,
    }),
    helper.accessor('title', {
      header: 'Банер',
      sortFn: 'text',
      meta: { sticky: true, help: 'Заглавие и адрес за вграждане в сайта.' },
      cell: ({ row }) => {
        const banner = row.original;

        return (
          <div className="min-w-48">
            {banner.deleted_at ? (
              <p className="m-0 font-bold text-foreground">{banner.title}</p>
            ) : (
              <Link to={`/banners/${banner.id}`} className="font-bold text-foreground no-underline hover:underline">
                {banner.title}
              </Link>
            )}
            <p className="m-0 mt-1 flex items-center gap-1.5 text-muted-foreground">
              <Image className="size-3.5 shrink-0" aria-hidden />
              {banner.slug}
            </p>
          </div>
        );
      },
    }),
    helper.accessor('buttons_count', {
      header: 'Бутони',
      sortFn: 'alphanumeric',
      meta: { help: 'Брой бутони към банера. Нужен е поне един.' },
    }),
    helper.accessor((banner) => bannerLayoutLabel(banner.layout), {
      id: 'layout',
      header: 'Дизайн',
      sortFn: 'text',
      meta: { help: 'Оформлението, с което банерът се показва в сайта.' },
      cell: ({ row }) => bannerLayoutLabel(row.original.layout),
    }),
    helper.accessor(
      (banner) => (banner.deleted_at ? 'Изтрит' : banner.is_active ? 'Активен' : 'Неактивен'),
      {
        id: 'status',
        header: 'Статус',
        sortFn: 'text',
        meta: {
          help: 'Активен се показва в сайта. Неактивен е скрит. Изтрит може да се възстанови.',
        },
        cell: ({ row }) => {
          const banner = row.original;
          const label = banner.deleted_at ? 'Изтрит' : banner.is_active ? 'Активен' : 'Неактивен';

          return (
            <span className={`badge ${banner.deleted_at ? 'warn' : banner.is_active ? 'ok' : 'idle'}`}>
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
    helper.accessor((banner) => banner.updated_at ?? '', {
      id: 'updated',
      header: 'Обновен',
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
        help: 'Редакция, изтриване или възстановяване на банера.',
      },
      cell: ({ row }) => (
        <div className="flex justify-end">
          <BannersRowActions banner={row.original} onRestore={onRestore} onDelete={onDelete} />
        </div>
      ),
    }),
  ]);
}
