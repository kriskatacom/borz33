import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { FilePlus } from 'lucide-react';
import { ApiError } from '@/api/client';
import { deletePage, listPages, listPageTree, restorePage, type PageListItem } from '@/api/pages';
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
import { getPagesColumns } from '@/features/pages/pagesColumns';
import { PageTreeSelect } from '@/features/pages/PageTreeSelect';
import { flattenPageTree } from '@/features/pages/pageTree';
import { toast, toastError } from '@/lib/toast';

function parsePageSize(raw: string | null): number {
  const value = Number(raw);

  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
}

export function PagesPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [pages, setPages] = useState<PageListItem[]>([]);
  const [treeOptions, setTreeOptions] = useState<ReturnType<typeof flattenPageTree>>([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [pending, setPending] = useState<PageListItem | null>(null);
  const [acting, setActing] = useState(false);
  const [treeTick, setTreeTick] = useState(0);
  useGlobalLoading(busy);

  const filters = useMemo(
    () => ({
      q: params.get('q') ?? '',
      status: params.get('status') ?? 'all',
      parent: params.get('parent') ?? 'all',
      page: Number(params.get('page') ?? '1') || 1,
      per_page: parsePageSize(params.get('per_page')),
    }),
    [params]
  );

  const depthById = useMemo(() => {
    const depths: Record<number, number> = {};

    for (const option of treeOptions) {
      depths[option.id] = option.depth;
    }

    return depths;
  }, [treeOptions]);

  const listedPages = useMemo(() => {
    if (treeOptions.length === 0) {
      return pages;
    }

    const order = new Map(treeOptions.map((option, index) => [option.id, index]));

    return [...pages].sort(
      (left, right) => (order.get(left.id) ?? Number.MAX_SAFE_INTEGER) - (order.get(right.id) ?? Number.MAX_SAFE_INTEGER)
    );
  }, [pages, treeOptions]);

  const columns = useMemo(
    () =>
      getPagesColumns({
        onRestore: setPending,
        onDelete: setPending,
        depthById,
      }),
    [depthById]
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
        const response = await listPages(token, {
          ...filters,
          parent: filters.parent === 'all' ? undefined : filters.parent,
        });
        if (cancelled) {
          return;
        }
        setPages(response.data.pages);
        setTotal(response.data.pagination.total);
        setLastPage(response.data.pagination.last_page);
      } catch (error) {
        if (!cancelled) {
          const text = error instanceof ApiError ? error.message : 'Списъкът не можа да се зареди.';
          setMessage(text);
          toast.error(text);
          setPages([]);
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

  useEffect(() => {
    let cancelled = false;

    async function loadTree() {
      try {
        const response = await listPageTree(token);
        if (!cancelled) {
          setTreeOptions(flattenPageTree(response.data.pages));
        }
      } catch {
        if (!cancelled) {
          setTreeOptions([]);
        }
      }
    }

    void loadTree();

    return () => {
      cancelled = true;
    };
  }, [token, treeTick]);

  async function confirmPending() {
    if (!pending) {
      return;
    }

    setActing(true);

    try {
      if (pending.deleted_at) {
        const response = await restorePage(token, pending.id);
        toast.success(response.message || 'Страницата е възстановена.');
      } else {
        const response = await deletePage(token, pending.id);
        toast.success(response.message || 'Страницата е изтрита.');
      }
      setPending(null);
      setTreeTick((current) => current + 1);
      const response = await listPages(token, {
        ...filters,
        parent: filters.parent === 'all' ? undefined : filters.parent,
      });
      setPages(response.data.pages);
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
        title="Страници"
        help="CMS страници с персонални полета. От тук търсите, филтрирате, редактирате, изтривате или възстановявате."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Страници' },
        ]}
        actions={
          <Button asChild>
            <Link to={routes.pagesNew}>
              <FilePlus />
              Нова страница
            </Link>
          </Button>
        }
      />

      <form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field
          id="q"
          label="Търсене"
          help="Търси по заглавие, адрес или SEO заглавие. Резултатите се обновяват докато пишете."
          value={search}
          placeholder="Заглавие или адрес"
          onChange={(event) => setSearch(event.target.value)}
        />
        <div className="field">
          <LabelWithHelp
            htmlFor="status"
            label="Статус"
            help="По подразбиране изтритите са скрити. Активна може да се показва, неактивна е спряна, изтрита може да се възстанови."
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
        <PageTreeSelect
          id="parent"
          label="Родител"
          help="Показва преките деца на избраната страница. Вложените страници са с дълги тирета."
          value={filters.parent}
          options={treeOptions}
          extra={[
            { value: 'all', label: 'Всички' },
            { value: 'root', label: 'Без родител' },
          ]}
          onValueChange={(value) => updateParams({ parent: value === 'all' ? '' : value })}
        />
      </form>

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      <DataTable
        columns={columns}
        data={listedPages}
        getRowId={(page) => String(page.id)}
        loading={busy}
        emptyMessage="Няма страници за избраните филтри."
        caption="Списък със страници"
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
              ? `Да възстановим ли страницата „${pending.title}“?`
              : `Да изтрием ли страницата „${pending.title}“? Може да я възстановите по-късно.`
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
