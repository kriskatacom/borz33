import { useMemo, useState, type FormEvent } from 'react';
import { Save } from 'lucide-react';
import { Link, useSearchParams } from 'react-router-dom';
import { resetAdminPassword } from '@/api/auth';
import { ApiError } from '@/api/client';
import { routes } from '@/app/constants';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { useGlobalLoading } from '@/components/loading-provider';
import { AuthLayout } from '@/layouts/AuthLayout';
import { toast, toastError } from '@/lib/toast';

export function ResetPasswordPage() {
  const [params] = useSearchParams();
  const email = params.get('email') ?? '';
  const token = params.get('token') ?? '';
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  useGlobalLoading(busy);
  const invalidLink = useMemo(() => email === '' || token.length < 32, [email, token]);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setMessage(null);
    setErrors({});

    try {
      const response = await resetAdminPassword({
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });
      setDone(true);
      setMessage(response.message);
      toast.success(response.message);
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors(error.fieldErrors());
      }
      toastError(error, 'Паролата не можа да се обнови.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthLayout
      eyebrow="Админ панел"
      title="Нова парола"
      footer={<Link to={routes.login}>Към входа</Link>}
    >
      {invalidLink ? (
        <p className="muted">Линкът е непълен или невалиден. Поискайте нов от страницата за забравена парола.</p>
      ) : done ? (
        <p className="muted">{message}</p>
      ) : (
        <>
          <p className="muted">Изберете нова парола за {email}.</p>
          <form className="form" onSubmit={(event) => void onSubmit(event)} noValidate>
            <Field
              id="password"
              label="Нова парола"
              type="password"
              autoComplete="new-password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              error={errors.password}
              help="Поне 8 символа. Използвайте я само за този админ профил."
              placeholder="Поне 8 символа"
              required
            />
            <Field
              id="password_confirmation"
              label="Потвърждение"
              type="password"
              autoComplete="new-password"
              value={passwordConfirmation}
              onChange={(event) => setPasswordConfirmation(event.target.value)}
              error={errors.password_confirmation}
              help="Трябва да съвпада с новата парола."
              placeholder="Повторете паролата"
              required
            />
            <Button type="submit" disabled={busy}>
              <Save />
              {busy ? 'Запис…' : 'Запази паролата'}
            </Button>
          </form>
        </>
      )}
    </AuthLayout>
  );
}
