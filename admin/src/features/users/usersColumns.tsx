import { Link } from 'react-router-dom';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import type { ManagedUser } from '@/api/users';
import { formatDateTime, roleLabel } from '@/lib/format';

const helper = createDataTableHelper<ManagedUser>();

type UsersColumnsOptions = {
  currentId?: number;
  onRestore: (user: ManagedUser) => void;
  onDelete: (user: ManagedUser) => void;
};

export function getUsersColumns({ currentId, onRestore, onDelete }: UsersColumnsOptions) {
  return helper.columns([
    helper.accessor((user) => `${user.first_name} ${user.last_name}`, {
      id: 'name',
      header: 'Име',
      sortFn: 'text',
      meta: { sticky: true },
      cell: ({ row }) => {
        const user = row.original;

        return (
          <div className="min-w-40">
            <p className="m-0 font-serif text-base text-foreground">
              {user.first_name} {user.last_name}
              {user.id === currentId ? (
                <span className="badge ml-2 align-middle">Вие</span>
              ) : null}
            </p>
          </div>
        );
      },
    }),
    helper.accessor('email', {
      header: 'Имейл',
      sortFn: 'text',
    }),
    helper.accessor((user) => roleLabel(user.role), {
      id: 'role',
      header: 'Роля',
      sortFn: 'text',
    }),
    helper.accessor(
      (user) => (user.deleted_at ? 'Изтрит' : user.is_active ? 'Активен' : 'Неактивен'),
      {
        id: 'status',
        header: 'Статус',
        sortFn: 'text',
        cell: ({ row }) => {
          const user = row.original;
          const label = user.deleted_at ? 'Изтрит' : user.is_active ? 'Активен' : 'Неактивен';

          return (
            <span className={`badge ${user.deleted_at ? 'warn' : user.is_active ? 'ok' : ''}`}>
              {label}
            </span>
          );
        },
      }
    ),
    helper.accessor((user) => user.last_login_at ?? '', {
      id: 'last_login',
      header: 'Последен вход',
      sortFn: 'text',
      cell: ({ row }) => formatDateTime(row.original.last_login_at),
    }),
    helper.display({
      id: 'actions',
      header: 'Действия',
      enableSorting: false,
      cell: ({ row }) => {
        const user = row.original;

        if (user.deleted_at) {
          return (
            <button type="button" className="text-btn" onClick={() => onRestore(user)}>
              Възстанови
            </button>
          );
        }

        return (
          <div className="row-actions">
            <Link to={`/users/${user.id}`}>Редакция</Link>
            {user.id !== currentId ? (
              <button type="button" className="text-btn" onClick={() => onDelete(user)}>
                Изтрий
              </button>
            ) : null}
          </div>
        );
      },
    }),
  ]);
}
