import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, Camera, KeyRound, Save, Shield, UserRound, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import { createUser, getUser, updateUser, type ManagedUser } from '@/api/users';
import { routes } from '@/app/constants';
import { useAppDispatch, useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { setCredentials, type AdminUser } from '@/features/auth/authSlice';
import { UserAvatarField } from '@/features/users/UserAvatarField';
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

type FormSectionId = 'profile' | 'access' | 'security';

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

const sectionFields: Record<FormSectionId, Array<keyof FormState>> = {
  profile: ['first_name', 'last_name', 'email', 'phone'],
  access: ['role'],
  security: ['password', 'password_confirmation'],
};

function sectionForErrors(errors: Record<string, string>): FormSectionId | null {
  const sections: FormSectionId[] = ['profile', 'access', 'security'];

  return sections.find((section) => sectionFields[section].some((field) => errors[field])) ?? null;
}

function toSessionUser(user: ManagedUser): AdminUser {
  return {
    id: user.id,
    first_name: user.first_name,
    last_name: user.last_name,
    email: user.email,
    phone: user.phone,
    role: user.role,
    is_active: user.is_active,
    email_verified_at: user.email_verified_at,
    avatar_url: user.avatar_url ?? null,
  };
}

function SectionActions({ busy }: { busy: boolean }) {
  return (
    <div className="row-actions">
      <Button type="submit" disabled={busy}>
        <Save />
        {busy ? 'Запис…' : 'Запази секцията'}
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

function SectionForm({
  children,
  busy,
  onSubmit,
}: {
  children: ReactNode;
  busy: boolean;
  onSubmit: (event: FormEvent) => void;
}) {
  return (
    <form className="grid gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
      <div className="form-grid">{children}</div>
      <SectionActions busy={busy} />
    </form>
  );
}

export function UserFormPage() {
  const { id } = useParams();
  const isNew = id === undefined;
  const userId = id && /^\d+$/.test(id) ? Number(id) : null;
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const currentId = useAppSelector((state) => state.auth.user?.id);
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null);
  const [busy, setBusy] = useState(!isNew);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const isSelf = userId !== null && userId === currentId;
  const title = isNew ? 'Нов потребител' : 'Редакция';
  const errorSection = sectionForErrors(errors);
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
        setAvatarUrl(user.avatar_url ?? null);
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

  function applyAvatarUser(user: ManagedUser) {
    setAvatarUrl(user.avatar_url ?? null);
    if (user.id === currentId && token) {
      dispatch(setCredentials({ token, user: toSessionUser(user) }));
    }
  }

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
        navigate(`/users/${response.data.user.id}`, { replace: true });
      } else if (userId !== null) {
        const response = await updateUser(token, userId, payload);
        toast.success(response.message || 'Профилът е обновен.');
        navigate(routes.users, { replace: true });
      }
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors(error.fieldErrors());
      }
      toastError(error, 'Записът не беше успешен.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="page min-w-0">
      <PageHeader
        title={title}
        help={
          isNew
            ? 'Профилът се създава потвърден и може да влиза веднага с паролата, която зададете. След записа можете да добавите снимка. Записът от която и да е секция праща всички полета.'
            : 'Променете данните на профила. Снимката се качва отделно. Паролата се сменя само ако попълните полетата в секцията Парола. Записът от която и да е секция праща всички полета.'
        }
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Потребители', to: routes.users },
          { label: title },
        ]}
        actions={
          <Button asChild variant="outline">
            <Link to={routes.users}>
              <ArrowLeft />
              Към списъка
            </Link>
          </Button>
        }
      />

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      <div className="flex min-w-0 max-w-full flex-col gap-3">
        <CollapsibleSection
          title="Профил"
          icon={UserRound}
          persistKey="user.profile"
          forceOpen={errorSection === 'profile'}
          help="Име, имейл и телефон. Имейлът е уникален и се използва за вход."
        >
          <SectionForm busy={busy} onSubmit={onSubmit}>
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
          </SectionForm>
        </CollapsibleSection>

        <CollapsibleSection
          title="Снимка"
          icon={Camera}
          persistKey="user.avatar"
          help={
            isNew
              ? 'Първо запишете профила. След това се отваря тази страница и можете да качите снимка.'
              : 'Качете или сменете профилната снимка. JPEG, PNG или WebP, до 8 MB.'
          }
        >
          <UserAvatarField
            userId={userId}
            avatarUrl={avatarUrl}
            displayName={`${form.first_name} ${form.last_name}`}
            token={token}
            onUserChange={applyAvatarUser}
          />
        </CollapsibleSection>

        <CollapsibleSection
          title="Достъп"
          icon={Shield}
          persistKey="user.access"
          forceOpen={errorSection === 'access'}
          help="Роля и дали профилът може да влиза. Администратор има достъп до панела."
        >
          <SectionForm busy={busy} onSubmit={onSubmit}>
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
          </SectionForm>
        </CollapsibleSection>

        <CollapsibleSection
          title="Парола"
          icon={KeyRound}
          persistKey="user.security"
          forceOpen={errorSection === 'security'}
          help={
            isNew
              ? 'Задайте парола, с която потребителят влиза веднага след създаването.'
              : 'Попълнете само ако искате да смените паролата. Празните полета оставят старата.'
          }
        >
          <SectionForm busy={busy} onSubmit={onSubmit}>
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
          </SectionForm>
        </CollapsibleSection>
      </div>
    </div>
  );
}
