import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
  ArrowLeft,
  ChevronDown,
  ChevronUp,
  File,
  FileText,
  Film,
  FolderOpen,
  List,
  Music,
  Plus,
  Save,
  Trash2,
  X,
} from 'lucide-react';
import { ApiError } from '@/api/client';
import type { MediaFile } from '@/api/media';
import {
  createPage,
  getPage,
  listPageTree,
  updatePage,
  type AdminPage,
  type AdminPageField,
  type PageFieldPayload,
  type PageFieldType,
} from '@/api/pages';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { MediaPickerDialog } from '@/features/media/MediaPickerDialog';
import { mediaKindLabel } from '@/features/media/mediaFile';
import { PageTreeSelect } from '@/features/pages/PageTreeSelect';
import { flattenPageTree, pageDescendantIds, type PageTreeNode } from '@/features/pages/pageTree';
import { toast, toastError } from '@/lib/toast';

type FormState = {
  title: string;
  slug: string;
  parent_id: string;
  is_active: boolean;
  sort_order: string;
  meta_title: string;
  meta_description: string;
};

type FieldDraft = {
  key: string;
  id?: number;
  name: string;
  slug: string;
  field_type: PageFieldType;
  value: string;
  media_file_id: number | null;
  media: MediaFile | null;
  is_required: boolean;
};

const emptyForm: FormState = {
  title: '',
  slug: '',
  parent_id: 'none',
  is_active: true,
  sort_order: '0',
  meta_title: '',
  meta_description: '',
};

let draftSeq = 0;
function nextKey(): string {
  draftSeq += 1;
  return `draft-${draftSeq}`;
}

function asFieldType(value: string): PageFieldType {
  if (value === 'textarea' || value === 'file') {
    return value;
  }

  return 'text';
}

function mapFields(rows: AdminPageField[]): FieldDraft[] {
  return rows.map((row) => ({
    key: `f-${row.id}`,
    id: row.id,
    name: row.name,
    slug: row.slug,
    field_type: asFieldType(row.field_type),
    value: row.value ?? '',
    media_file_id: row.media_file_id,
    media: row.media,
    is_required: row.is_required,
  }));
}

function emptyField(): FieldDraft {
  return {
    key: nextKey(),
    name: '',
    slug: '',
    field_type: 'text',
    value: '',
    media_file_id: null,
    media: null,
    is_required: false,
  };
}

function fieldError(errors: Record<string, string>, key: string): string | undefined {
  return errors[key];
}

function fieldTypeLabel(type: PageFieldType): string {
  if (type === 'textarea') {
    return 'Дълъг текст';
  }

  if (type === 'file') {
    return 'Файл';
  }

  return 'Кратък текст';
}

function KindIcon({ kind }: { kind: string }) {
  const className = 'size-5 text-muted-foreground';

  if (kind === 'video') {
    return <Film className={className} aria-hidden />;
  }

  if (kind === 'audio') {
    return <Music className={className} aria-hidden />;
  }

  if (kind === 'document') {
    return <FileText className={className} aria-hidden />;
  }

  return <File className={className} aria-hidden />;
}

function SwitchField({
  id,
  label,
  help,
  checked,
  onCheckedChange,
}: {
  id: string;
  label: string;
  help: string;
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
}) {
  return (
    <div className="field">
      <LabelWithHelp htmlFor={id} label={label} help={help} />
      <div className="flex min-h-12 items-center">
        <Switch id={id} checked={checked} onCheckedChange={onCheckedChange} />
      </div>
    </div>
  );
}

function FieldFilePreview({ file }: { file: MediaFile }) {
  if (file.kind === 'image') {
    return (
      <img
        src={file.url}
        alt={file.alt || file.original_name}
        className="size-16 shrink-0 rounded-[6px] border border-border object-cover"
      />
    );
  }

  return (
    <div className="flex size-16 shrink-0 flex-col items-center justify-center rounded-[6px] border border-border bg-muted">
      <KindIcon kind={file.kind} />
    </div>
  );
}

