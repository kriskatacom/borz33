import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ApiError } from '@/api/client';
import { deleteUser, listUsers, restoreUser, type ManagedUser } from '@/api/users';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { DataTable } from '@/components/data-table/DataTable';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Field } from '@/components/ui/Field';
import { SelectField } from '@/components/ui/SelectField';
import { getUsersColumns } from '@/features/users/usersColumns';

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

  const filters = useMemo(
    () => ({
      q: params.get('q') ?? '',
      role: params.get('role') ?? '',
      status: params.get('status') ?? 'all',
      page: Number(params.get('page') ?? '1') || 1,
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
      <header className="page-head split">
        <div>
          <p className="eyebrow">Потребители</p>
          <h1>Управление на профили</h1>
          <p className="muted">Създавате, редактирате и деактивирате администратори и клиенти от едно място.</p>
        </div>
        <Link className="btn btn-primary" to={routes.usersNew}>
          Нов потребител
        </Link>
      </header>

      <form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field
          id="q"
          label="Търсене"
          value={search}
          placeholder="Име, имейл или телефон"
          onChange={(event) => setSearch(event.target.value)}
        />
        <SelectField
          id="role"
          label="Роля"
          value={filters.role}
          onChange={(event) => updateParams({ role: event.target.value })}
        >
          <option value="">Всички роли</option>
          <option value="admin">Администратор</option>
          <option value="customer">Клиент</option>
        </SelectField>
        <SelectField
          id="status"
          label="Статус"
          value={filters.status}
          onChange={(event) => updateParams({ status: event.target.value })}
        >
          <option value="all">Всички (без изтрити)</option>
          <option value="active">Активни</option>
          <option value="inactive">Неактивни</option>
          <option value="deleted">Изтрити</option>
        </SelectField>
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
          onPageChange: (page) => updateParams({ page: String(page) }, false),
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
