import { useEffect, useRef, useState, type Dispatch, type FormEvent, type ReactNode, type SetStateAction } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, Eye, ImagePlus, Images, Layers, List, Palette, Plus, RotateCcw, Save, Share2, Shirt, Trash2, Type, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import { listCategoryTree, type CategoryTreeNode } from '@/api/categories';
import {
  createProduct,
  deleteProduct,
  getProduct,
  restoreProduct,
  shareProductPersonalization,
  uploadProductFrontImage,
  uploadProductGalleryImage,
  updateProduct,
  type AdminProduct,
  type ProductOption,
  type ProductParameter,
  type ProductPersonalizationField,
  type ProductVariant,
} from '@/api/products';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { TextEditor } from '@/components/ui/TextEditor';
import { toast, toastError } from '@/lib/toast';
import { ProductImagesEditor } from '@/features/products/ProductImagesSection';
import { flattenCategoryTree } from '@/features/categories/categoryTree';
import { PageTreeSelect } from '@/features/pages/PageTreeSelect';
import { VariantImageField } from '@/features/products/VariantImageField';

type ParameterDraft = { key: string; id?: number; name: string; value: string };
type OptionValueDraft = { key: string; id?: number; name: string; slug: string; hex_color: string };
type OptionDraft = { key: string; id?: number; name: string; slug: string; values: OptionValueDraft[] };
type VariantDraft = {
  key: string;
  id?: number;
  name: string;
  sku: string;
  price: string;
  stock: string;
  is_default: boolean;
  is_active: boolean;
  option_values: Record<string, string>;
};
type PersonalizationFieldDraft = {
  key: string;
  id?: number;
  name: string;
  description: string;
  field_type: string;
  is_required: boolean;
  max_length: string;
};
type DraftProductImage = { key: string; file: File; previewUrl: string };
type DraftProductImages = { front: DraftProductImage | null; gallery: DraftProductImage[] };

let draftSeq = 0;
function nextKey(): string {
  draftSeq += 1;
  return `draft-${draftSeq}`;
}

function moneyInput(value: string | number | null | undefined): string {
  if (value === null || value === undefined) {
    return '';
  }

  return String(value);
}

function toNumber(value: string): number | null {
  const trimmed = value.trim();

  if (trimmed === '') {
    return null;
  }

  const amount = Number(trimmed);

  return Number.isFinite(amount) ? amount : null;
}

function fieldError(errors: Record<string, string>, key: string): string | undefined {
  return errors[key];
}

function SectionActions({ busy }: { busy: boolean }) {
  return (
    <div className="row-actions">
      <Button type="submit" disabled={busy}>
        <Save />
        {busy ? 'Запис…' : 'Запази секцията'}
      </Button>
    </div>
  );
}

function SwitchField({
  id,
  label,
  help,
  checked,
  disabled = false,
  note,
  onCheckedChange,
}: {
  id: string;
  label: string;
  help: string;
  checked: boolean;
  disabled?: boolean;
  note?: string;
  onCheckedChange: (checked: boolean) => void;
}) {
  return (
    <div className="field">
      <LabelWithHelp htmlFor={id} label={label} help={help} />
      <div className="flex min-h-12 items-center">
        <Switch id={id} checked={checked} disabled={disabled} onCheckedChange={onCheckedChange} />
      </div>
      {note ? <p className="m-0 text-base text-muted-foreground">{note}</p> : null}
    </div>
  );
}

type SectionFormProps = {
  product: AdminProduct;
  token: string;
  onSaved: (product: AdminProduct) => void;
};

type GeneralFormProps = {
  product: AdminProduct | null;
  token: string;
  onSaved: (product: AdminProduct) => void;
  onCreated?: (product: AdminProduct) => void | Promise<void>;
};

