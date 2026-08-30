import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, FileText, Mail, MailOpen, Paperclip, Phone, Send, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import { getMessage, markMessage, sendMessageReply, type ContactAttachment, type ContactMessage } from '@/api/messages';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { formatDateTime } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';

const customerBubble = 'rounded-[14px] rounded-bl-[3px] border border-input bg-field px-4 py-3 text-foreground shadow-sm';
const formatSize = (bytes: number) => bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.ceil(bytes / 1024))} KB`;

function Attachments({ items = [] }: { items?: ContactAttachment[] }) {
  if (items.length === 0) return null;
  return <div className="mt-3 grid gap-1.5 border-t border-current/15 pt-3">{items.map((file) => <a key={file.id} className="flex min-w-0 items-center gap-2 border border-current/15 bg-background/50 p-2 text-inherit no-underline hover:bg-background/80" href={file.url} target="_blank" rel="noreferrer"><FileText className="size-4 shrink-0" /><span className="grid min-w-0 text-left"><strong className="truncate text-xs">{file.name}</strong><small className="text-[11px] opacity-70">{formatSize(file.size)}</small></span></a>)}</div>;
}

export function MessageDetailsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const id = Number(useParams().id);
  const [message, setMessage] = useState<ContactMessage | null>(null);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [reply, setReply] = useState('');
  const [files, setFiles] = useState<File[]>([]);
  const [sending, setSending] = useState(false);
  useGlobalLoading(busy);

  useEffect(() => {
    let cancelled = false;
    setBusy(true);
    void getMessage(token, id)
      .then((response) => { if (!cancelled) { setMessage(response.data.message); window.dispatchEvent(new CustomEvent('admin:messages-unread-refresh')); } })
      .catch((reason) => { if (!cancelled) setError(reason instanceof ApiError ? reason.message : 'Съобщението не можа да се зареди.'); })
      .finally(() => { if (!cancelled) setBusy(false); });
    return () => { cancelled = true; };
  }, [id, token]);

  async function toggleRead() {
    if (!message) return;
    try {
      const response = await markMessage(token, message.id, Boolean(!message.read_at));
      setMessage(response.data.message);
      window.dispatchEvent(new CustomEvent('admin:messages-unread-refresh'));
      toast.success(response.message);
    } catch (reason) { toastError(reason, 'Статусът не можа да се промени.'); }
  }

  async function sendReply() {
    if (!message || reply.trim().length < 2) return;
    setSending(true);
    try {
      const response = await sendMessageReply(token, message.id, reply.trim(), files);
      setMessage(response.data.message);
      setReply('');
      setFiles([]);
      if (response.data.email_sent) toast.success(response.message || 'Отговорът е изпратен.');
      else toast.error(response.message || 'Отговорът е записан, но имейлът не можа да бъде изпратен.');
    } catch (reason) { toastError(reason, 'Отговорът не можа да бъде изпратен.'); }
    finally { setSending(false); }
  }

  if (error) return <div className="page"><p className="form-message is-error">{error}</p></div>;
  if (!message) return <div className="page" aria-busy="true" />;

  return <div className="page">
    <PageHeader title={message.subject} help={`Разговор с ${message.name} · започнат ${formatDateTime(message.created_at)}`} crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Съобщения', to: routes.messages }, { label: 'Разговор' }]} actions={<Button asChild variant="outline"><Link to={routes.messages}><ArrowLeft />Назад</Link></Button>} />
    <div className="grid w-full gap-4">
      <header className="flex flex-col gap-3 border border-border bg-card p-4 sm:flex-row sm:items-start sm:justify-between">
        <div><h2 className="m-0 text-lg">{message.name}</h2><a className="mt-1 inline-flex items-center gap-2" href={`mailto:${message.email}`}><Mail className="size-4" />{message.email}</a>{message.phone ? <a className="mt-1 flex items-center gap-2" href={`tel:${message.phone}`}><Phone className="size-4" />{message.phone}</a> : null}</div>
        <Button type="button" size="sm" variant="outline" onClick={() => void toggleRead()}>{message.read_at ? <Mail /> : <MailOpen />}{message.read_at ? 'Маркирай като непрочетено' : 'Маркирай като прочетено'}</Button>
      </header>
      <section className="grid min-h-72 content-start gap-4 border border-border bg-muted/30 p-4" aria-label="Разговор">
        <article className="mr-auto max-w-[85%]"><div className={customerBubble}><p className="m-0 whitespace-pre-wrap leading-7">{message.message}</p><Attachments items={message.attachments} /></div><p className="m-0 mt-1 text-xs text-muted-foreground">{message.name} · <time dateTime={message.created_at ?? undefined}>{formatDateTime(message.created_at)}</time></p></article>
        {(message.replies ?? []).map((item) => {
          const customer = item.sender_type === 'customer';
          return <article key={item.id} className={`${customer ? 'mr-auto' : 'ml-auto text-right'} max-w-[85%]`}>
            <div className={customer ? customerBubble : 'rounded-[14px] rounded-br-[3px] bg-primary px-4 py-3 text-left text-primary-foreground shadow-sm'}><p className="m-0 whitespace-pre-wrap leading-7">{item.body}</p><Attachments items={item.attachments} /></div>
            <p className="m-0 mt-1 text-xs text-muted-foreground">{item.sender} · <time dateTime={item.created_at ?? undefined}>{formatDateTime(item.created_at)}</time>{customer ? '' : ` · ${item.email_sent ? 'изпратен по имейл' : 'имейлът е неуспешен'}`}</p>
          </article>;
        })}
      </section>
      <form className="grid gap-2 border border-border bg-card p-4" onSubmit={(event) => { event.preventDefault(); void sendReply(); }}>
        <label htmlFor="message-reply" className="font-bold">Вашият отговор</label>
        <textarea id="message-reply" className="min-h-32 w-full resize-y border border-input bg-field p-3 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50" value={reply} maxLength={10000} placeholder={`Отговор до ${message.name}…`} disabled={sending} onChange={(event) => setReply(event.target.value)} />
        <div className="flex flex-wrap items-center gap-2"><label className="inline-flex cursor-pointer items-center gap-2 border border-dashed border-input bg-field px-3 py-2 text-sm hover:bg-accent"><Paperclip className="size-4" />Прикачи файлове<input className="sr-only" type="file" multiple disabled={sending} accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,text/plain,.doc,.docx,.xls,.xlsx" onChange={(event) => setFiles(Array.from(event.target.files ?? []).slice(0, 5))} /></label>{files.map((file, index) => <span key={`${file.name}-${index}`} className="inline-flex max-w-64 items-center gap-1 border border-border bg-muted px-2 py-1 text-xs"><span className="truncate">{file.name}</span><button type="button" className="shrink-0" aria-label={`Премахни ${file.name}`} onClick={() => setFiles((current) => current.filter((_, itemIndex) => itemIndex !== index))}><X className="size-3.5" /></button></span>)}</div>
        <div className="flex items-center justify-between gap-3"><span className="text-xs text-muted-foreground">Отговорът се записва в разговора и се изпраща на {message.email}.</span><Button type="submit" disabled={sending || reply.trim().length < 2}><Send />{sending ? 'Изпращане…' : 'Изпрати отговора'}</Button></div>
      </form>
    </div>
  </div>;
}
