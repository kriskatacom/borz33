import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ImagePlus } from 'lucide-react';
import { ApiError } from '@/api/client';
import { deleteBanner, listBanners, restoreBanner, type BannerListItem } from '@/api/banners';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { DataTable, DATA_TABLE_PAGE_SIZES, DEFAULT_PAGE_SIZE } from '@/components/data-table/DataTable';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { getBannersColumns } from '@/features/banners/bannersColumns';
import { toast, toastError } from '@/lib/toast';

function parsePageSize(raw: string | null): number {
  const value = Number(raw);

  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
}

export function BannersPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [banners, setBanners] = useState<BannerListItem[]>([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [pending, setPending] = useState<BannerListItem | null>(null);
  const [acting, setActing] = useState(false);
  useGlobalLoading(busy);

  const filters = useMemo(
    () => ({
      q: params.get('q') ?? '',
      status: params.get('status') ?? 'all',
      page: Number(params.get('page') ?? '1') || 1,
      per_page: parsePageSize(params.get('per_page')),
    }),
    [params]
  );

  const columns = useMemo(
    () =>
      getBannersColumns({
        onRestore: setPending,
        onDelete: setPending,
      }),
    []
  );

  function updateParams(next: Record<string, string>, resetPage = true) {
    const merged = new URLSearchParams(params);

    for (const [key, value] of Object.entries(next)) {
      if (value) {
        merged.set(key, value);
      } else {
        merged.delete(key);
      }
    }

    if (resetPage) {
      merged.delete('page');
    }

    setParams(merged);
  }

  useEffect(() => {
    const handle = window.setTimeout(() => {
      if (search !== (params.get('q') ?? '')) {
        updateParams({ q: search });
      }
    }, 300);

    return () => window.clearTimeout(handle);
  }, [search, params]);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      setBusy(true);
      setMessage(null);

      try {
        const response = await listBanners(token, filters);
        if (cancelled) {
          return;
        }
        setBanners(response.data.banners);
        setTotal(response.data.pagination.total);
        setLastPage(response.data.pagination.last_page);
      } catch (error) {
        if (!cancelled) {
          const text = error instanceof ApiError ? error.message : 'Списъкът не можа да се зареди.';
          setMessage(text);
          toast.error(text);
          setBanners([]);
        }
      } finally {
        if (!cancelled) {
          setBusy(false);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [filters, token]);

  async function confirmPending() {
    if (!pending) {
      return;
    }

    setActing(true);

    try {
      if (pending.deleted_at) {
        const response = await restoreBanner(token, pending.id);
        toast.success(response.message || 'Банерът е възстановен.');
      } else {
        const response = await deleteBanner(token, pending.id);
        toast.success(response.message || 'Банерът е изтрит.');
      }
      setPending(null);
      const response = await listBanners(token, filters);
      setBanners(response.data.banners);
      setTotal(response.data.pagination.total);
      setLastPage(response.data.pagination.last_page);
    } catch (error) {
      toastError(error, 'Действието не беше успешно.');
    } finally {
      setActing(false);
    }
  }

  return (
    <div className="page">
      <PageHeader
        title="Банери"
        help="Блокове с текст, изображение и поне един бутон. Вграждате ги в сайта чрез адреса (slug)."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Банери' },
        ]}
        actions={
          <Button asChild>
            <Link to={routes.bannersNew}>
              <ImagePlus />
              Нов банер
            </Link>
          </Button>
        }
      />

      <form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field
          id="q"
          label="Търсене"
          help="Търси по заглавие, адрес или текст. Резултатите се обновяват докато пишете."
          value={search}
          placeholder="Заглавие или адрес"
          onChange={(event) => setSearch(event.target.value)}
        />
        <div className="field">
          <LabelWithHelp
            htmlFor="status"
            label="Статус"
            help="По подразбиране изтритите са скрити. Активен се показва, неактивен е спрян, изтрит може да се възстанови."
          />
          <Select value={filters.status} onValueChange={(value) => updateParams({ status: value })}>
            <SelectTrigger id="status" className="w-full min-h-12 font-sans">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Всички (без изтрити)</SelectItem>
              <SelectItem value="active">Активни</SelectItem>
              <SelectItem value="inactive">Неактивни</SelectItem>
              <SelectItem value="deleted">Изтрити</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </form>

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      <DataTable
        columns={columns}
        data={banners}
        getRowId={(banner) => String(banner.id)}
        loading={busy}
        emptyMessage="Няма банери за избраните филтри."
        caption="Списък с банери"
        isRowSelectable={(banner) => !banner.deleted_at}
        onBulkDelete={async (selected) => {
          await Promise.all(selected.map((banner) => deleteBanner(token, banner.id)));
          const ids = new Set(selected.map((banner) => banner.id));
          setBanners((current) => current.filter((banner) => !ids.has(banner.id)));
          setTotal((current) => Math.max(0, current - selected.length));
          toast.success(`${selected.length} банера бяха изтрити.`);
        }}
        pagination={{
          page: filters.page,
          lastPage,
          total,
          pageSize: filters.per_page,
          onPageChange: (page) => updateParams({ page: String(page) }, false),
          onPageSizeChange: (pageSize) =>
            updateParams({ per_page: pageSize === DEFAULT_PAGE_SIZE ? '' : String(pageSize) }),
        }}
      />

      {pending ? (
        <ConfirmDialog
          title={pending.deleted_at ? 'Възстановяване' : 'Изтриване'}
          message={
            pending.deleted_at
              ? `Да възстановим ли банера „${pending.title}“?`
              : `Да изтрием ли банера „${pending.title}“? Може да го възстановите по-късно.`
          }
          confirmLabel={pending.deleted_at ? 'Възстанови' : 'Изтрий'}
          busy={acting}
          onCancel={() => setPending(null)}
          onConfirm={() => void confirmPending()}
        />
      ) : null}
    </div>
  );
}
