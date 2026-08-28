import { Link } from 'react-router-dom';
import { MoreHorizontal, Pencil, RotateCcw, Trash2, UserRound } from 'lucide-react';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { Button } from '@/components/ui/Button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ManagedUser } from '@/api/users';
import { formatDateTime, formatRelativeTime, roleLabel } from '@/lib/format';

const helper = createDataTableHelper<ManagedUser>();

type UsersColumnsOptions = {
  currentId?: number;
  onRestore: (user: ManagedUser) => void;
  onDelete: (user: ManagedUser) => void;
};

function UsersRowActions({
  user,
  currentId,
  onRestore,
  onDelete,
}: {
  user: ManagedUser;
  currentId?: number;
  onRestore: (user: ManagedUser) => void;
  onDelete: (user: ManagedUser) => void;
}) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button type="button" variant="ghost" size="icon" aria-label="Още опции">
          <MoreHorizontal />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {user.deleted_at ? (
          <DropdownMenuItem onSelect={() => onRestore(user)}>
            <RotateCcw />
            Възстанови
          </DropdownMenuItem>
        ) : (
          <>
            <DropdownMenuItem asChild>
              <Link to={`/users/${user.id}`}>
                <Pencil />
                Редакция
              </Link>
            </DropdownMenuItem>
            {user.id !== currentId ? (
              <>
                <DropdownMenuSeparator />
                <DropdownMenuItem variant="destructive" onSelect={() => onDelete(user)}>
                  <Trash2 />
                  Изтрий
                </DropdownMenuItem>
              </>
            ) : null}
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export function getUsersColumns({ currentId, onRestore, onDelete }: UsersColumnsOptions) {
  return helper.columns([
    helper.display({
      id: 'avatar',
      header: 'Снимка',
      enableSorting: false,
      meta: { className: 'w-24', help: 'Профилна снимка. Липсваща снимка показва икона.' },
      cell: ({ row }) => {
        const user = row.original;
        const url = user.avatar_url;
        const alt = `${user.first_name} ${user.last_name}`.trim();

        return (
          <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-[6px] border border-border bg-muted">
            {url ? (
              <img src={url} alt={alt || 'Профил'} className="size-full object-cover" />
            ) : (
              <UserRound className="size-5 text-muted-foreground" aria-hidden />
            )}
          </div>
        );
      },
    }),
    helper.accessor((user) => `${user.first_name} ${user.last_name}`, {
      id: 'name',
      header: 'Име',
      sortFn: 'text',
      meta: { sticky: true, help: 'Пълно име. „Вие“ означава профила, с който сте влезли.' },
      cell: ({ row }) => {
        const user = row.original;

        return (
          <div className="min-w-40">
            <p className="m-0 text-foreground">
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
      meta: { help: 'Имейлът служи за вход и за съобщения към потребителя.' },
    }),
    helper.accessor((user) => roleLabel(user.role), {
      id: 'role',
      header: 'Роля',
      sortFn: 'text',
      meta: { help: 'Администратор има достъп до този панел. Клиент е профил за магазина.' },
      cell: ({ row }) => (
        <span className={`badge ${row.original.role === 'admin' ? 'info' : 'idle'}`}>
          {roleLabel(row.original.role)}
        </span>
      ),
    }),
    helper.accessor(
      (user) => (user.deleted_at ? 'Изтрит' : user.is_active ? 'Активен' : 'Неактивен'),
      {
        id: 'status',
        header: 'Статус',
        sortFn: 'text',
        meta: {
          help: 'Активен може да влиза. Неактивен е блокиран. Изтрит е скрит от обичайния списък и може да се възстанови.',
        },
        cell: ({ row }) => {
          const user = row.original;
          const label = user.deleted_at ? 'Изтрит' : user.is_active ? 'Активен' : 'Неактивен';

          return (
            <span className={`badge ${user.deleted_at ? 'warn' : user.is_active ? 'ok' : 'idle'}`}>
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
      meta: { help: 'Кога потребителят последно е влязъл. Показано относително; точната дата се вижда при посочване.' },
      cell: ({ row }) => {
        const value = row.original.last_login_at;

        return (
          <span title={value ? formatDateTime(value) : undefined}>{formatRelativeTime(value)}</span>
        );
      },
    }),
    helper.display({
      id: 'actions',
      header: 'Действия',
      enableSorting: false,
      meta: {
        className: 'text-right',
        help: 'Редакция, изтриване или възстановяване на профила.',
      },
      cell: ({ row }) => (
        <div className="flex justify-end">
          <UsersRowActions
            user={row.original}
            currentId={currentId}
            onRestore={onRestore}
            onDelete={onDelete}
          />
        </div>
      ),
    }),
  ]);
}
