import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, FolderOpen, FolderTree, Save, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import {
  createCategory,
  getCategory,
  listCategoryTree,
  updateCategory,
  type AdminCategory,
  type CategoryTreeNode,
} from '@/api/categories';
import type { MediaFile } from '@/api/media';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Switch } from '@/components/ui/switch';
import { MediaPickerDialog } from '@/features/media/MediaPickerDialog';
import { categoryDescendantIds, flattenCategoryTree } from '@/features/categories/categoryTree';
import { PageTreeSelect } from '@/features/pages/PageTreeSelect';
import { toast, toastError } from '@/lib/toast';

type FormState = {
  name: string;
  slug: string;
  parent_id: string;
  is_active: boolean;
  sort_order: string;
};

const emptyForm: FormState = {
  name: '',
  slug: '',
  parent_id: 'none',
  is_active: true,
  sort_order: '0',
};

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

export function CategoryFormPage() {
  const { id } = useParams();
  const isNew = id === undefined;
  const categoryId = id && /^\d+$/.test(id) ? Number(id) : null;
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [tree, setTree] = useState<CategoryTreeNode[]>([]);
  const [media, setMedia] = useState<MediaFile | null>(null);
  const [mediaFileId, setMediaFileId] = useState<number | null>(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [deleted, setDeleted] = useState(false);
  const [busy, setBusy] = useState(!isNew);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const title = isNew ? 'Нова категория' : 'Редакция';
  useGlobalLoading(busy);

  useEffect(() => {
    if (isNew || categoryId === null) {
      return;
    }

    const loadedId = categoryId;
    let cancelled = false;

    async function load() {
      setBusy(true);
      try {
        const response = await getCategory(token, loadedId);
        if (cancelled) {
          return;
        }
        applyCategory(response.data.category);
      } catch (error) {
        if (!cancelled) {
          setMessage(error instanceof ApiError ? error.message : 'Категорията не можа да се зареди.');
          toastError(error, 'Категорията не можа да се зареди.');
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
  }, [isNew, categoryId, token]);

  useEffect(() => {
    let cancelled = false;

    async function loadTree() {
      try {
        const response = await listCategoryTree(token);
        if (!cancelled) {
          setTree(response.data.categories);
        }
      } catch (error) {
        if (!cancelled) {
          toastError(error, 'Списъкът с категории не можа да се зареди.');
        }
      }
    }

    void loadTree();

    return () => {
      cancelled = true;
    };
  }, [token]);

  function applyCategory(category: AdminCategory) {
    setForm({
      name: category.name,
      slug: category.slug,
      parent_id: category.parent_id ? String(category.parent_id) : 'none',
      is_active: category.is_active,
      sort_order: String(category.sort_order),
    });
    setMediaFileId(category.media_file_id);
    setMedia(category.media);
    setDeleted(Boolean(category.deleted_at));
  }

  function patchForm<K extends keyof FormState>(key: K, value: FormState[K]) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  function toPayload() {
    return {
      name: form.name.trim(),
      slug: form.slug.trim() === '' ? null : form.slug.trim(),
      parent_id: form.parent_id === 'none' ? null : Number(form.parent_id),
      media_file_id: mediaFileId,
      is_active: form.is_active,
      sort_order: Math.max(0, Number.parseInt(form.sort_order, 10) || 0),
    };
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const payload = toPayload();
      if (isNew) {
        const response = await createCategory(token, payload);
        toast.success(response.message || 'Категорията е създадена.');
        navigate(`/categories/${response.data.category.id}`, { replace: true });
        return;
      }

      if (categoryId !== null) {
        const response = await updateCategory(token, categoryId, payload);
        applyCategory(response.data.category);
        toast.success(response.message || 'Категорията е обновена.');
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
  const imageAlt = media?.alt?.trim() || '';

  return (
    <div className="page min-w-0">
      <PageHeader
        title={isNew ? title : form.name.trim() ? `Редакция · ${form.name}` : title}
        help="Име, родител и опционално изображение. Празен адрес се генерира от името."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Категории', to: routes.categories },
          { label: isNew ? title : form.name.trim() || title },
        ]}
        actions={
          <Button asChild variant="outline">
            <Link to={routes.categories}>
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
          Изтрита категория не се редактира. Възстановете я от списъка.
        </p>
      ) : null}

      {canEdit ? (
        <form className="flex min-w-0 max-w-full flex-col gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
          <CollapsibleSection
            title="Категория"
            icon={FolderTree}
            persistKey="category.general"
            help="Име, адрес в каталога и място в дървото."
          >
            <div className="form-grid">
              <Field
                id="name"
                label="Име"
                help="Името на категорията в панела и в каталога."
                value={form.name}
                onChange={(event) => patchForm('name', event.target.value)}
                error={errors.name}
              />
              <Field
                id="slug"
                label="Адрес (slug)"
                help="Оставете празно, за да се генерира от името."
                value={form.slug}
                onChange={(event) => patchForm('slug', event.target.value)}
                error={errors.slug}
              />
              <PageTreeSelect
                id="parent_id"
                label="Родител"
                help="Категорията се влага под избраната. Тиретата показват нивото. „Няма“ е първо ниво."
                value={form.parent_id}
                options={flattenCategoryTree(
                  tree,
                  categoryId === null ? [] : categoryDescendantIds(tree, categoryId)
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
                help="Неактивна категория е скрита от каталога."
                checked={form.is_active}
                onCheckedChange={(checked) => patchForm('is_active', checked)}
              />
            </div>
            <div className="field">
              <LabelWithHelp
                label="Изображение"
                help="По избор. JPEG, PNG или WebP от медията."
              />
              <div className="mt-2 flex flex-wrap items-start gap-3">
                {media ? (
                  <figure className="m-0">
                    <img
                      src={media.url}
                      alt={imageAlt}
                      className="size-16 shrink-0 rounded-[6px] border border-border object-cover"
                    />
                    {imageAlt ? (
                      <figcaption className="mt-1 max-w-16 truncate text-xs text-muted-foreground">{imageAlt}</figcaption>
                    ) : null}
                  </figure>
                ) : null}
                <div className="grid min-w-0 gap-2">
                  {media ? (
                    <p className="m-0 truncate font-medium">{media.original_name}</p>
                  ) : (
                    <p className="m-0 text-muted-foreground">Няма избрано изображение.</p>
                  )}
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={() => setPickerOpen(true)}>
                      <FolderOpen />
                      {media ? 'Смени' : 'Избери'}
                    </Button>
                    {media ? (
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          setMedia(null);
                          setMediaFileId(null);
                        }}
                      >
                        <X />
                        Премахни
                      </Button>
                    ) : null}
                  </div>
                </div>
              </div>
              {errors.media_file_id ? (
                <p className="field-error" role="alert">
                  {errors.media_file_id}
                </p>
              ) : null}
            </div>
          </CollapsibleSection>

          <div className="row-actions">
            <Button type="submit" disabled={busy}>
              <Save />
              {busy ? 'Запис…' : 'Запази'}
            </Button>
            <Button asChild variant="outline">
              <Link to={routes.categories}>
                <X />
                Отказ
              </Link>
            </Button>
          </div>
        </form>
      ) : null}

      {pickerOpen ? (
        <MediaPickerDialog
          token={token}
          title="Избор на изображение"
          onSelect={(files) => {
            const file = files[0];
            if (file) {
              setMedia(file);
              setMediaFileId(file.id);
            }
            setPickerOpen(false);
          }}
          onClose={() => setPickerOpen(false)}
        />
      ) : null}
    </div>
  );
}
