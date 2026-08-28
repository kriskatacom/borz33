import { useState, type FormEvent } from 'react';
import { Mail } from 'lucide-react';
import { Link } from 'react-router-dom';
import { forgotAdminPassword } from '@/api/auth';
import { ApiError } from '@/api/client';
import { routes } from '@/app/constants';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { useGlobalLoading } from '@/components/loading-provider';
import { AuthLayout } from '@/layouts/AuthLayout';

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | undefined>();
  useGlobalLoading(busy);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setMessage(null);
    setError(undefined);

    try {
      const response = await forgotAdminPassword(email.trim());
      setMessage(response.message);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.fieldErrors().email);
        setMessage(caught.message);
      } else {
        setMessage('Заявката не беше успешна.');
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthLayout
      eyebrow="Админ панел"
      title="Забравена парола"
      footer={<Link to={routes.login}>Към входа</Link>}
    >
      <p className="muted">Ще изпратим линк само ако имейлът е на администраторски профил.</p>
      <form className="form" onSubmit={(event) => void onSubmit(event)} noValidate>
        <Field
          id="email"
          label="Имейл"
          type="email"
          autoComplete="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          error={error}
          required
        />
        {message ? (
          <p className="form-message" role="status">
            {message}
          </p>
        ) : null}
        <Button type="submit" disabled={busy}>
          <Mail />
          {busy ? 'Изпращане…' : 'Изпрати линк'}
        </Button>
      </form>
    </AuthLayout>
  );
}
