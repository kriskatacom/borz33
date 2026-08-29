import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, ChevronDown, ChevronUp, FolderOpen, Image, Plus, Save, Trash2, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import {
  createBanner,
  getBanner,
  updateBanner,
  type AdminBanner,
  type BannerButton,
} from '@/api/banners';
import type { MediaFile } from '@/api/media';
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
import { TextEditor } from '@/components/ui/TextEditor';
import { BANNER_LAYOUTS, isBannerLayout } from '@/features/banners/bannerLayouts';
import { MediaPickerDialog } from '@/features/media/MediaPickerDialog';
import { toast, toastError } from '@/lib/toast';

type FormState = {
  title: string;
  slug: string;
  text: string;
  layout: string;
  is_active: boolean;
  sort_order: string;
};

type ButtonDraft = {
  key: string;
  id?: number;
  label: string;
  url: string;
  open_in_new_tab: boolean;
};

const emptyForm: FormState = {
  title: '',
  slug: '',
  text: '',
  layout: 'split',
  is_active: true,
  sort_order: '0',
};

let draftSeq = 0;
function nextKey(): string {
  draftSeq += 1;
  return `draft-${draftSeq}`;
}

function emptyButton(): ButtonDraft {
  return {
    key: nextKey(),
    label: '',
    url: '',
    open_in_new_tab: false,
  };
}

function mapButtons(rows: BannerButton[]): ButtonDraft[] {
  if (rows.length === 0) {
    return [emptyButton()];
  }

  return rows.map((row) => ({
    key: `b-${row.id}`,
    id: row.id,
    label: row.label,
    url: row.url,
    open_in_new_tab: row.open_in_new_tab,
  }));
}