function GeneralForm({ product, token, onSaved, onCreated }: GeneralFormProps) {
  const isNew = product === null;
  const [name, setName] = useState(product?.name ?? '');
  const [slug, setSlug] = useState(product?.slug ?? '');
  const [sku, setSku] = useState(product?.sku ?? '');
  const [categoryId, setCategoryId] = useState(product?.category_id ? String(product.category_id) : 'none');
  const [tree, setTree] = useState<CategoryTreeNode[]>([]);
  const [price, setPrice] = useState(moneyInput(product?.price));
  const [compareAt, setCompareAt] = useState(moneyInput(product?.compare_at_price));
  const [weightGrams, setWeightGrams] = useState(String(product?.weight_grams || ''));
  const [shortDescription, setShortDescription] = useState(product?.short_description ?? '');
  const [description, setDescription] = useState(product?.description ?? '');
  const [isActive, setIsActive] = useState(product?.is_active ?? true);
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

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

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const payload = {
        name: name.trim(),
        slug: slug.trim() === '' ? null : slug.trim(),
        sku: sku.trim() === '' ? null : sku.trim(),
        category_id: categoryId === 'none' ? null : Number(categoryId),
        price: toNumber(price) ?? 0,
        compare_at_price: toNumber(compareAt),
        weight_grams: toNumber(weightGrams) ?? 0,
        short_description: shortDescription.trim() === '' ? null : shortDescription.trim(),
        description: description.trim() === '' ? null : description.trim(),
        is_active: isActive,
      };

      if (isNew) {
        const response = await createProduct(token, {
          ...payload,
          personalization_enabled: false,
          personalization_required: false,
          personalization_max_length: 80,
        });
        toast.success(response.message || 'Продуктът е създаден.');
        await onCreated?.(response.data.product);
        return;
      }

      const response = await updateProduct(token, product.id, payload);
      onSaved(response.data.product);
      toast.success(response.message || 'Записано.');
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
    <form className="grid gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
      <div className="form-grid">
        <Field id="name" label="Име" help="Името, което се вижда в каталога." value={name} onChange={(event) => setName(event.target.value)} error={errors.name} />
        <Field id="slug" label="Адрес (slug)" help="Оставете празно, за да се генерира от името." value={slug} onChange={(event) => setSlug(event.target.value)} error={errors.slug} />
        <Field id="sku" label="SKU" help="Базов артикулен номер на продукта." value={sku} onChange={(event) => setSku(event.target.value)} error={errors.sku} />
        <PageTreeSelect
          id="category_id"
          label="Категория"
          help="По избор. Вложените категории са с дълги тирета."
          value={categoryId}
          options={flattenCategoryTree(tree)}
          extra={[{ value: 'none', label: 'Няма' }]}
          error={errors.category_id}
          onValueChange={setCategoryId}
        />
        <Field id="price" label="Цена" type="number" step="0.01" min="0" help="Базова цена „от“." value={price} onChange={(event) => setPrice(event.target.value)} error={errors.price} />
        <Field id="compare_at_price" label="Сравнителна цена" type="number" step="0.01" min="0" help="Стара цена, ако има намаление. Празно поле я маха." value={compareAt} onChange={(event) => setCompareAt(event.target.value)} error={errors.compare_at_price} />
        <Field id="weight_grams" label="Тегло (грама)" type="number" step="1" min="1" help="Нетно тегло на един продукт в грамове." value={weightGrams} onChange={(event) => setWeightGrams(event.target.value)} error={errors.weight_grams} />
        <SwitchField id="is_active" label="Активен" help="Неактивен продукт е скрит от каталога." checked={isActive} onCheckedChange={setIsActive} />
      </div>
      <TextEditor id="short_description" label="Кратко описание" help="Кратък форматиран текст за списъка и картите." value={shortDescription} onChange={setShortDescription} error={errors.short_description} />
      <TextEditor id="description" label="Описание" help="Пълният форматиран текст на продуктовата страница." value={description} onChange={setDescription} error={errors.description} />
      <SectionActions busy={busy} />
    </form>
  );
}

function DraftImagesEditor({ images, setImages }: { images: DraftProductImages; setImages: Dispatch<SetStateAction<DraftProductImages>> }) {
  const frontInput = useRef<HTMLInputElement>(null);
  const galleryInput = useRef<HTMLInputElement>(null);
  const accept = 'image/jpeg,image/png,image/webp';

  function draft(file: File): DraftProductImage {
    return { key: `${Date.now()}-${Math.random()}`, file, previewUrl: URL.createObjectURL(file) };
  }

  function setFront(file?: File) {
    if (!file) return;
    setImages((current) => {
      if (current.front) URL.revokeObjectURL(current.front.previewUrl);
      return { ...current, front: draft(file) };
    });
  }

  function addGallery(files: FileList | null) {
    if (!files?.length) return;
    setImages((current) => ({ ...current, gallery: [...current.gallery, ...Array.from(files).map(draft)] }));
  }

  function remove(image: DraftProductImage, target: 'front' | 'gallery') {
    URL.revokeObjectURL(image.previewUrl);
    setImages((current) => target === 'front'
      ? { ...current, front: null }
      : { ...current, gallery: current.gallery.filter((item) => item.key !== image.key) });
  }

  return <div className="grid gap-4">
    <div className="grid gap-2">
      <LabelWithHelp label="Основно изображение" help="Ще се използва като предна снимка на продукта." />
      {images.front ? <div className="relative w-40 overflow-hidden border border-border bg-muted aspect-[4/5]">
        <img src={images.front.previewUrl} alt="Основно изображение" className="h-full w-full object-cover" />
        <Button type="button" size="icon" variant="outline" className="absolute right-2 top-2 bg-background" aria-label="Премахни основното изображение" onClick={() => remove(images.front!, 'front')}><X /></Button>
      </div> : <Button type="button" variant="outline" className="w-fit" onClick={() => frontInput.current?.click()}><ImagePlus />Избери основно изображение</Button>}
      <input ref={frontInput} className="sr-only" type="file" accept={accept} onChange={(event) => { setFront(event.target.files?.[0]); event.target.value = ''; }} />
    </div>
    <div className="grid gap-2">
      <LabelWithHelp label="Галерия" help="Може да изберете няколко изображения още преди създаването." />
      {images.gallery.length ? <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6">{images.gallery.map((image) => <div key={image.key} className="relative overflow-hidden border border-border bg-muted aspect-[4/5]">
        <img src={image.previewUrl} alt={image.file.name} className="h-full w-full object-cover" />
        <Button type="button" size="icon" variant="outline" className="absolute right-1 top-1 bg-background" aria-label="Премахни изображението" onClick={() => remove(image, 'gallery')}><X /></Button>
      </div>)}</div> : null}
      <Button type="button" variant="outline" className="w-fit" onClick={() => galleryInput.current?.click()}><Images />Добави към галерията</Button>
      <input ref={galleryInput} className="sr-only" type="file" accept={accept} multiple onChange={(event) => { addGallery(event.target.files); event.target.value = ''; }} />
    </div>
  </div>;
}

