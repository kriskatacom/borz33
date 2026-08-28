import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { KeyRound, Save, Shield, UserRound, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import { createUser, getUser, updateUser } from '@/api/users';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { FormSection } from '@/components/ui/form-section';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { toast, toastError } from '@/lib/toast';

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

type FormTab = 'profile' | 'access' | 'security';

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

const tabFields: Record<FormTab, Array<keyof FormState>> = {
  profile: ['first_name', 'last_name', 'email', 'phone'],
  access: ['role'],
  security: ['password', 'password_confirmation'],
};

function tabForErrors(errors: Record<string, string>): FormTab | null {
  const tabs: FormTab[] = ['profile', 'access', 'security'];

  return tabs.find((tab) => tabFields[tab].some((field) => errors[field])) ?? null;
}

function FormActions({ busy }: { busy: boolean }) {
  return (
    <div className="row-actions">
      <Button type="submit" disabled={busy}>
        <Save />
        {busy ? 'Запис…' : 'Запази'}
      </Button>
      <Button asChild variant="outline">
        <Link to={routes.users}>
          <X />
          Отказ
        </Link>
      </Button>
    </div>
  );
}

function TabForm({
  children,
  busy,
  onSubmit,
}: {
  children: ReactNode;
  busy: boolean;
  onSubmit: (event: FormEvent) => void;
}) {
  return (
    <form className="contents" onSubmit={(event) => void onSubmit(event)} noValidate>
      <FormSection className="grid gap-3">
        <div className="form-grid">{children}</div>
        <FormActions busy={busy} />
      </FormSection>
    </form>
  );
}

export function UserFormPage() {
  const { id } = useParams();
  const isNew = id === undefined;
  const userId = id && /^\d+$/.test(id) ? Number(id) : null;
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const currentId = useAppSelector((state) => state.auth.user?.id);
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [tab, setTab] = useState<FormTab>('profile');
  const [busy, setBusy] = useState(!isNew);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const isSelf = userId !== null && userId === currentId;
  const title = isNew ? 'Нов потребител' : 'Редакция';
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
          toastError(error, 'Потребителят не можа да се зареди.');
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
        const response = await createUser(token, payload);
        toast.success(response.message || 'Профилът е създаден.');
      } else if (userId !== null) {
        const response = await updateUser(token, userId, payload);
        toast.success(response.message || 'Профилът е обновен.');
      }
      navigate(routes.users, { replace: true });
    } catch (error) {
      if (error instanceof ApiError) {
        const fieldErrors = error.fieldErrors();
        setErrors(fieldErrors);
        const nextTab = tabForErrors(fieldErrors);
        if (nextTab) {
          setTab(nextTab);
        }
      }
      toastError(error, 'Записът не беше успешен.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="page">
      <PageHeader
        title={title}
        help={
          isNew
            ? 'Профилът се създава потвърден и може да влиза веднага с паролата, която зададете.'
            : 'Променете данните на профила. Паролата се сменя само ако попълните полетата в раздела Парола.'
        }
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Потребители', to: routes.users },
          { label: title },
        ]}
      />

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      <Tabs value={tab} onValueChange={(value) => setTab(value as FormTab)}>
        <TabsList>
          <TabsTrigger value="profile">
            <UserRound />
            Профил
          </TabsTrigger>
          <TabsTrigger value="access">
            <Shield />
            Достъп
          </TabsTrigger>
          <TabsTrigger value="security">
            <KeyRound />
            Парола
          </TabsTrigger>
        </TabsList>

        <TabsContent value="profile" className="grid gap-3">
          <TabForm busy={busy} onSubmit={onSubmit}>
            <Field
              id="first_name"
              label="Име"
              help="Собствено име, с което потребителят се показва в панела."
              placeholder="Иван"
              value={form.first_name}
              onChange={(event) => patch('first_name', event.target.value)}
              error={errors.first_name}
            />
            <Field
              id="last_name"
              label="Фамилия"
              help="Фамилия, с която потребителят се показва в панела."
              placeholder="Петров"
              value={form.last_name}
              onChange={(event) => patch('last_name', event.target.value)}
              error={errors.last_name}
            />
            <Field
              id="email"
              label="Имейл"
              type="email"
              autoComplete="off"
              help="Уникален адрес. С него потребителят влиза и получава съобщения."
              placeholder="ivan@example.com"
              value={form.email}
              onChange={(event) => patch('email', event.target.value)}
              error={errors.email}
            />
            <Field
              id="phone"
              label="Телефон"
              help="По желание. Използва се за връзка и за търсене в списъка."
              placeholder="+359 88 123 4567"
              value={form.phone}
              onChange={(event) => patch('phone', event.target.value)}
              error={errors.phone}
            />
          </TabForm>
        </TabsContent>

        <TabsContent value="access" className="grid gap-3">
          <TabForm busy={busy} onSubmit={onSubmit}>
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
            <div className="field">
              <LabelWithHelp
                htmlFor="is_active"
                label="Активен профил"
                help={
                  isSelf
                    ? 'Не можете да деактивирате собствения си профил. Неактивен потребител не може да влиза.'
                    : 'Изключен профил не може да влиза, докато не го активирате отново.'
                }
              />
              <div className="flex min-h-12 items-center">
                <Switch
                  id="is_active"
                  checked={form.is_active}
                  disabled={isSelf}
                  onCheckedChange={(checked) => patch('is_active', checked)}
                />
              </div>
            </div>
          </TabForm>
        </TabsContent>

        <TabsContent value="security" className="grid gap-3">
          <TabForm busy={busy} onSubmit={onSubmit}>
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
              placeholder="Поне 8 символа"
              value={form.password}
              onChange={(event) => patch('password', event.target.value)}
              error={errors.password}
            />
            <Field
              id="password_confirmation"
              label="Потвърждение на паролата"
              type="password"
              autoComplete="new-password"
              help="Трябва да съвпада с паролата. При редакция се попълва само ако сменяте паролата."
              placeholder="Повторете паролата"
              value={form.password_confirmation}
              onChange={(event) => patch('password_confirmation', event.target.value)}
              error={errors.password_confirmation}
            />
          </TabForm>
        </TabsContent>
      </Tabs>
    </div>
  );
}
