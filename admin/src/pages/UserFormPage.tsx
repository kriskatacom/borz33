import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '@/api/client';
import { createUser, getUser, updateUser } from '@/api/users';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { SelectField } from '@/components/ui/SelectField';

type FormState = {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  role: string;
  is_active: boolean;
  password: string;
  password_confirmation: string;
};

const emptyForm: FormState = {
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: 'customer',
  is_active: true,
  password: '',
  password_confirmation: '',
};

export function UserFormPage() {
  const { id } = useParams();
  const isNew = id === undefined;
  const userId = id && /^\d+$/.test(id) ? Number(id) : null;
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const currentId = useAppSelector((state) => state.auth.user?.id);
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [busy, setBusy] = useState(!isNew);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const isSelf = userId !== null && userId === currentId;

  useEffect(() => {
    if (isNew || userId === null) {
      return;
    }

    const loadedId = userId;
    let cancelled = false;

    async function load() {
      setBusy(true);
      try {
        const response = await getUser(token, loadedId);
        if (cancelled) {
          return;
        }
        const user = response.data.user;
        setForm({
          first_name: user.first_name,
          last_name: user.last_name,
          email: user.email,
          phone: user.phone ?? '',
          role: user.role,
          is_active: user.is_active,
          password: '',
          password_confirmation: '',
        });
      } catch (error) {
        if (!cancelled) {
          setMessage(error instanceof ApiError ? error.message : 'Потребителят не можа да се зареди.');
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
  }, [isNew, token, userId]);

  function patch<K extends keyof FormState>(key: K, value: FormState[K]) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setMessage(null);
    setErrors({});

    const payload = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      email: form.email.trim(),
      phone: form.phone.trim() === '' ? null : form.phone.trim(),
      role: form.role,
      is_active: form.is_active,
      ...(form.password
        ? { password: form.password, password_confirmation: form.password_confirmation }
        : isNew
          ? { password: form.password, password_confirmation: form.password_confirmation }
          : {}),
    };

    try {
      if (isNew) {
        await createUser(token, payload);
      } else if (userId !== null) {
        await updateUser(token, userId, payload);
      }
      navigate(routes.users, { replace: true });
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors(error.fieldErrors());
        setMessage(error.message);
      } else {
        setMessage('Записът не беше успешен.');
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="page">
      <header className="page-head">
        <p className="eyebrow">Потребители</p>
        <h1>{isNew ? 'Нов потребител' : 'Редакция на потребител'}</h1>
        <p className="muted">
          {isNew
            ? 'Профилът се създава потвърден и може да влиза веднага с паролата, която зададете.'
            : 'Оставете паролата празна, ако не искате да я сменяте.'}
        </p>
      </header>

      <form className="form panel" onSubmit={(event) => void onSubmit(event)} noValidate>
        <div className="form-grid">
          <Field
            id="first_name"
            label="Име"
            value={form.first_name}
            onChange={(event) => patch('first_name', event.target.value)}
            error={errors.first_name}
            required
          />
          <Field
            id="last_name"
            label="Фамилия"
            value={form.last_name}
            onChange={(event) => patch('last_name', event.target.value)}
            error={errors.last_name}
            required
          />
          <Field
            id="email"
            label="Имейл"
            type="email"
            autoComplete="off"
            value={form.email}
            onChange={(event) => patch('email', event.target.value)}
            error={errors.email}
            required
          />
          <Field
            id="phone"
            label="Телефон"
            value={form.phone}
            onChange={(event) => patch('phone', event.target.value)}
            error={errors.phone}
          />
          <SelectField
            id="role"
            label="Роля"
            value={form.role}
            disabled={isSelf}
            onChange={(event) => patch('role', event.target.value)}
            error={errors.role}
          >
            <option value="customer">Клиент</option>
            <option value="admin">Администратор</option>
          </SelectField>
          <label className="check">
            <input
              type="checkbox"
              checked={form.is_active}
              disabled={isSelf}
              onChange={(event) => patch('is_active', event.target.checked)}
            />
            Активен профил
          </label>
          <Field
            id="password"
            label={isNew ? 'Парола' : 'Нова парола'}
            type="password"
            autoComplete="new-password"
            value={form.password}
            onChange={(event) => patch('password', event.target.value)}
            error={errors.password}
            hint={isNew ? 'Поне 8 символа.' : 'По желание. Поне 8 символа, ако сменяте паролата.'}
            required={isNew}
          />
          <Field
            id="password_confirmation"
            label="Потвърждение на паролата"
            type="password"
            autoComplete="new-password"
            value={form.password_confirmation}
            onChange={(event) => patch('password_confirmation', event.target.value)}
            error={errors.password_confirmation}
            required={isNew}
          />
        </div>

        {message ? (
          <p className="form-message is-error" role="alert">
            {message}
          </p>
        ) : null}

        <div className="row-actions">
          <Button type="submit" disabled={busy}>
            {busy ? 'Запис…' : 'Запази'}
          </Button>
          <Link className="btn btn-ghost" to={routes.users}>
            Назад
          </Link>
        </div>
      </form>
    </div>
  );
}