function ParametersForm({ product, token, onSaved }: SectionFormProps) {
  const [rows, setRows] = useState<ParameterDraft[]>(() => mapParameters(product.parameters));
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const response = await updateProduct(token, product.id, {
        parameters: rows.map((row, index) => ({
          ...(row.id ? { id: row.id } : {}),
          name: row.name.trim(),
          value: row.value.trim(),
          sort_order: index,
        })),
      });
      onSaved(response.data.product);
      setRows(mapParameters(response.data.product.parameters));
      toast.success(response.message || 'Записано.');
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
    <form className="grid gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
      {rows.length === 0 ? <p className="m-0 text-muted-foreground">Няма параметри. Добавете материя, грамаж или кройка.</p> : null}
      {rows.map((row, index) => (
        <CollapsibleSection
          key={row.key}
          heading="h3"
          persistKey={row.id ? `parameter:${row.id}` : undefined}
          title={<ParameterCardTitle row={row} />}
          actions={
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="size-8"
              aria-label="Премахни параметър"
              onClick={() => setRows((current) => current.filter((_, item) => item !== index))}
            >
              <Trash2 />
            </Button>
          }
        >
          <div className="grid gap-3 sm:grid-cols-2">
            <Field id={`${row.key}-name`} label="Име" help="Напр. материя, грамаж или кройка." value={row.name} onChange={(event) => patchRow(index, { name: event.target.value })} error={fieldError(errors, `parameters.${index}.name`)} />
            <Field id={`${row.key}-value`} label="Стойност" help="Какво се показва до името, напр. 180 г/м²." value={row.value} onChange={(event) => patchRow(index, { value: event.target.value })} error={fieldError(errors, `parameters.${index}.value`)} />
          </div>
        </CollapsibleSection>
      ))}
      <Button type="button" variant="outline" onClick={() => setRows((current) => [...current, { key: nextKey(), name: '', value: '' }])}>
        <Plus />
        Параметър
      </Button>
      <SectionActions busy={busy} />
    </form>
  );

  function patchRow(index: number, patch: Partial<ParameterDraft>) {
    setRows((current) => current.map((row, item) => (item === index ? { ...row, ...patch } : row)));
  }
}

function ParameterCardTitle({ row }: { row: ParameterDraft }) {
  return (
    <span className="flex min-w-0 flex-wrap items-center gap-2">
      <span className="truncate">{row.name.trim() || 'Нов параметър'}</span>
      {row.value.trim() ? <span className="truncate font-medium tracking-normal text-muted-foreground">{row.value.trim()}</span> : null}
    </span>
  );
}

function mapParameters(rows: ProductParameter[]): ParameterDraft[] {
  return rows.map((row) => ({ key: `p-${row.id}`, id: row.id, name: row.name, value: row.value }));
}

