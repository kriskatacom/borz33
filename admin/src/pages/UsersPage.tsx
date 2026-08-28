import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { UserPlus } from 'lucide-react';
import { ApiError } from '@/api/client';
import { deleteUser, listUsers, restoreUser, type ManagedUser } from '@/api/users';
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
import { getUsersColumns } from '@/features/users/usersColumns';

function parsePageSize(raw: string | null): number {
  const value = Number(raw);

  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
}

export function UsersPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const currentId = useAppSelector((state) => state.auth.user?.id);
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [users, setUsers] = useState<ManagedUser[]>([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [pending, setPending] = useState<ManagedUser | null>(null);
  const [acting, setActing] = useState(false);
  useGlobalLoading(busy);

  const filters = useMemo(
    () => ({
      q: params.get('q') ?? '',
      role: params.get('role') ?? '',
      status: params.get('status') ?? 'all',
      page: Number(params.get('page') ?? '1') || 1,
      per_page: parsePageSize(params.get('per_page')),
    }),
    [params]
  );

  const columns = useMemo(
    () =>
      getUsersColumns({
        currentId,
        onRestore: setPending,
        onDelete: setPending,
      }),
    [currentId]
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
        const response = await listUsers(token, filters);
        if (cancelled) {
          return;
        }
        setUsers(response.data.users);
        setTotal(response.data.pagination.total);
        setLastPage(response.data.pagination.last_page);
      } catch (error) {
        if (!cancelled) {
          setMessage(error instanceof ApiError ? error.message : 'Списъкът не можа да се зареди.');
          setUsers([]);
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
    setMessage(null);

    try {
      if (pending.deleted_at) {
        await restoreUser(token, pending.id);
      } else {
        await deleteUser(token, pending.id);
      }
      setPending(null);
      const response = await listUsers(token, filters);
      setUsers(response.data.users);
      setTotal(response.data.pagination.total);
      setLastPage(response.data.pagination.last_page);
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : 'Действието не беше успешно.');
    } finally {
      setActing(false);
    }
  }

  return (
    <div className="page">
      <PageHeader
        title="Потребители"
        help="Списък с администратори и клиенти. От тук търсите, филтрирате, редактирате, изтривате или възстановявате профили."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Потребители' },
        ]}
        actions={
          <Button asChild>
            <Link to={routes.usersNew}>
              <UserPlus />
              Нов потребител
            </Link>
          </Button>
        }
      />

      <form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field
          id="q"
          label="Търсене"
          help="Търси по име, имейл или телефон. Резултатите се обновяват докато пишете."
          value={search}
          placeholder="Име, имейл или телефон"
          onChange={(event) => setSearch(event.target.value)}
        />
        <div className="field">
          <LabelWithHelp
            htmlFor="role"
            label="Роля"
            help="Показва само потребители с избраната роля. „Всички роли“ включва и администратори, и клиенти."
          />
          <Select
            value={filters.role || 'all'}
            onValueChange={(value) => updateParams({ role: value === 'all' ? '' : value })}
          >
            <SelectTrigger id="role" className="w-full min-h-12 font-sans">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Всички роли</SelectItem>
              <SelectItem value="admin">Администратор</SelectItem>
              <SelectItem value="customer">Клиент</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div className="field">
          <LabelWithHelp
            htmlFor="status"
            label="Статус"
            help="По подразбиране изтритите са скрити. Активен може да влиза, неактивен е блокиран, изтрит може да се възстанови."
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
        data={users}
        getRowId={(user) => String(user.id)}
        loading={busy}
        emptyMessage="Няма потребители за избраните филтри."
        caption="Списък с потребители"
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
              ? `Да възстановим ли профила на ${pending.first_name} ${pending.last_name}?`
              : `Да изтрием ли профила на ${pending.first_name} ${pending.last_name}? Сесиите му ще бъдат прекратени.`
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
