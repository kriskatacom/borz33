import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Mail, MailOpen, Phone, Send } from 'lucide-react';
import { ApiError } from '@/api/client';
import { getMessage, markMessage, sendMessageReply, type ContactMessage } from '@/api/messages';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { formatDateTime } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';

export function MessageDetailsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const id = Number(useParams().id);
  const [message, setMessage] = useState<ContactMessage | null>(null);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [reply, setReply] = useState('');
  const [sending, setSending] = useState(false);
  useGlobalLoading(busy);
  useEffect(() => { let cancelled = false; setBusy(true); void getMessage(token, id).then((response) => { if (!cancelled) setMessage(response.data.message); }).catch((reason) => { if (!cancelled) setError(reason instanceof ApiError ? reason.message : 'Съобщението не можа да се зареди.'); }).finally(() => { if (!cancelled) setBusy(false); }); return () => { cancelled = true; }; }, [id, token]);
  async function toggleRead() { if (!message) return; try { const response = await markMessage(token, message.id, Boolean(!message.read_at)); setMessage(response.data.message); toast.success(response.message); } catch (reason) { toastError(reason, 'Статусът не можа да се промени.'); } }
  async function sendReply() { if (!message || reply.trim().length < 2) return; setSending(true); try { const response = await sendMessageReply(token, message.id, reply.trim()); setMessage(response.data.message); setReply(''); if (response.data.email_sent) toast.success(response.message || 'Отговорът е изпратен.'); else toast.error(response.message || 'Отговорът е записан, но имейлът не можа да бъде изпратен.'); } catch (reason) { toastError(reason, 'Отговорът не можа да бъде изпратен.'); } finally { setSending(false); } }
  if (error) return <div className="page"><p className="form-message is-error">{error}</p></div>;
  if (!message) return <div className="page" aria-busy="true" />;
  return <div className="page"><PageHeader title={message.subject} help={`Разговор с ${message.name} · започнат ${formatDateTime(message.created_at)}`} crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Съобщения', to: routes.messages }, { label: 'Разговор' }]} actions={<Button asChild variant="outline"><Link to={routes.messages}><ArrowLeft />Назад</Link></Button>} /><div className="mx-auto grid max-w-4xl gap-4"><header className="flex flex-col gap-3 border border-border bg-card p-4 sm:flex-row sm:items-start sm:justify-between"><div><h2 className="m-0 text-lg">{message.name}</h2><a className="mt-1 inline-flex items-center gap-2" href={`mailto:${message.email}`}><Mail className="size-4" />{message.email}</a>{message.phone ? <a className="mt-1 flex items-center gap-2" href={`tel:${message.phone}`}><Phone className="size-4" />{message.phone}</a> : null}</div><Button type="button" size="sm" variant="outline" onClick={() => void toggleRead()}>{message.read_at ? <Mail /> : <MailOpen />}{message.read_at ? 'Маркирай като непрочетено' : 'Маркирай като прочетено'}</Button></header><section className="grid min-h-72 content-start gap-4 border border-border bg-muted/30 p-4" aria-label="Разговор"><article className="mr-auto max-w-[85%]"><div className="rounded-[14px] rounded-bl-[3px] border border-border bg-card px-4 py-3 shadow-sm"><p className="m-0 whitespace-pre-wrap leading-7">{message.message}</p></div><p className="m-0 mt-1 text-xs text-muted-foreground">{message.name} · {formatDateTime(message.created_at)}</p></article>{(message.replies ?? []).map((item) => { const customer = item.sender_type === 'customer'; return <article key={item.id} className={`${customer ? 'mr-auto' : 'ml-auto text-right'} max-w-[85%]`}><div className={customer ? 'rounded-[14px] rounded-bl-[3px] border border-border bg-card px-4 py-3 shadow-sm' : 'rounded-[14px] rounded-br-[3px] bg-primary px-4 py-3 text-left text-primary-foreground shadow-sm'}><p className="m-0 whitespace-pre-wrap leading-7">{item.body}</p></div><p className="m-0 mt-1 text-xs text-muted-foreground">{item.sender} · {formatDateTime(item.created_at)}{customer ? '' : ` · ${item.email_sent ? 'изпратен по имейл' : 'имейлът е неуспешен'}`}</p></article>; })}</section><form className="grid gap-2 border border-border bg-card p-4" onSubmit={(event) => { event.preventDefault(); void sendReply(); }}><label htmlFor="message-reply" className="font-bold">Вашият отговор</label><textarea id="message-reply" className="min-h-32 w-full resize-y border border-input bg-field p-3 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50" value={reply} maxLength={10000} placeholder={`Отговор до ${message.name}…`} disabled={sending} onChange={(event) => setReply(event.target.value)} /><div className="flex items-center justify-between gap-3"><span className="text-xs text-muted-foreground">Отговорът се записва в разговора и се изпраща на {message.email}.</span><Button type="submit" disabled={sending || reply.trim().length < 2}><Send />{sending ? 'Изпращане…' : 'Изпрати отговора'}</Button></div></form></div></div>;
}
