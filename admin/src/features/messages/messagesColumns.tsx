import { Link } from 'react-router-dom';
import { Eye, Mail, MailOpen } from 'lucide-react';
import type { ContactMessage } from '@/api/messages';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { Button } from '@/components/ui/Button';
import { formatDateTime } from '@/lib/format';

const helper = createDataTableHelper<ContactMessage>();

export function getMessagesColumns() {
  return helper.columns([
    helper.display({ id: 'state', header: '', meta: { className: 'w-12' }, cell: ({ row }) => row.original.read_at ? <MailOpen className="size-4 text-muted-foreground" aria-label="Прочетено" /> : <Mail className="size-4 text-primary" aria-label="Непрочетено" /> }),
    helper.accessor('subject', { header: 'Съобщение', meta: { sticky: true }, cell: ({ row }) => <div className="min-w-56"><Link to={`/messages/${row.original.id}`} className={row.original.read_at ? 'text-foreground no-underline hover:underline' : 'font-extrabold text-foreground no-underline hover:underline'}>{row.original.subject}</Link><p className="m-0 mt-1 line-clamp-1 text-sm text-muted-foreground">{row.original.message}</p></div> }),
    helper.accessor('name', { header: 'Подател', cell: ({ row }) => <div><p className="m-0 font-medium">{row.original.name}</p><p className="m-0 text-sm text-muted-foreground">{row.original.email}</p></div> }),
    helper.accessor('email_sent', { header: 'Имейл', cell: ({ getValue }) => getValue() ? <span className="text-sm text-primary">Изпратен</span> : <span className="text-sm text-destructive">Неуспешен</span> }),
    helper.accessor('created_at', { header: 'Получено', cell: ({ getValue }) => formatDateTime(getValue()) }),
    helper.display({ id: 'actions', header: '', meta: { className: 'w-14 text-right' }, cell: ({ row }) => <Button asChild size="icon" variant="ghost" aria-label="Преглед"><Link to={`/messages/${row.original.id}`}><Eye /></Link></Button> }),
  ]);
}