function OptionsForm({ product, token, onSaved }: SectionFormProps) {
  const [rows, setRows] = useState<OptionDraft[]>(() => mapOptions(product.options));
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const response = await updateProduct(token, product.id, {
        options: rows.map((option, index) => ({
          ...(option.id ? { id: option.id } : {}),
          name: option.name.trim(),
          slug: option.slug.trim() === '' ? null : option.slug.trim(),
          sort_order: index,
          values: option.values.map((value, valueIndex) => ({
            ...(value.id ? { id: value.id } : {}),
            name: value.name.trim(),
            slug: value.slug.trim() === '' ? null : value.slug.trim(),
            hex_color: value.hex_color.trim() === '' ? null : value.hex_color.trim(),
            sort_order: valueIndex,
          })),
        })),
      });
      onSaved(response.data.product);
      setRows(mapOptions(response.data.product.options));
      toast.success(response.message || 'Записано.');
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
    <form className="grid gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
      <p className="m-0 text-muted-foreground">Размер, цвят и други избори. След промяна запишете и вариантите, ако комбинациите са се сменили.</p>
      {rows.map((option, optionIndex) => (
        <CollapsibleSection
          key={option.key}
          heading="h3"
          persistKey={option.id ? `option:${option.id}` : undefined}
          title={<OptionCardTitle option={option} />}
          actions={
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="size-8"
              aria-label="Премахни опция"
              onClick={() => setRows((current) => current.filter((_, item) => item !== optionIndex))}
            >
              <Trash2 />
            </Button>
          }
        >
          <div className="grid gap-3">
            <div className="grid gap-3 sm:grid-cols-2">
              <Field id={`${option.key}-name`} label="Опция" help="Името, което клиентът вижда, напр. Размер или Цвят." value={option.name} onChange={(event) => patchOption(optionIndex, { name: event.target.value })} error={fieldError(errors, `options.${optionIndex}.name`)} />
              <Field id={`${option.key}-slug`} label="Адрес" help="Празно поле се попълва от името." value={option.slug} onChange={(event) => patchOption(optionIndex, { slug: event.target.value })} error={fieldError(errors, `options.${optionIndex}.slug`)} />
            </div>
            {option.values.map((value, valueIndex) => (
              <div key={value.key} className="grid gap-3 sm:grid-cols-[1fr_1fr_8rem_auto]">
                <Field id={`${value.key}-name`} label="Стойност" help="Една от възможностите, напр. M или Черно." value={value.name} onChange={(event) => patchValue(optionIndex, valueIndex, { name: event.target.value })} error={fieldError(errors, `options.${optionIndex}.values.${valueIndex}.name`)} />
                <Field id={`${value.key}-slug`} label="Адрес" help="Празно поле се попълва от името." value={value.slug} onChange={(event) => patchValue(optionIndex, valueIndex, { slug: event.target.value })} error={fieldError(errors, `options.${optionIndex}.values.${valueIndex}.slug`)} />
                <Field id={`${value.key}-hex`} label="Цвят" placeholder="#FFFFFF" help="HEX за цветни опции. Оставете празно, ако не е цвят." value={value.hex_color} onChange={(event) => patchValue(optionIndex, valueIndex, { hex_color: event.target.value })} error={fieldError(errors, `options.${optionIndex}.values.${valueIndex}.hex_color`)} />
                <div className="flex items-end">
                  <Button type="button" variant="outline" size="icon" aria-label="Премахни стойност" onClick={() => removeValue(optionIndex, valueIndex)}>
                    <Trash2 />
                  </Button>
                </div>
              </div>
            ))}
            <Button type="button" variant="outline" onClick={() => addValue(optionIndex)}>
              <Plus />
              Стойност
            </Button>
          </div>
        </CollapsibleSection>
      ))}
      <Button type="button" variant="outline" onClick={() => setRows((current) => [...current, { key: nextKey(), name: '', slug: '', values: [] }])}>
        <Plus />
        Опция
      </Button>
      <SectionActions busy={busy} />
    </form>
  );

  function patchOption(index: number, patch: Partial<OptionDraft>) {
    setRows((current) => current.map((row, item) => (item === index ? { ...row, ...patch } : row)));
  }

  function patchValue(optionIndex: number, valueIndex: number, patch: Partial<OptionValueDraft>) {
    setRows((current) =>
      current.map((option, item) =>
        item === optionIndex
          ? { ...option, values: option.values.map((value, nested) => (nested === valueIndex ? { ...value, ...patch } : value)) }
          : option
      )
    );
  }

  function addValue(optionIndex: number) {
    setRows((current) =>
      current.map((option, item) =>
        item === optionIndex ? { ...option, values: [...option.values, { key: nextKey(), name: '', slug: '', hex_color: '' }] } : option
      )
    );
  }

  function removeValue(optionIndex: number, valueIndex: number) {
    setRows((current) =>
      current.map((option, item) =>
        item === optionIndex ? { ...option, values: option.values.filter((_, nested) => nested !== valueIndex) } : option
      )
    );
  }
}

function OptionCardTitle({ option }: { option: OptionDraft }) {
  const valueLabel = option.values
    .map((value) => value.name.trim())
    .filter(Boolean)
    .join(' · ');

  return (
    <span className="flex min-w-0 flex-wrap items-center gap-2">
      <span className="truncate">{option.name.trim() || 'Нова опция'}</span>
      {valueLabel ? <span className="truncate font-medium tracking-normal text-muted-foreground">{valueLabel}</span> : null}
    </span>
  );
}

function mapOptions(options: ProductOption[]): OptionDraft[] {
  return options.map((option) => ({
    key: `o-${option.id}`,
    id: option.id,
    name: option.name,
    slug: option.slug,
    values: option.values.map((value) => ({
      key: `ov-${value.id}`,
      id: value.id,
      name: value.name,
      slug: value.slug,
      hex_color: value.hex_color ?? '',
    })),
  }));
}

