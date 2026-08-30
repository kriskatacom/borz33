import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Plus } from 'lucide-react';
import { ApiError } from '@/api/client';
import { listCategoryTree } from '@/api/categories';
import { deleteProduct, forceDeleteProduct, listProducts, restoreProduct, type ProductListItem } from '@/api/products';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { DataTable, DATA_TABLE_PAGE_SIZES, DEFAULT_PAGE_SIZE } from '@/components/data-table/DataTable';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { getProductsColumns } from '@/features/products/productsColumns';
import { flattenCategoryTree } from '@/features/categories/categoryTree';
import { PageTreeSelect } from '@/features/pages/PageTreeSelect';
import { toast, toastError } from '@/lib/toast';

type PendingProductAction = { product: ProductListItem; action: 'delete' | 'restore' | 'force' };

function parsePageSize(raw: string | null): number {
  const value = Number(raw);

  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
}

export function ProductsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [products, setProducts] = useState<ProductListItem[]>([]);
  const [treeOptions, setTreeOptions] = useState<ReturnType<typeof flattenCategoryTree>>([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [pending, setPending] = useState<PendingProductAction | null>(null);
  const [acting, setActing] = useState(false);
  const [revision, setRevision] = useState(0);
  useGlobalLoading(busy);

  const filters = useMemo(
    () => ({
      q: params.get('q') ?? '',
      status: params.get('status') ?? 'all',
      category: params.get('category') ?? 'all',
      page: Number(params.get('page') ?? '1') || 1,
      per_page: parsePageSize(params.get('per_page')),
    }),
    [params]
  );

  const columns = useMemo(() => getProductsColumns({
    onDelete: (product) => setPending({ product, action: 'delete' }),
    onRestore: (product) => setPending({ product, action: 'restore' }),
    onForceDelete: (product) => setPending({ product, action: 'force' }),
  }), []);

  async function confirmPending() {
    if (!pending) return;
    setActing(true);
    try {
      const response = pending.action === 'restore'
        ? await restoreProduct(token, pending.product.id)
        : pending.action === 'force'
          ? await forceDeleteProduct(token, pending.product.id)
          : await deleteProduct(token, pending.product.id);
      toast.success(response.message || (pending.action === 'restore' ? 'Продуктът е възстановен.' : 'Продуктът е изтрит.'));
      setPending(null);
      setRevision((value) => value + 1);
    } catch (error) {
      toastError(error, 'Действието не беше успешно.');
    } finally {
      setActing(false);
    }
  }

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
        const response = await listProducts(token, {
          ...filters,
          category: filters.category === 'all' ? undefined : filters.category,
        });
        if (cancelled) {
          return;
        }
        setProducts(response.data.products);
        setTotal(response.data.pagination.total);
        setLastPage(response.data.pagination.last_page);
      } catch (error) {
        if (!cancelled) {
          const text = error instanceof ApiError ? error.message : 'Списъкът не можа да се зареди.';
          setMessage(text);
          toast.error(text);
          setProducts([]);
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
  }, [filters, token, revision]);

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
  }, [token]);

  return (
    <div className="page">
      <PageHeader
        title="Продукти"
        help="Каталог с тениски и подобни артикули. От тук търсите, филтрирате и преглеждате продукт с варианти и параметри."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Продукти' },
        ]}
        actions={
          <Button asChild>
            <Link to={routes.productsNew}>
              <Plus />
              Нов продукт
            </Link>
          </Button>
        }
      />

      <form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field
          id="q"
          label="Търсене"
          help="Търси по име, slug, SKU или кратко описание. Резултатите се обновяват докато пишете."
          value={search}
          placeholder="Име, SKU или описание"
          onChange={(event) => setSearch(event.target.value)}
        />
        <div className="field">
          <LabelWithHelp
            htmlFor="status"
            label="Статус"
            help="По подразбиране изтритите са скрити. Активен е в каталога, неактивен е спрян."
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
          id="category"
          label="Категория"
          help="Филтър по категория. „Без категория“ са продукти без избрана категория."
          value={filters.category}
          options={treeOptions}
          extra={[
            { value: 'all', label: 'Всички' },
            { value: 'none', label: 'Без категория' },
          ]}
          onValueChange={(value) => updateParams({ category: value === 'all' ? '' : value })}
        />
      </form>

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      <DataTable
        columns={columns}
        data={products}
        getRowId={(product) => String(product.id)}
        loading={busy}
        emptyMessage="Няма продукти за избраните филтри."
        caption="Списък с продукти"
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

      {pending ? <ConfirmDialog
        title={pending.action === 'restore' ? 'Възстановяване на продукт' : pending.action === 'force' ? 'Окончателно изтриване' : 'Изтриване на продукт'}
        message={pending.action === 'restore'
          ? `Да възстановим ли „${pending.product.name}“?`
          : pending.action === 'force'
            ? `„${pending.product.name}“ ще бъде изтрит завинаги заедно със свързаните продуктови изображения. Това действие не може да бъде отменено.`
            : `„${pending.product.name}“ ще бъде преместен в „Изтрити“ и може да бъде възстановен по-късно.`}
        confirmLabel={pending.action === 'restore' ? 'Възстанови' : pending.action === 'force' ? 'Изтрий завинаги' : 'Изтрий'}
        variant={pending.action === 'restore' ? 'default' : 'destructive'}
        busy={acting}
        onCancel={() => setPending(null)}
        onConfirm={() => void confirmPending()}
      /> : null}
    </div>
  );
}
