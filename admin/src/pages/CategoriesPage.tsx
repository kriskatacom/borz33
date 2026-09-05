import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { FolderInput, FolderPlus } from 'lucide-react';
import { ApiError } from '@/api/client';
import {
  deleteCategory,
  bulkSetCategoryParent,
  listCategories,
  listCategoryTree,
  restoreCategory,
  type CategoryListItem,
} from '@/api/categories';
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
import { getCategoriesColumns } from '@/features/categories/categoriesColumns';
import { flattenCategoryTree } from '@/features/categories/categoryTree';
import { PageTreeSelect } from '@/features/pages/PageTreeSelect';
import { toast, toastError } from '@/lib/toast';

function parsePageSize(raw: string | null): number {
  const value = Number(raw);

  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
}

export function CategoriesPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [categories, setCategories] = useState<CategoryListItem[]>([]);
  const [treeOptions, setTreeOptions] = useState<ReturnType<typeof flattenCategoryTree>>([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [pending, setPending] = useState<CategoryListItem | null>(null);
  const [acting, setActing] = useState(false);
  const [treeTick, setTreeTick] = useState(0);
  const [bulkParent, setBulkParent] = useState('none');
  const [filterDialogOpen, setFilterDialogOpen] = useState(false);
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

  const listedCategories = useMemo(() => {
    if (treeOptions.length === 0) {
      return categories;
    }

    const order = new Map(treeOptions.map((option, index) => [option.id, index]));

    return [...categories].sort(
      (left, right) => (order.get(left.id) ?? Number.MAX_SAFE_INTEGER) - (order.get(right.id) ?? Number.MAX_SAFE_INTEGER)
    );
  }, [categories, treeOptions]);

  const columns = useMemo(
    () =>
      getCategoriesColumns({
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
        const response = await listCategories(token, {
          ...filters,
          parent: filters.parent === 'all' ? undefined : filters.parent,
        });
        if (cancelled) {
          return;
        }
        setCategories(response.data.categories);
        setTotal(response.data.pagination.total);
        setLastPage(response.data.pagination.last_page);
      } catch (error) {
        if (!cancelled) {
          const text = error instanceof ApiError ? error.message : 'Списъкът не можа да се зареди.';
          setMessage(text);
          toast.error(text);
          setCategories([]);
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
        const response = await listCategoryTree(token);
        if (!cancelled) {
          setTreeOptions(flattenCategoryTree(response.data.categories));
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
        const response = await restoreCategory(token, pending.id);
        toast.success(response.message || 'Категорията е възстановена.');
      } else {
        const response = await deleteCategory(token, pending.id);
        toast.success(response.message || 'Категорията е изтрита.');
      }
      setPending(null);
      setTreeTick((current) => current + 1);
      const response = await listCategories(token, {
        ...filters,
        parent: filters.parent === 'all' ? undefined : filters.parent,
      });
      setCategories(response.data.categories);
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
        title="Категории"
        help="Дърво от категории за каталога. Можете да влагате категории и да им зададете изображение."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Категории' },
        ]}
        actions={<><Button type="button" variant="outline" onClick={() => setFilterDialogOpen(true)}>Филтри (3)</Button><Button asChild><Link to={routes.categoriesNew}><FolderPlus />Нова категория</Link></Button></>}
      />

      {filterDialogOpen ? <div className="dialog-root"><button type="button" className="dialog-backdrop" aria-label="Затвори филтрите" onClick={() => setFilterDialogOpen(false)} /><div className="dialog dialog-wide catalog-filters-dialog" role="dialog" aria-modal="true" aria-labelledby="categories-filters-title"><header className="orders-filters-dialog-header"><h2 id="categories-filters-title">Филтри на категориите</h2><Button type="button" variant="ghost" size="icon" aria-label="Затвори филтрите" onClick={() => setFilterDialogOpen(false)}>×</Button></header><form className="filters catalog-filters catalog-category-filters" onSubmit={(event) => event.preventDefault()}>
        <Field
          id="q"
          label="Търсене"
          help="Търси по име или адрес. Резултатите се обновяват докато пишете."
          value={search}
          placeholder="Име или адрес"
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
          help="Показва преките деца на избраната категория. Вложените категории са с дълги тирета."
          value={filters.parent}
          options={treeOptions}
          extra={[
            { value: 'all', label: 'Всички' },
            { value: 'root', label: 'Без родител' },
          ]}
          onValueChange={(value) => updateParams({ parent: value === 'all' ? '' : value })}
        />
      </form><footer className="dialog-actions filter-dialog-actions"><Button type="button" variant="outline" onClick={() => setFilterDialogOpen(false)}>Затвори</Button></footer></div></div> : null}

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      <DataTable
        columns={columns}
        data={listedCategories}
        getRowId={(category) => String(category.id)}
        loading={busy}
        emptyMessage="Няма категории за избраните филтри."
        caption="Списък с категории"
        isRowSelectable={(category) => !category.deleted_at}
        renderBulkActions={({ rows, busy: bulkBusy, run }) => {
          const selectedIds = new Set(rows.map((category) => category.id));
          const availableParents = treeOptions.filter((option) => !selectedIds.has(option.id));
          const removeParent = bulkParent === 'none';

          return <>
            <Select value={bulkParent} disabled={bulkBusy} onValueChange={setBulkParent}>
              <SelectTrigger className="min-h-9 w-[15rem] bg-card" aria-label="Нов родител на избраните категории">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Без родител</SelectItem>
                {availableParents.map((option) => <SelectItem key={option.id} value={String(option.id)} className="whitespace-pre">{option.label}</SelectItem>)}
              </SelectContent>
            </Select>
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={bulkBusy}
              onClick={() => run(async () => {
                const parentId = removeParent ? null : Number(bulkParent);
                const response = await bulkSetCategoryParent(token, rows.map((category) => category.id), parentId);
                const refreshed = await listCategories(token, { ...filters, parent: filters.parent === 'all' ? undefined : filters.parent });
                setCategories(refreshed.data.categories);
                setTotal(refreshed.data.pagination.total);
                setLastPage(refreshed.data.pagination.last_page);
                setTreeTick((current) => current + 1);
                toast.success(response.message || (removeParent ? 'Родителят е премахнат.' : 'Родителят е зададен.'));
              })}
            >
              <FolderInput />
              {removeParent ? 'Премахни родителя' : 'Задай родител'}
            </Button>
          </>;
        }}
        onBulkDelete={async (selected) => {
          await Promise.all(selected.map((category) => deleteCategory(token, category.id)));
          const ids = new Set(selected.map((category) => category.id));
          setCategories((current) => current.filter((category) => !ids.has(category.id)));
          setTotal((current) => Math.max(0, current - selected.length));
          toast.success(`${selected.length} категории бяха изтрити.`);
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
              ? `Да възстановим ли категорията „${pending.name}“?`
              : `Да изтрием ли категорията „${pending.name}“? Може да я възстановите по-късно.`
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