function VariantsForm({ product, token, onSaved }: SectionFormProps) {
  const [rows, setRows] = useState<VariantDraft[]>(() => mapVariants(product.variants));
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const response = await updateProduct(token, product.id, {
        variants: rows.map((row, index) => ({
          ...(row.id ? { id: row.id } : {}),
          name: row.name.trim() === '' ? null : row.name.trim(),
          sku: row.sku.trim(),
          price: toNumber(row.price) ?? 0,
          stock: Math.max(0, Number.parseInt(row.stock, 10) || 0),
          is_default: row.is_default,
          is_active: row.is_active,
          sort_order: index,
          option_values: product.options.map((option) => ({
            option: option.slug,
            value: row.option_values[option.slug] ?? '',
          })),
        })),
      });
      onSaved(response.data.product);
      setRows(mapVariants(response.data.product.variants));
      toast.success(response.message || 'Записано.');
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
    <form className="grid gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
      {rows.length === 0 ? <p className="m-0 text-muted-foreground">Няма варианти. Добавете комбинация от опциите.</p> : null}
      {rows.map((row, index) => (
        <CollapsibleSection
          key={row.key}
          heading="h3"
          persistKey={row.id ? `variant:${row.id}` : undefined}
          title={<VariantCardTitle row={row} options={product.options} />}
          actions={
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="size-8"
              aria-label="Премахни вариант"
              onClick={() => setRows((current) => current.filter((_, item) => item !== index))}
            >
              <Trash2 />
            </Button>
          }
        >
          <div className="grid gap-3">
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <Field id={`${row.key}-name`} label="Име" help="Име на варианта. Използва се и за име на каченото изображение." value={row.name} onChange={(event) => patchRow(index, { name: event.target.value })} error={fieldError(errors, `variants.${index}.name`)} />
              <Field id={`${row.key}-sku`} label="SKU" help="Уникален артикулен номер на тази комбинация." value={row.sku} onChange={(event) => patchRow(index, { sku: event.target.value })} error={fieldError(errors, `variants.${index}.sku`)} />
              <Field id={`${row.key}-price`} label="Цена" type="number" step="0.01" min="0" help="Цена на този вариант. Може да е различна от базовата." value={row.price} onChange={(event) => patchRow(index, { price: event.target.value })} error={fieldError(errors, `variants.${index}.price`)} />
            </div>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <Field id={`${row.key}-stock`} label="Наличност" type="number" min="0" help="Бройки за продажба от този вариант." value={row.stock} onChange={(event) => patchRow(index, { stock: event.target.value })} error={fieldError(errors, `variants.${index}.stock`)} />
              {product.options.map((option) => (
                <div key={option.id} className="field">
                  <LabelWithHelp htmlFor={`${row.key}-${option.slug}`} label={option.name} help="Стойността на тази опция за варианта." />
                  <Select
                    value={row.option_values[option.slug] || undefined}
                    onValueChange={(value) =>
                      patchRow(index, { option_values: { ...row.option_values, [option.slug]: value } })
                    }
                  >
                    <SelectTrigger id={`${row.key}-${option.slug}`} className="w-full min-h-12 font-sans">
                      <SelectValue placeholder="Изберете" />
                    </SelectTrigger>
                    <SelectContent>
                      {option.values.map((value) => (
                        <SelectItem key={value.id} value={value.slug}>
                          {value.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              ))}
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              <SwitchField
                id={`${row.key}-active`}
                label="Активен"
                help="Неактивен вариант не се предлага за покупка."
                checked={row.is_active}
                onCheckedChange={(checked) => patchRow(index, { is_active: checked })}
              />
              <SwitchField
                id={`${row.key}-default`}
                label="По подразбиране"
                help="Само един вариант може да е избран първи в магазина. Включете го на друг вариант, за да преместите избора."
                checked={row.is_default}
                disabled={row.is_default}
                note={
                  row.is_default
                    ? 'Включено е и не може да се кликва. За да го смените, отворете друг вариант и включете „По подразбиране“ там.'
                    : undefined
                }
                onCheckedChange={(checked) => {
                  if (checked) {
                    setDefault(index);
                  }
                }}
              />
            </div>
            <VariantImageField
              product={product}
              variantId={row.id}
              token={token}
              onProductChange={onSaved}
            />
          </div>
        </CollapsibleSection>
      ))}
      <Button type="button" variant="outline" onClick={() => addVariant()}>
        <Plus />
        Вариант
      </Button>
      <SectionActions busy={busy} />
    </form>
  );

  function patchRow(index: number, patch: Partial<VariantDraft>) {
    setRows((current) => current.map((row, item) => (item === index ? { ...row, ...patch } : row)));
  }

  function setDefault(index: number) {
    setRows((current) => current.map((row, item) => ({ ...row, is_default: item === index })));
  }

  function addVariant() {
    const option_values: Record<string, string> = {};

    for (const option of product.options) {
      option_values[option.slug] = option.values[0]?.slug ?? '';
    }

    setRows((current) => [
      ...current,
      {
        key: nextKey(),
        name: '',
        sku: '',
        price: moneyInput(product.price),
        stock: '0',
        is_default: current.length === 0,
        is_active: true,
        option_values,
      },
    ]);
  }
}

function mapVariants(variants: ProductVariant[]): VariantDraft[] {
  return variants.map((variant) => ({
    key: `v-${variant.id}`,
    id: variant.id,
    name: variant.name ?? '',
    sku: variant.sku ?? '',
    price: moneyInput(variant.price),
    stock: String(variant.stock),
    is_default: variant.is_default,
    is_active: variant.is_active,
    option_values: Object.fromEntries(variant.option_values.map((row) => [row.option ?? '', row.value ?? ''])),
  }));
}

function VariantCardTitle({ row, options }: { row: VariantDraft; options: ProductOption[] }) {
  const optionLabel = options
    .map((option) => option.values.find((value) => value.slug === row.option_values[option.slug])?.name)
    .filter(Boolean)
    .join(' · ');

  return (
    <span className="flex min-w-0 flex-wrap items-center gap-2">
      <span className="truncate">{row.name.trim() || row.sku.trim() || 'Нов вариант'}</span>
      {optionLabel ? <span className="truncate font-medium tracking-normal text-muted-foreground">{optionLabel}</span> : null}
      {row.is_default ? <span className="badge info">По подразбиране</span> : null}
      {!row.is_active ? <span className="badge idle">Неактивен</span> : null}
    </span>
  );
}

function PersonalizationForm({ product, token, onSaved }: SectionFormProps) {
  const [enabled, setEnabled] = useState(product.personalization_enabled);
  const [label, setLabel] = useState(product.personalization_label ?? '');
  const [description, setDescription] = useState(product.personalization_description ?? '');
  const [required, setRequired] = useState(product.personalization_required);
  const [maxLength, setMaxLength] = useState(String(product.personalization_max_length ?? 80));
  const [override, setOverride] = useState(product.personalization_override);
  const [fields, setFields] = useState<PersonalizationFieldDraft[]>(() => mapPersonalization(product.personalization_fields));
  const [busy, setBusy] = useState(false);
  const [sharing, setSharing] = useState(false);
  const [confirmShare, setConfirmShare] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  function payload() {
    return {
      personalization_enabled: enabled,
      personalization_label: label.trim() === '' ? null : label.trim(),
      personalization_description: description.trim() === '' ? null : description.trim(),
      personalization_required: required,
      personalization_max_length: Math.max(1, Number.parseInt(maxLength, 10) || 80),
      personalization_override: override,
      personalization_fields: fields.map((field, index) => ({
        ...(field.id ? { id: field.id } : {}),
        name: field.name.trim(),
        description: field.description.trim() === '' ? null : field.description.trim(),
        field_type: field.field_type,
        is_required: field.is_required,
        max_length: Math.max(1, Number.parseInt(field.max_length, 10) || 80),
        sort_order: index,
      })),
    };
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setErrors({});

    try {
      const response = await updateProduct(token, product.id, payload());
      onSaved(response.data.product);
      setFields(mapPersonalization(response.data.product.personalization_fields));
      toast.success(response.message || 'Записано.');
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors(error.fieldErrors());
      }
      toastError(error, 'Записът не беше успешен.');
    } finally {
      setBusy(false);
    }
  }

  async function onShare() {
    setSharing(true);
    setErrors({});

    try {
      const response = await shareProductPersonalization(token, product.id, payload());
      onSaved(response.data.product);
      setFields(mapPersonalization(response.data.product.personalization_fields));
      setConfirmShare(false);
      toast.success(response.message || 'Персонализацията е приложена към всички продукти.');
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors(error.fieldErrors());
      }
      toastError(error, 'Споделянето не беше успешно.');
    } finally {
      setSharing(false);
    }
  }

  function changeOverride(checked: boolean) {
    if (!checked && product.personalization_default) {
      const defaults = product.personalization_default;
      setEnabled(defaults.enabled);
      setLabel(defaults.label ?? '');
      setDescription(defaults.description ?? '');
      setRequired(defaults.required);
      setMaxLength(String(defaults.max_length ?? 80));
      setFields(mapPersonalization(defaults.fields ?? []));
    }
    setOverride(checked);
  }

  return (
    <form className="grid gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
      <SwitchField id="personalization_override" label="Собствена настройка за този продукт" help="Когато е изключено, продуктът използва автоматично запазената настройка по подразбиране." checked={override} onCheckedChange={changeOverride} />
      <fieldset className="grid gap-3 border-0 p-0 m-0 disabled:opacity-60" disabled={!override}>
      <SwitchField id="personalization_enabled" label="Включена" help="Клиентът вижда полета за текст преди добавяне в количката." checked={enabled} onCheckedChange={setEnabled} />
      <Field id="personalization_label" label="Етикет" help="Заглавие над полето в магазина, ако няма отделни полета." value={label} onChange={(event) => setLabel(event.target.value)} error={errors.personalization_label} />
      <Field id="personalization_description" label="Placeholder" multiline rows={3} value={description} onChange={(event) => setDescription(event.target.value)} error={errors.personalization_description} />
      <SwitchField id="personalization_required" label="Задължителна" help="Ако няма отделни полета, това важи за единственото текстово поле." checked={required} onCheckedChange={setRequired} />
      <Field id="personalization_max_length" label="Макс. дължина" type="number" min="1" help="Лимит на символите, ако няма отделни полета." value={maxLength} onChange={(event) => setMaxLength(event.target.value)} error={errors.personalization_max_length} />
      {fields.map((field, index) => (
        <div key={field.key} className="grid gap-3 rounded-[6px] border border-border p-3">
          <Field id={`${field.key}-name`} label="Поле" help="Името на полето, напр. Име върху тениската." value={field.name} onChange={(event) => patchField(index, { name: event.target.value })} error={fieldError(errors, `personalization_fields.${index}.name`)} />
          <Field id={`${field.key}-description`} label="Placeholder" value={field.description} onChange={(event) => patchField(index, { description: event.target.value })} error={fieldError(errors, `personalization_fields.${index}.description`)} />
          <div className="field">
            <LabelWithHelp htmlFor={`${field.key}-type`} label="Тип" help="Текст е един ред. Многоредов е за по-дълги надписи." />
            <Select value={field.field_type} onValueChange={(value) => patchField(index, { field_type: value })}>
              <SelectTrigger id={`${field.key}-type`} className="w-full min-h-12 font-sans">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="text">Текст</SelectItem>
                <SelectItem value="textarea">Многоредов текст</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <Field id={`${field.key}-max`} label="Макс. дължина" type="number" min="1" help="Лимит на символите за това поле." value={field.max_length} onChange={(event) => patchField(index, { max_length: event.target.value })} error={fieldError(errors, `personalization_fields.${index}.max_length`)} />
          <div className="flex flex-wrap items-center gap-4">
            <SwitchField id={`${field.key}-required`} label="Задължително" help="Клиентът трябва да попълни това поле." checked={field.is_required} onCheckedChange={(checked) => patchField(index, { is_required: checked })} />
            <Button type="button" variant="outline" size="icon" aria-label="Премахни поле" onClick={() => setFields((current) => current.filter((_, item) => item !== index))}>
              <Trash2 />
            </Button>
          </div>
        </div>
      ))}
      <Button type="button" variant="outline" onClick={() => setFields((current) => [...current, { key: nextKey(), name: '', description: '', field_type: 'text', is_required: false, max_length: '80' }])}>
        <Plus />
        Поле
      </Button>
      </fieldset>
      <div className="row-actions">
        <Button type="submit" disabled={busy || sharing}>
          <Save />
          {busy ? 'Запис…' : 'Запази секцията'}
        </Button>
        <Button type="button" variant="outline" disabled={busy || sharing} onClick={() => setConfirmShare(true)}>
          <Share2 />
          Запази по подразбиране
        </Button>
      </div>
      {confirmShare ? (
        <ConfirmDialog
          title="Настройка по подразбиране"
          message="Тези настройки и полета ще се използват автоматично от всички продукти без собствена настройка."
          confirmLabel="Запази по подразбиране"
          variant="default"
          busy={sharing}
          onConfirm={() => void onShare()}
          onCancel={() => setConfirmShare(false)}
        />
      ) : null}
    </form>
  );

  function patchField(index: number, patch: Partial<PersonalizationFieldDraft>) {
    setFields((current) => current.map((field, item) => (item === index ? { ...field, ...patch } : field)));
  }
}

function mapPersonalization(fields: ProductPersonalizationField[]): PersonalizationFieldDraft[] {
  return fields.map((field) => ({
    key: field.id ? `pf-${field.id}` : nextKey(),
    id: field.id ?? undefined,
    name: field.name,
    description: field.description ?? '',
    field_type: field.field_type || 'text',
    is_required: field.is_required,
    max_length: String(field.max_length ?? 80),
  }));
}

function SectionShell({
  title,
  icon,
  help,
  children,
}: {
  title: string;
  icon: typeof Shirt;
  help: string;
  children: ReactNode;
}) {
  return (
    <CollapsibleSection title={title} icon={icon} help={help}>
      {children}
    </CollapsibleSection>
  );
}

export function ProductEditPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const navigate = useNavigate();
  const { id } = useParams();
  const isNew = id === undefined;
  const productId = Number(id);
  const [product, setProduct] = useState<AdminProduct | null>(null);
  const [busy, setBusy] = useState(!isNew);
  const [message, setMessage] = useState<string | null>(null);
  const [confirmStatus, setConfirmStatus] = useState<'delete' | 'restore' | null>(null);
  const [draftImages, setDraftImages] = useState<DraftProductImages>({ front: null, gallery: [] });
  const draftImagesRef = useRef(draftImages);
  draftImagesRef.current = draftImages;
  useGlobalLoading(busy);

  useEffect(() => () => {
    const current = draftImagesRef.current;
    if (current.front) URL.revokeObjectURL(current.front.previewUrl);
    current.gallery.forEach((image) => URL.revokeObjectURL(image.previewUrl));
  }, []);

  async function finishCreation(created: AdminProduct) {
    setBusy(true);
    try {
      if (draftImages.front) await uploadProductFrontImage(token, created.id, draftImages.front.file);
      for (const image of draftImages.gallery) await uploadProductGalleryImage(token, created.id, image.file);
      if (draftImages.front || draftImages.gallery.length) toast.success('Изображенията са качени и прикачени към продукта.');
    } catch (error) {
      toastError(error, 'Продуктът е създаден, но част от изображенията не можаха да се качат. Можете да опитате отново в редакцията.');
    } finally {
      navigate(`/products/${created.id}/edit`, { replace: true });
    }
  }

  async function changeDeletedStatus() {
    if (!product || !confirmStatus) return;
    setBusy(true);
    try {
      if (confirmStatus === 'restore') {
        const response = await restoreProduct(token, product.id);
        setProduct(response.data.product);
        toast.success(response.message || 'Продуктът е възстановен.');
      } else {
        const response = await deleteProduct(token, product.id);
        const refreshed = await getProduct(token, product.id);
        setProduct(refreshed.data.product);
        toast.success(response.message || 'Продуктът е преместен в изтрити.');
      }
      setConfirmStatus(null);
    } catch (error) {
      toastError(error, confirmStatus === 'restore' ? 'Продуктът не можа да бъде възстановен.' : 'Продуктът не можа да бъде изтрит.');
    } finally {
      setBusy(false);
    }
  }

  useEffect(() => {
    let cancelled = false;

    async function load() {
      if (isNew) {
        setBusy(false);
        return;
      }

      if (!Number.isInteger(productId) || productId < 1) {
        setMessage('Продуктът не е намерен.');
        toast.error('Продуктът не е намерен.');
        setBusy(false);
        return;
      }

      setBusy(true);
      setMessage(null);

      try {
        const response = await getProduct(token, productId);
        if (!cancelled) {
          setProduct(response.data.product);
        }
      } catch (error) {
        if (!cancelled) {
          setProduct(null);
          const text = error instanceof ApiError ? error.message : 'Продуктът не можа да се зареди.';
          setMessage(text);
          toast.error(text);
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
  }, [isNew, productId, token]);

  const canEdit = isNew || (product !== null && !product.deleted_at);

  return (
    <div className="page min-w-0">
      <PageHeader
        title={isNew ? 'Нов продукт' : product ? `Редакция · ${product.name}` : 'Редакция'}
        help={
          isNew
            ? 'Добавете изображения и попълнете основните данни. Снимките ще се качат автоматично след създаването.'
            : 'Всяка секция се записва отделно. Изображенията се качват веднага. Незапазените промени в другите секции не се пращат.'
        }
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Продукти', to: routes.products },
          ...(isNew
            ? [{ label: 'Нов продукт' }]
            : [
                { label: product?.name ?? 'Продукт', to: `/products/${productId}` },
                { label: 'Редакция' },
              ]),
        ]}
        actions={
          <div className="flex w-full flex-wrap gap-2 sm:w-auto">
            {!isNew ? (
              <>
                {!product?.deleted_at ? <Button asChild variant="outline"><Link to={`/products/${productId}`}><Eye />Преглед</Link></Button> : null}
                {product?.deleted_at
                  ? <Button type="button" variant="outline" onClick={() => setConfirmStatus('restore')}><RotateCcw />Възстанови</Button>
                  : <Button type="button" variant="destructive" onClick={() => setConfirmStatus('delete')}><Trash2 />Изтрий</Button>}
              </>
            ) : null}
            <Button asChild variant="outline">
              <Link to={routes.products}>
                <ArrowLeft />
                Към списъка
              </Link>
            </Button>
          </div>
        }
      />

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      {product?.deleted_at ? (
        <p className="form-message is-error" role="alert">
          Изтрит продукт не се редактира. Възстановете го от списъка.
        </p>
      ) : null}

      {canEdit && isNew ? (
        <div className="flex min-w-0 max-w-full flex-col gap-3">
          <SectionShell title="Изображения" icon={Images} help="Изберете основна снимка и галерия преди създаването на продукта.">
            <DraftImagesEditor images={draftImages} setImages={setDraftImages} />
          </SectionShell>
          <SectionShell title="Общи данни" icon={Shirt} help="Име, цена и статус. След запис се отваря пълната редакция.">
            <GeneralForm
              product={null}
              token={token}
              onSaved={setProduct}
              onCreated={finishCreation}
            />
          </SectionShell>
        </div>
      ) : null}

      {canEdit && product ? (
        <div className="flex min-w-0 max-w-full flex-col gap-3">
          <SectionShell title="Изображения" icon={Images} help="Предна снимка и галерия. Качват се веднага, без запис на секцията.">
            <ProductImagesEditor product={product} token={token} onProductChange={setProduct} />
          </SectionShell>
          <SectionShell title="Общи данни" icon={Shirt} help="Име, цена и статус в каталога. Записът важи само за тази секция.">
            <GeneralForm key={`general-${product.id}`} product={product} token={token} onSaved={setProduct} />
          </SectionShell>
          <SectionShell title="Параметри" icon={List} help="Характеристики като материя и грамаж. Показват се в описанието на продукта.">
            <ParametersForm key={`parameters-${product.id}`} product={product} token={token} onSaved={setProduct} />
          </SectionShell>
          <SectionShell title="Опции" icon={Palette} help="Размер, цвят и други избори. След промяна запишете и вариантите, ако комбинациите са се сменили.">
            <OptionsForm key={`options-${product.id}`} product={product} token={token} onSaved={setProduct} />
          </SectionShell>
          <SectionShell title="Варианти" icon={Layers} help="Комбинации за покупка. Всяка може да има своя цена, наличност и снимка.">
            <VariantsForm key={`variants-${product.id}`} product={product} token={token} onSaved={setProduct} />
          </SectionShell>
          <SectionShell title="Персонализация" icon={Type} help="Текст, който клиентът въвежда преди добавяне в количката, например име върху тениска.">
            <PersonalizationForm key={`personalization-${product.id}`} product={product} token={token} onSaved={setProduct} />
          </SectionShell>
        </div>
      ) : null}

      {confirmStatus ? <ConfirmDialog
        title={confirmStatus === 'restore' ? 'Възстановяване на продукт' : 'Изтриване на продукт'}
        message={confirmStatus === 'restore' ? 'Продуктът отново ще може да се редактира и публикува.' : 'Продуктът ще бъде скрит от магазина и преместен в „Изтрити“. Данните и изображенията му ще се запазят.'}
        confirmLabel={confirmStatus === 'restore' ? 'Възстанови' : 'Изтрий'}
        variant={confirmStatus === 'restore' ? 'default' : 'destructive'}
        busy={busy}
        onConfirm={() => void changeDeletedStatus()}
        onCancel={() => setConfirmStatus(null)}
      /> : null}
    </div>
  );
}
