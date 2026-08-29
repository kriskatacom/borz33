import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Eye, Images, Layers, List, Palette, Plus, Save, Share2, Shirt, Trash2, Type } from 'lucide-react';
import { ApiError } from '@/api/client';
import { listCategoryTree, type CategoryTreeNode } from '@/api/categories';
import {
  getProduct,
  shareProductPersonalization,
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

function GeneralForm({ product, token, onSaved }: SectionFormProps) {
  const [name, setName] = useState(product.name);
  const [slug, setSlug] = useState(product.slug);
  const [sku, setSku] = useState(product.sku ?? '');
  const [categoryId, setCategoryId] = useState(product.category_id ? String(product.category_id) : 'none');
  const [tree, setTree] = useState<CategoryTreeNode[]>([]);
  const [price, setPrice] = useState(moneyInput(product.price));
  const [compareAt, setCompareAt] = useState(moneyInput(product.compare_at_price));
  const [shortDescription, setShortDescription] = useState(product.short_description ?? '');
  const [description, setDescription] = useState(product.description ?? '');
  const [isActive, setIsActive] = useState(product.is_active);
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
      const response = await updateProduct(token, product.id, {
        name: name.trim(),
        slug: slug.trim() === '' ? null : slug.trim(),
        sku: sku.trim() === '' ? null : sku.trim(),
        category_id: categoryId === 'none' ? null : Number(categoryId),
        price: toNumber(price) ?? 0,
        compare_at_price: toNumber(compareAt),
        short_description: shortDescription.trim() === '' ? null : shortDescription.trim(),
        description: description.trim() === '' ? null : description.trim(),
        is_active: isActive,
      });
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
        <SwitchField id="is_active" label="Активен" help="Неактивен продукт е скрит от каталога." checked={isActive} onCheckedChange={setIsActive} />
      </div>
      <Field id="short_description" label="Кратко описание" help="Едно изречение за списъка и картите." value={shortDescription} onChange={(event) => setShortDescription(event.target.value)} error={errors.short_description} />
      <Field id="description" label="Описание" multiline rows={6} help="Пълният текст на продуктовата страница." value={description} onChange={(event) => setDescription(event.target.value)} error={errors.description} />
      <SectionActions busy={busy} />
    </form>
  );
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

  return (
    <form className="grid gap-3" onSubmit={(event) => void onSubmit(event)} noValidate>
      <SwitchField id="personalization_enabled" label="Включена" help="Клиентът вижда полета за текст преди добавяне в количката." checked={enabled} onCheckedChange={setEnabled} />
      <Field id="personalization_label" label="Етикет" help="Заглавие над полето в магазина, ако няма отделни полета." value={label} onChange={(event) => setLabel(event.target.value)} error={errors.personalization_label} />
      <Field id="personalization_description" label="Указания" multiline rows={3} help="Кратък текст какво да въведе клиентът." value={description} onChange={(event) => setDescription(event.target.value)} error={errors.personalization_description} />
      <SwitchField id="personalization_required" label="Задължителна" help="Ако няма отделни полета, това важи за единственото текстово поле." checked={required} onCheckedChange={setRequired} />
      <Field id="personalization_max_length" label="Макс. дължина" type="number" min="1" help="Лимит на символите, ако няма отделни полета." value={maxLength} onChange={(event) => setMaxLength(event.target.value)} error={errors.personalization_max_length} />
      {fields.map((field, index) => (
        <div key={field.key} className="grid gap-3 rounded-[6px] border border-border p-3">
          <Field id={`${field.key}-name`} label="Поле" help="Името на полето, напр. Име върху тениската." value={field.name} onChange={(event) => patchField(index, { name: event.target.value })} error={fieldError(errors, `personalization_fields.${index}.name`)} />
          <Field id={`${field.key}-description`} label="Описание" help="Подсказка под полето в магазина." value={field.description} onChange={(event) => patchField(index, { description: event.target.value })} error={fieldError(errors, `personalization_fields.${index}.description`)} />
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
      <div className="row-actions">
        <Button type="submit" disabled={busy || sharing}>
          <Save />
          {busy ? 'Запис…' : 'Запази секцията'}
        </Button>
        <Button type="button" variant="outline" disabled={busy || sharing} onClick={() => setConfirmShare(true)}>
          <Share2 />
          Към всички продукти
        </Button>
      </div>
      {confirmShare ? (
        <ConfirmDialog
          title="Споделяне на персонализация"
          message="Тези настройки и полета ще заменят персонализацията на всички останали продукти. Действието не може да се отмени автоматично."
          confirmLabel="Приложи към всички"
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
    key: `pf-${field.id}`,
    id: field.id,
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
  const { id } = useParams();
  const productId = Number(id);
  const [product, setProduct] = useState<AdminProduct | null>(null);
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  useGlobalLoading(busy);

  useEffect(() => {
    let cancelled = false;

    async function load() {
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
  }, [productId, token]);

  const canEdit = product !== null && !product.deleted_at;

  return (
    <div className="page min-w-0">
      <PageHeader
        title={product ? `Редакция · ${product.name}` : 'Редакция'}
        help="Всяка секция се записва отделно. Изображенията се качват веднага. Незапазените промени в другите секции не се пращат."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Продукти', to: routes.products },
          { label: product?.name ?? 'Продукт', to: `/products/${productId}` },
          { label: 'Редакция' },
        ]}
        actions={
          <div className="flex w-full flex-wrap gap-2 sm:w-auto">
            <Button asChild variant="outline">
              <Link to={`/products/${productId}`}>
                <Eye />
                Преглед
              </Link>
            </Button>
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
    </div>
  );
}
