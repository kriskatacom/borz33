import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, Save } from 'lucide-react';
import { ApiError } from '@/api/client';
import { createUser, getUser, updateUser } from '@/api/users';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { HelpHint, LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

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
  useGlobalLoading(busy);

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
        <h1 className="flex items-center gap-1.5">
          {isNew ? 'Нов потребител' : 'Редакция на потребител'}
          <HelpHint label={isNew ? 'Нов потребител' : 'Редакция на потребител'}>
            {isNew
              ? 'Профилът се създава потвърден и може да влиза веднага с паролата, която зададете.'
              : 'Променете данните на профила. Оставете паролата празна, ако не искате да я сменяте.'}
          </HelpHint>
        </h1>
      </header>

      <form className="form panel" onSubmit={(event) => void onSubmit(event)} noValidate>
        <div className="form-grid">
          <Field
            id="first_name"
            label="Име"
            help="Собствено име, с което потребителят се показва в панела."
            value={form.first_name}
            onChange={(event) => patch('first_name', event.target.value)}
            error={errors.first_name}
            required
          />
          <Field
            id="last_name"
            label="Фамилия"
            help="Фамилия, с която потребителят се показва в панела."
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
            help="Уникален адрес. С него потребителят влиза и получава съобщения."
            value={form.email}
            onChange={(event) => patch('email', event.target.value)}
            error={errors.email}
            required
          />
          <Field
            id="phone"
            label="Телефон"
            help="По желание. Използва се за връзка и за търсене в списъка."
            value={form.phone}
            onChange={(event) => patch('phone', event.target.value)}
            error={errors.phone}
          />
          <div className="field">
            <LabelWithHelp
              htmlFor="role"
              label="Роля"
              help={
                isSelf
                  ? 'Собствената роля не може да се смени от тук. Администратор има достъп до панела, клиент е профил за магазина.'
                  : 'Администратор има достъп до този панел. Клиент е профил за магазина.'
              }
            />
            <Select value={form.role} onValueChange={(value) => patch('role', value)} disabled={isSelf}>
              <SelectTrigger id="role" className="w-full min-h-12 font-sans" aria-invalid={errors.role ? true : undefined}>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="customer">Клиент</SelectItem>
                <SelectItem value="admin">Администратор</SelectItem>
              </SelectContent>
            </Select>
            {errors.role ? (
              <p id="role-error" className="field-error" role="alert">
                {errors.role}
              </p>
            ) : null}
          </div>
          <div className="check">
            <input
              id="is_active"
              type="checkbox"
              checked={form.is_active}
              disabled={isSelf}
              onChange={(event) => patch('is_active', event.target.checked)}
            />
            <label htmlFor="is_active">Активен профил</label>
            <HelpHint label="Активен профил">
              {isSelf
                ? 'Не можете да деактивирате собствения си профил. Неактивен потребител не може да влиза.'
                : 'Изключен профил не може да влиза, докато не го активирате отново.'}
            </HelpHint>
          </div>
          <Field
            id="password"
            label={isNew ? 'Парола' : 'Нова парола'}
            type="password"
            autoComplete="new-password"
            help={
              isNew
                ? 'Поне 8 символа. С тази парола потребителят влиза веднага след създаването.'
                : 'По желание. Поне 8 символа, ако сменяте паролата. Празно поле оставя старата парола.'
            }
            value={form.password}
            onChange={(event) => patch('password', event.target.value)}
            error={errors.password}
            required={isNew}
          />
          <Field
            id="password_confirmation"
            label="Потвърждение на паролата"
            type="password"
            autoComplete="new-password"
            help="Трябва да съвпада с паролата. При редакция се попълва само ако сменяте паролата."
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
            <Save />
            {busy ? 'Запис…' : 'Запази'}
          </Button>
          <Button asChild variant="outline">
            <Link to={routes.users}>
              <ArrowLeft />
              Назад
            </Link>
          </Button>
        </div>
      </form>
    </div>
  );
}
