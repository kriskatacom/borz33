import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { loginAdmin, resendAdminDeviceCode, verifyAdminDevice } from '@/api/auth';
import { ApiError } from '@/api/client';
import { routes } from '@/app/constants';
import { useAppDispatch } from '@/app/hooks';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { OtpInputs } from '@/components/ui/OtpInputs';
import { setCredentials, type AdminUser } from '@/features/auth/authSlice';
import { AuthLayout } from '@/layouts/AuthLayout';
import { deviceName, getOrCreateDeviceUuid } from '@/lib/device';

export function LoginPage() {
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [code, setCode] = useState('');
  const [step, setStep] = useState<'credentials' | 'device'>('credentials');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [isError, setIsError] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  function deviceFields() {
    return {
      email: email.trim(),
      device_uuid: getOrCreateDeviceUuid(),
      device_name: deviceName(),
    };
  }

  function applySession(token: string | undefined, user: AdminUser) {
    if (!token || user.role !== 'admin') {
      throw new ApiError('Този профил няма достъп до админ панела.', 403);
    }

    dispatch(setCredentials({ token, user }));
    navigate(routes.home, { replace: true });
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setMessage(null);
    setIsError(false);
    setErrors({});

    try {
      if (step === 'credentials') {
        const response = await loginAdmin({ ...deviceFields(), password });

        if (response.data.requires_device_verification) {
          setStep('device');
          setMessage(response.message);
          setIsError(false);
          return;
        }

        applySession(response.data.token, response.data.user);
        return;
      }

      const response = await verifyAdminDevice({ ...deviceFields(), code });
      applySession(response.data.token, response.data.user);
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors(error.fieldErrors());
        setMessage(error.message);
        setIsError(true);
      } else {
        setMessage('Неуспешен вход. Опитайте отново.');
        setIsError(true);
      }
    } finally {
      setBusy(false);
    }
  }

  async function onResend() {
    setBusy(true);
    setMessage(null);

    try {
      const response = await resendAdminDeviceCode(deviceFields());
      setMessage(response.message);
      setIsError(false);
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : 'Кодът не можа да се изпрати отново.');
      setIsError(true);
    } finally {
      setBusy(false);
    }
  }

  return (
    <AuthLayout
      eyebrow="Админ панел"
      title={step === 'credentials' ? 'Вход' : 'Потвърдете устройството'}
      footer={
        step === 'credentials' ? (
          <Link to={routes.forgotPassword}>Забравена парола</Link>
        ) : (
          <button type="button" className="link-btn" onClick={() => setStep('credentials')}>
            Назад към входа
          </button>
        )
      }
    >
      <p className="muted">
        {step === 'credentials'
          ? 'Влезте с администраторския имейл. Регистрация в този панел няма.'
          : 'Изпратихме 6-цифрен код на имейла. Въведете го, за да доверим това устройство.'}
      </p>

      <form className="form" onSubmit={(event) => void onSubmit(event)} noValidate>
        {step === 'credentials' ? (
          <>
            <Field
              id="email"
              label="Имейл"
              type="email"
              autoComplete="username"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              error={errors.email}
              required
            />
            <Field
              id="password"
              label="Парола"
              type={showPassword ? 'text' : 'password'}
              autoComplete="current-password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              error={errors.password}
              required
              trailing={
                <button type="button" className="field-action" onClick={() => setShowPassword((value) => !value)}>
                  {showPassword ? 'Скрий' : 'Покажи'}
                </button>
              }
            />
          </>
        ) : (
          <OtpInputs value={code} onChange={setCode} disabled={busy} />
        )}

        {message ? (
          <p className={`form-message${isError ? ' is-error' : ''}`} role="status">
            {message}
          </p>
        ) : null}

        <Button type="submit" disabled={busy || (step === 'device' && code.length !== 6)}>
          {busy ? 'Моля, изчакайте…' : step === 'credentials' ? 'Вход' : 'Потвърди'}
        </Button>

        {step === 'device' ? (
          <Button type="button" variant="ghost" disabled={busy} onClick={() => void onResend()}>
            Изпрати нов код
          </Button>
        ) : null}
      </form>
    </AuthLayout>
  );
}