function fieldError(errors: Record<string, string>, key: string): string | undefined {
  return errors[key];
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

export function BannerFormPage() {
  const { id } = useParams();
  const isNew = id === undefined;
  const bannerId = id && /^\d+$/.test(id) ? Number(id) : null;
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const navigate = useNavigate();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [buttons, setButtons] = useState<ButtonDraft[]>([emptyButton()]);
  const [media, setMedia] = useState<MediaFile | null>(null);
  const [mediaFileId, setMediaFileId] = useState<number | null>(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [deleted, setDeleted] = useState(false);
  const [busy, setBusy] = useState(!isNew);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const title = isNew ? 'Нов банер' : 'Редакция';
  useGlobalLoading(busy);

  useEffect(() => {
    if (isNew || bannerId === null) {
      return;
    }

    const loadedId = bannerId;
    let cancelled = false;

    async function load() {
      setBusy(true);
      try {
        const response = await getBanner(token, loadedId);
        if (cancelled) {
          return;
        }
        applyBanner(response.data.banner);
      } catch (error) {
        if (!cancelled) {
          setMessage(error instanceof ApiError ? error.message : 'Банерът не можа да се зареди.');
          toastError(error, 'Банерът не можа да се зареди.');
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
  }, [isNew, bannerId, token]);

  function applyBanner(banner: AdminBanner) {
    setForm({
      title: banner.title,
      slug: banner.slug,
      text: banner.text,
      layout: isBannerLayout(banner.layout) ? banner.layout : 'split',
      is_active: banner.is_active,
      sort_order: String(banner.sort_order),
    });
    setButtons(mapButtons(banner.buttons));
    setMediaFileId(banner.media_file_id);
    setMedia(banner.media);
    setDeleted(Boolean(banner.deleted_at));
  }

  function patchForm<K extends keyof FormState>(key: K, value: FormState[K]) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  function patchButton(index: number, patch: Partial<ButtonDraft>) {
    setButtons((current) => current.map((button, item) => (item === index ? { ...button, ...patch } : button)));
  }

  function moveButton(index: number, direction: -1 | 1) {
    setButtons((current) => {
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
      text: form.text.trim(),
      layout: form.layout,
      media_file_id: mediaFileId ?? 0,
      is_active: form.is_active,
      sort_order: Math.max(0, Number.parseInt(form.sort_order, 10) || 0),
      buttons: buttons.map((button, index) => ({
        ...(button.id ? { id: button.id } : {}),
        label: button.label.trim(),
        url: button.url.trim(),
        open_in_new_tab: button.open_in_new_tab,
        sort_order: index,
      })),
    };
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const payload = toPayload();
      if (isNew) {
        const response = await createBanner(token, payload);
        toast.success(response.message || 'Банерът е създаден.');
        navigate(`/banners/${response.data.banner.id}`, { replace: true });
        return;
      }

      if (bannerId !== null) {
        const response = await updateBanner(token, bannerId, payload);
        applyBanner(response.data.banner);
        toast.success(response.message || 'Банерът е обновен.');
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
        title={isNew ? title : form.title.trim() ? `Редакция · ${form.title}` : title}
        help="Заглавие, текст, изображение и поне един бутон. Адресът (slug) се ползва за вграждане в сайта."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Банери', to: routes.banners },
          { label: isNew ? title : form.title.trim() || title },
        ]}
        actions={
          <Button asChild variant="outline">
            <Link to={routes.banners}>
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
          Изтрит банер не се редактира. Възстановете го от списъка.
        </p>
      ) : null}

      {canEdit ? (
        <form className="flex min-w-0 max-w-full flex-col gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
          <CollapsibleSection
            title="Банер"
            icon={Image}
            persistKey="banner.general"
            help="Заглавие, адрес за вграждане и съдържание. Празен адрес се генерира от заглавието."
          >
            <div className="form-grid">
              <Field
                id="title"
                label="Заглавие"
                help="Заглавието в панела и върху банера в сайта."
                value={form.title}
                onChange={(event) => patchForm('title', event.target.value)}
                error={errors.title}
              />
              <Field
                id="slug"
                label="Адрес (slug)"
                help="Ключ за вграждане, напр. home. Празно поле се попълва от заглавието."
                value={form.slug}
                onChange={(event) => patchForm('slug', event.target.value)}
                error={errors.slug}
              />
              <div className="field">
                <LabelWithHelp
                  htmlFor="layout"
                  label="Дизайн"
                  help="Оформлението в сайта. „Разделен“ е текущият вид на „Пролетна промоция“."
                />
                <Select value={form.layout} onValueChange={(value) => patchForm('layout', value)}>
                  <SelectTrigger id="layout" className="w-full min-h-12 font-sans">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {BANNER_LAYOUTS.map((layout) => (
                      <SelectItem key={layout.value} value={layout.value}>
                        {layout.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.layout ? (
                  <p className="field-error" role="alert">
                    {errors.layout}
                  </p>
                ) : (
                  <p className="text-muted-foreground m-0 text-sm">
                    {BANNER_LAYOUTS.find((layout) => layout.value === form.layout)?.help}
                  </p>
                )}
              </div>
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
                label="Активен"
                help="Неактивен банер не се показва в публичния сайт."
                checked={form.is_active}
                onCheckedChange={(checked) => patchForm('is_active', checked)}
              />
            </div>
            <TextEditor
              id="text"
              label="Текст"
              help="Основният текст върху банера. Може да удебелите, курсивирате или да направите списък."
              value={form.text}
              onChange={(html) => patchForm('text', html)}
              error={errors.text}
            />
            <div className="field">
              <LabelWithHelp
                label="Изображение"
                help="Задължително. JPEG, PNG или WebP от медията."
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

          <CollapsibleSection
            title="Бутони"
            icon={Plus}
            persistKey="banner.buttons"
            help="Нужен е поне един бутон. Първият е основен. Редът тук е редът върху банера."
          >
            {errors.buttons ? (
              <p className="form-message is-error" role="alert">
                {errors.buttons}
              </p>
            ) : null}
            <div className="grid gap-3">
              {buttons.map((button, index) => (
                <CollapsibleSection
                  key={button.key}
                  heading="h3"
                  persistKey={button.id ? `banner-button:${button.id}` : undefined}
                  title={
                    <span className="flex min-w-0 flex-wrap items-center gap-2">
                      <span className="truncate">{button.label.trim() || 'Нов бутон'}</span>
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
                        onClick={() => moveButton(index, -1)}
                      >
                        <ChevronUp />
                      </Button>
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Надолу"
                        disabled={index === buttons.length - 1}
                        onClick={() => moveButton(index, 1)}
                      >
                        <ChevronDown />
                      </Button>
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-8"
                        aria-label="Премахни бутон"
                        disabled={buttons.length === 1}
                        onClick={() => setButtons((current) => (current.length === 1 ? current : current.filter((_, item) => item !== index)))}
                      >
                        <Trash2 />
                      </Button>
                    </div>
                  }
                >
                  <div className="form-grid">
                    <Field
                      id={`${button.key}-label`}
                      label="Текст на бутон"
                      help="Етикетът, който се вижда върху бутона."
                      value={button.label}
                      onChange={(event) => patchButton(index, { label: event.target.value })}
                      error={fieldError(errors, `buttons.${index}.label`)}
                    />
                    <Field
                      id={`${button.key}-url`}
                      label="Адрес"
                      help="Вътрешен път като /catalog или пълен адрес https://…"
                      value={button.url}
                      onChange={(event) => patchButton(index, { url: event.target.value })}
                      error={fieldError(errors, `buttons.${index}.url`)}
                    />
                    <SwitchField
                      id={`${button.key}-blank`}
                      label="Нов таб"
                      help="Отваря връзката в нов раздел на браузъра."
                      checked={button.open_in_new_tab}
                      onCheckedChange={(checked) => patchButton(index, { open_in_new_tab: checked })}
                    />
                  </div>
                </CollapsibleSection>
              ))}
            </div>
            <Button type="button" variant="outline" className="mt-3" onClick={() => setButtons((current) => [...current, emptyButton()])}>
              <Plus />
              Бутон
            </Button>
          </CollapsibleSection>

          <div className="row-actions">
            <Button type="submit" disabled={busy}>
              <Save />
              {busy ? 'Запис…' : 'Запази'}
            </Button>
            <Button asChild variant="outline">
              <Link to={routes.banners}>
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