export function PageFormPage() {
  const { id } = useParams();
  const isNew = id === undefined;
  const pageId = id && /^\d+$/.test(id) ? Number(id) : null;
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [tree, setTree] = useState<PageTreeNode[]>([]);
  const [fields, setFields] = useState<FieldDraft[]>([]);
  const [deleted, setDeleted] = useState(false);
  const [busy, setBusy] = useState(!isNew);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [pickerIndex, setPickerIndex] = useState<number | null>(null);
  const title = isNew ? 'Нова страница' : 'Редакция';
  useGlobalLoading(busy);

  useEffect(() => {
    if (isNew || pageId === null) {
      return;
    }

    const loadedId = pageId;
    let cancelled = false;

    async function load() {
      setBusy(true);
      try {
        const response = await getPage(token, loadedId);
        if (cancelled) {
          return;
        }
        const page = response.data.page;
        applyPage(page);
      } catch (error) {
        if (!cancelled) {
          setMessage(error instanceof ApiError ? error.message : 'Страницата не можа да се зареди.');
          toastError(error, 'Страницата не можа да се зареди.');
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
  }, [isNew, pageId, token]);

  useEffect(() => {
    let cancelled = false;

    async function loadTree() {
      try {
        const response = await listPageTree(token);
        if (!cancelled) {
          setTree(response.data.pages);
        }
      } catch (error) {
        if (!cancelled) {
          toastError(error, 'Списъкът със страници не можа да се зареди.');
        }
      }
    }

    void loadTree();

    return () => {
      cancelled = true;
    };
  }, [token]);

  function applyPage(page: AdminPage) {
    setForm({
      title: page.title,
      slug: page.slug,
      parent_id: page.parent_id ? String(page.parent_id) : 'none',
      is_active: page.is_active,
      sort_order: String(page.sort_order),
      meta_title: page.meta_title ?? '',
      meta_description: page.meta_description ?? '',
    });
    setFields(mapFields(page.fields));
    setDeleted(Boolean(page.deleted_at));
  }

  function patchForm<K extends keyof FormState>(key: K, value: FormState[K]) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  function patchField(index: number, patch: Partial<FieldDraft>) {
    setFields((current) => current.map((field, item) => (item === index ? { ...field, ...patch } : field)));
  }

  function moveField(index: number, direction: -1 | 1) {
    setFields((current) => {
      const next = index + direction;
      if (next < 0 || next >= current.length) {
        return current;
      }
      const copy = [...current];
      const [row] = copy.splice(index, 1);
      copy.splice(next, 0, row);
      return copy;
    });
  }

  function toPayload() {
    return {
      title: form.title.trim(),
      slug: form.slug.trim() === '' ? null : form.slug.trim(),
      parent_id: form.parent_id === 'none' ? null : Number(form.parent_id),
      is_active: form.is_active,
      sort_order: Math.max(0, Number.parseInt(form.sort_order, 10) || 0),
      meta_title: form.meta_title.trim() === '' ? null : form.meta_title.trim(),
      meta_description: form.meta_description.trim() === '' ? null : form.meta_description.trim(),
      fields: fields.map((field, index) => {
        const type = field.field_type;
        const isFile = type === 'file';

        return {
          ...(field.id ? { id: field.id } : {}),
          name: field.name.trim(),
          slug: field.slug.trim() === '' ? null : field.slug.trim(),
          field_type: type,
          value: isFile ? null : field.value.trim() === '' ? null : field.value,
          media_file_id: isFile ? field.media_file_id : null,
          is_required: field.is_required,
          sort_order: index,
        };
      }),
    };
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const payload = toPayload();
      if (isNew) {
        const response = await createPage(token, payload);
        toast.success(response.message || 'Страницата е създадена.');
        navigate(`/pages/${response.data.page.id}`, { replace: true });
        return;
      }

      if (pageId !== null) {
        const response = await updatePage(token, pageId, payload);
        applyPage(response.data.page);
        toast.success(response.message || 'Страницата е обновена.');
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

  const canEdit = isNew || !deleted;

  return (
    <div className="page min-w-0">
      <PageHeader
        title={isNew ? title : form.title.trim() ? `Редакция · ${form.title}` : title}
        help="Заглавие, адрес и персонални полета се записват заедно. За файлово поле изберете файл от медията."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Страници', to: routes.pages },
          { label: isNew ? title : form.title.trim() || title },
        ]}
        actions={
          <Button asChild variant="outline">
            <Link to={routes.pages}>
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

      {deleted ? (
        <p className="form-message is-error" role="alert">
          Изтрита страница не се редактира. Възстановете я от списъка.
        </p>
      ) : null}

      {canEdit ? (
        <form className="flex min-w-0 max-w-full flex-col gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
          <CollapsibleSection
            title="Страница"
            icon={FileText}
            persistKey="page.general"
            help="Заглавие, адрес в сайта и SEO. Празен адрес се генерира от заглавието."
          >
            <div className="form-grid">
              <Field
                id="title"
                label="Заглавие"
                help="Името на страницата в панела и в сайта."
                value={form.title}
                onChange={(event) => patchForm('title', event.target.value)}
                error={errors.title}
              />
              <Field
                id="slug"
                label="Адрес (slug)"
                help="Оставете празно, за да се генерира от заглавието."
                value={form.slug}
                onChange={(event) => patchForm('slug', event.target.value)}
                error={errors.slug}
              />
              <PageTreeSelect
                id="parent_id"
                label="Родител"
                help="Страницата се влага под избраната. Тиретата показват нивото в дървото. „Няма“ е първо ниво."
                value={form.parent_id}
                options={flattenPageTree(
                  tree,
                  pageId === null ? [] : pageDescendantIds(tree, pageId)
                )}
                extra={[{ value: 'none', label: 'Няма' }]}
                error={errors.parent_id}
                onValueChange={(value) => patchForm('parent_id', value)}
              />
              <Field
                id="sort_order"
                label="Ред"
                type="number"
                min="0"
                help="По-малък номер е по-нагоре в списъка."
                value={form.sort_order}
                onChange={(event) => patchForm('sort_order', event.target.value)}
                error={errors.sort_order}
              />
              <SwitchField
                id="is_active"
                label="Активна"
                help="Неактивна страница е скрита от публичния сайт."
                checked={form.is_active}
                onCheckedChange={(checked) => patchForm('is_active', checked)}
              />
              <Field
                id="meta_title"
                label="SEO заглавие"
                help="Ако е празно, може да се ползва заглавието на страницата."
                value={form.meta_title}
                onChange={(event) => patchForm('meta_title', event.target.value)}
                error={errors.meta_title}
              />
            </div>
            <Field
              id="meta_description"
              label="SEO описание"
              multiline
              rows={3}
              help="Кратък текст за търсачките."
              value={form.meta_description}
              onChange={(event) => patchForm('meta_description', event.target.value)}
              error={errors.meta_description}
            />
          </CollapsibleSection>

          <CollapsibleSection
            title="Полета"
            icon={List}
            persistKey="page.fields"
            help="Текст или файл към страницата. Редът тук е редът при показване."
          >
            {fields.length === 0 ? (
              <p className="m-0 text-muted-foreground">Няма полета. Добавете текст, дълъг текст или файл.</p>
            ) : null}
            {errors.fields ? (
              <p className="form-message is-error" role="alert">
                {errors.fields}
              </p>
            ) : null}
            <div className="grid gap-3">
              {fields.map((field, index) => (
                <CollapsibleSection
                  key={field.key}
                  heading="h3"
                  persistKey={field.id ? `page-field:${field.id}` : undefined}
                  title={
                    <span className="flex min-w-0 flex-wrap items-center gap-2">
                      <span className="truncate">{field.name.trim() || 'Ново поле'}</span>
                      <span className="truncate font-medium tracking-normal text-muted-foreground">
                        {fieldTypeLabel(field.field_type)}
                      </span>
                    </span>
                  }
                  actions={
                    <div className="flex items-center gap-1">
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Нагоре"
                        disabled={index === 0}
                        onClick={() => moveField(index, -1)}
                      >
                        <ChevronUp />
                      </Button>
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Надолу"
                        disabled={index === fields.length - 1}
                        onClick={() => moveField(index, 1)}
                      >
                        <ChevronDown />
                      </Button>
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Премахни поле"
                        onClick={() => setFields((current) => current.filter((_, item) => item !== index))}
                      >
                        <Trash2 />
                      </Button>
                    </div>
                  }
                >
                  <div className="grid gap-3">
                    <div className="grid gap-3 sm:grid-cols-2">
                    <Field
                      id={`${field.key}-name`}
                      label="Име"
                      help="Етикетът на полето, напр. Въведение или Банер."
                      value={field.name}
                      onChange={(event) => patchField(index, { name: event.target.value })}
                      error={fieldError(errors, `fields.${index}.name`)}
                    />
                    <Field
                      id={`${field.key}-slug`}
                      label="Адрес"
                      help="Празно поле се попълва от името."
                      value={field.slug}
                      onChange={(event) => patchField(index, { slug: event.target.value })}
                      error={fieldError(errors, `fields.${index}.slug`)}
                    />
                    <div className="field">
                      <LabelWithHelp
                        htmlFor={`${field.key}-type`}
                        label="Тип"
                        help="Кратък текст е един ред. Дълъг текст е абзац. Файл се избира от медията."
                      />
                      <Select
                        value={field.field_type}
                        onValueChange={(value) => {
                          const type = asFieldType(value);
                          patchField(index, {
                            field_type: type,
                            ...(type === 'file'
                              ? { value: '' }
                              : { media_file_id: null, media: null }),
                          });
                        }}
                      >
                        <SelectTrigger id={`${field.key}-type`} className="w-full min-h-12 font-sans">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="text">Кратък текст</SelectItem>
                          <SelectItem value="textarea">Дълъг текст</SelectItem>
                          <SelectItem value="file">Файл</SelectItem>
                        </SelectContent>
                      </Select>
                      {fieldError(errors, `fields.${index}.field_type`) ? (
                        <p className="field-error" role="alert">
                          {fieldError(errors, `fields.${index}.field_type`)}
                        </p>
                      ) : null}
                    </div>
                    <SwitchField
                      id={`${field.key}-required`}
                      label="Задължително"
                      help="При запис трябва да има стойност или файл, според типа."
                      checked={field.is_required}
                      onCheckedChange={(checked) => patchField(index, { is_required: checked })}
                    />
                  </div>
                  {field.field_type === 'file' ? (
                    <div className="field">
                      <LabelWithHelp
                        label="Файл"
                        help="Избор от вече качени файлове в медията. Всички типове са позволени."
                      />
                      <div className="mt-2 flex flex-wrap items-start gap-3">
                        {field.media ? <FieldFilePreview file={field.media} /> : null}
                        <div className="grid min-w-0 gap-2">
                          {field.media ? (
                            <p className="m-0 truncate font-medium">
                              {field.media.original_name}
                              <span className="ml-2 font-normal text-muted-foreground">
                                {mediaKindLabel(field.media.kind)}
                              </span>
                            </p>
                          ) : (
                            <p className="m-0 text-muted-foreground">Няма избран файл.</p>
                          )}
                          <div className="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={() => setPickerIndex(index)}>
                              <FolderOpen />
                              {field.media ? 'Смени' : 'Избери'}
                            </Button>
                            {field.media ? (
                              <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => patchField(index, { media_file_id: null, media: null })}
                              >
                                <X />
                                Премахни
                              </Button>
                            ) : null}
                          </div>
                        </div>
                      </div>
                      {fieldError(errors, `fields.${index}.media_file_id`) ? (
                        <p className="field-error" role="alert">
                          {fieldError(errors, `fields.${index}.media_file_id`)}
                        </p>
                      ) : null}
                    </div>
                  ) : (
                    <Field
                      id={`${field.key}-value`}
                      label="Стойност"
                      multiline={field.field_type === 'textarea'}
                      rows={field.field_type === 'textarea' ? 5 : undefined}
                      help={
                        field.field_type === 'textarea'
                          ? 'Дълъг текст за съдържанието на страницата.'
                          : 'Кратък текст в едно поле.'
                      }
                      value={field.value}
                      onChange={(event) => patchField(index, { value: event.target.value })}
                      error={fieldError(errors, `fields.${index}.value`)}
                    />
                  )}
                  </div>
                </CollapsibleSection>
              ))}
            </div>
            <Button type="button" variant="outline" className="mt-3" onClick={() => setFields((current) => [...current, emptyField()])}>
              <Plus />
              Поле
            </Button>
          </CollapsibleSection>

          <div className="row-actions">
            <Button type="submit" disabled={busy}>
              <Save />
              {busy ? 'Запис…' : 'Запази'}
            </Button>
            <Button asChild variant="outline">
              <Link to={routes.pages}>
                <X />
                Отказ
              </Link>
            </Button>
          </div>
        </form>
      ) : null}

      {pickerIndex !== null ? (
        <MediaPickerDialog
          token={token}
          title="Избор на файл"
          allFiles
          onSelect={(files) => {
            const file = files[0];
            if (file) {
              patchField(pickerIndex, { media_file_id: file.id, media: file });
            }
            setPickerIndex(null);
          }}
          onClose={() => setPickerIndex(null)}
        />
      ) : null}
    </div>
  );
}
