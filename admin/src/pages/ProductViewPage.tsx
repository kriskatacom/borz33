import { useEffect, useState, type ReactNode } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Images, Layers, List, Palette, Pencil, Shirt, Type } from 'lucide-react';
import { ApiError } from '@/api/client';
import { getProduct, type AdminProduct, type ProductImage } from '@/api/products';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatMoney } from '@/lib/format';
import { toast } from '@/lib/toast';
import { ImageLightbox, ProductImagesPreview } from '@/features/products/ProductImagesSection';

function DetailTable({ children, caption }: { children: ReactNode; caption: string }) {
  return (
    <div className="min-w-0 overflow-hidden border border-border bg-card">
      <Table>
        <caption className="sr-only">{caption}</caption>
        {children}
      </Table>
    </div>
  );
}

function DetailHead({ children }: { children: ReactNode }) {
  return (
    <TableHead className="bg-muted px-4 py-3 font-sans text-sm font-extrabold tracking-wide text-muted-foreground uppercase">
      {children}
    </TableHead>
  );
}

function DetailCell({ children }: { children: ReactNode }) {
  return <TableCell className="px-4 py-3 font-sans">{children}</TableCell>;
}

function statusLabel(product: AdminProduct): { text: string; className: string } {
  if (product.deleted_at) {
    return { text: 'Изтрит', className: 'badge warn' };
  }

  if (product.is_active) {
    return { text: 'Активен', className: 'badge ok' };
  }

  return { text: 'Неактивен', className: 'badge idle' };
}

function ColorDot({ hex }: { hex: string | null }) {
  if (!hex) {
    return null;
  }

  return (
    <span
      className="inline-block size-3.5 rounded-full border border-border align-middle"
      style={{ backgroundColor: hex }}
      title={hex}
    />
  );
}

function VariantImageThumb({ image, label }: { image: ProductImage | null; label: string }) {
  const [open, setOpen] = useState(false);

  if (!image?.url) {
    return (
      <div className="flex size-12 items-center justify-center overflow-hidden rounded-[6px] border border-border bg-muted">
        <Shirt className="size-5 text-muted-foreground" aria-hidden />
      </div>
    );
  }

  return (
    <>
      <button
        type="button"
        className="flex size-12 cursor-zoom-in items-center justify-center overflow-hidden rounded-[6px] border border-border bg-muted p-0 outline-none transition-opacity hover:opacity-90 focus-visible:ring-[3px] focus-visible:ring-ring/50"
        onClick={() => setOpen(true)}
      >
        <img src={image.url} alt={label} className="size-full object-cover" />
      </button>
      {open ? <ImageLightbox images={[image]} index={0} onIndex={() => undefined} onClose={() => setOpen(false)} /> : null}
    </>
  );
}

export function ProductViewPage() {
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

  const status = product ? statusLabel(product) : null;

  return (
    <div className="page min-w-0">
      <PageHeader
        title={product?.name ?? 'Продукт'}
        help="Преглед на продукт, изображения, параметри, опции, варианти и персонализация."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Продукти', to: routes.products },
          { label: product?.name ?? 'Преглед' },
        ]}
        actions={
          <div className="flex w-full flex-wrap gap-2 sm:w-auto">
            {product && !product.deleted_at ? (
              <Button asChild>
                <Link to={`/products/${product.id}/edit`}>
                  <Pencil />
                  Редакция
                </Link>
              </Button>
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

      {product ? (
        <div className="flex min-w-0 max-w-full flex-col gap-3">
          <CollapsibleSection title="Изображения" icon={Images} help="Предна снимка и галерия на продукта.">
            <ProductImagesPreview product={product} />
          </CollapsibleSection>
          <CollapsibleSection title="Общи данни" icon={Shirt} help="Име, цена, статус и описание в каталога.">
            <div className="min-w-0">
              <p className="m-0 flex flex-wrap items-center gap-2">
                <span className={status?.className}>{status?.text}</span>
                {product.personalization_enabled ? <span className="badge info">Персонализация</span> : null}
              </p>
              <dl className="mt-3 grid gap-2 sm:grid-cols-2">
                <div>
                  <dt className="text-muted-foreground">SKU</dt>
                  <dd className="m-0 font-bold">{product.sku || '—'}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Slug</dt>
                  <dd className="m-0 font-mono text-sm">{product.slug}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Цена</dt>
                  <dd className="m-0 font-bold">{formatMoney(product.price)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Сравнителна цена</dt>
                  <dd className="m-0">{formatMoney(product.compare_at_price)}</dd>
                </div>
              </dl>
              {product.short_description ? <p className="mb-0 mt-4">{product.short_description}</p> : null}
              {product.description ? (
                <p className="mb-0 mt-3 whitespace-pre-wrap text-muted-foreground">{product.description}</p>
              ) : null}
            </div>
          </CollapsibleSection>

          <CollapsibleSection title="Параметри" icon={List} help="Характеристики като материя и грамаж.">
            {product.parameters.length === 0 ? (
              <p className="mb-0 text-muted-foreground">Няма информационни параметри.</p>
            ) : (
              <div className="grid gap-3">
                {product.parameters.map((row) => (
                  <CollapsibleSection key={row.id} heading="h3" persistKey={`parameter:${row.id}`} title={row.name}>
                    <p className="m-0">{row.value}</p>
                  </CollapsibleSection>
                ))}
              </div>
            )}
          </CollapsibleSection>

          <CollapsibleSection title="Опции" icon={Palette} help="Размер, цвят и други избори за вариантите.">
            {product.options.length === 0 ? (
              <p className="mb-0 text-muted-foreground">Няма опции за избор.</p>
            ) : (
              <div className="grid gap-3">
                {product.options.map((option) => (
                  <CollapsibleSection key={option.id} heading="h3" persistKey={`option:${option.id}`} title={option.name}>
                    <ul className="m-0 flex list-none flex-wrap gap-2 p-0">
                      {option.values.map((value) => (
                        <li key={value.id} className="badge idle gap-2">
                          <ColorDot hex={value.hex_color} />
                          {value.name}
                        </li>
                      ))}
                    </ul>
                  </CollapsibleSection>
                ))}
              </div>
            )}
          </CollapsibleSection>

          <CollapsibleSection title="Варианти" icon={Layers} help="Комбинации за покупка, всяка със своя цена и снимка.">
            {product.variants.length === 0 ? (
              <p className="mb-0 text-muted-foreground">Няма варианти.</p>
            ) : (
              <DetailTable caption="Варианти на продукта">
                <TableHeader>
                  <TableRow className="hover:bg-transparent">
                    <DetailHead>Снимка</DetailHead>
                    <DetailHead>Вариант</DetailHead>
                    <DetailHead>SKU</DetailHead>
                    <DetailHead>Цена</DetailHead>
                    <DetailHead>Наличност</DetailHead>
                    <DetailHead>Статус</DetailHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {product.variants.map((variant) => (
                    <TableRow key={variant.id}>
                      <DetailCell>
                        <VariantImageThumb image={variant.image} label={variant.image?.alt || variant.name || variant.sku || 'Вариант'} />
                      </DetailCell>
                      <DetailCell>
                        <span className="inline-flex flex-col items-start gap-1">
                          <span className="inline-flex flex-wrap items-center gap-2">
                            {variant.name ? <span>{variant.name}</span> : null}
                            {variant.option_values.map((value) => (
                              <span key={`${variant.id}-${value.option}-${value.value}`} className="inline-flex items-center gap-1">
                                <ColorDot hex={value.hex_color} />
                                {value.value_name ?? value.value}
                              </span>
                            ))}
                            {variant.is_default ? <span className="badge info">По подразбиране</span> : null}
                          </span>
                        </span>
                      </DetailCell>
                      <DetailCell>{variant.sku || '—'}</DetailCell>
                      <DetailCell>{formatMoney(variant.price ?? product.price)}</DetailCell>
                      <DetailCell>{variant.stock}</DetailCell>
                      <DetailCell>
                        <span className={`badge ${variant.is_active ? 'ok' : 'idle'}`}>
                          {variant.is_active ? 'Активен' : 'Неактивен'}
                        </span>
                      </DetailCell>
                    </TableRow>
                  ))}
                </TableBody>
              </DetailTable>
            )}
          </CollapsibleSection>

          {product.personalization_enabled ? (
            <CollapsibleSection title="Персонализация" icon={Type} help="Текст, който клиентът въвежда преди добавяне в количката.">
              <p className="mt-0">{product.personalization_label || 'Текст за персонализация'}</p>
              {product.personalization_description ? (
                <p className="text-muted-foreground">{product.personalization_description}</p>
              ) : null}
              {product.personalization_fields.length > 0 ? (
                <DetailTable caption="Полета за персонализация">
                  <TableHeader>
                    <TableRow className="hover:bg-transparent">
                      <DetailHead>Поле</DetailHead>
                      <DetailHead>Тип</DetailHead>
                      <DetailHead>Задължително</DetailHead>
                      <DetailHead>Макс. дължина</DetailHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {product.personalization_fields.map((field) => (
                      <TableRow key={field.id}>
                        <DetailCell>
                          <p className="m-0 font-bold">{field.name}</p>
                          {field.description ? (
                            <p className="mb-0 mt-1 text-muted-foreground">{field.description}</p>
                          ) : null}
                        </DetailCell>
                        <DetailCell>{field.field_type === 'textarea' ? 'Многоредов текст' : 'Текст'}</DetailCell>
                        <DetailCell>{field.is_required ? 'Да' : 'Не'}</DetailCell>
                        <DetailCell>{field.max_length ?? '—'}</DetailCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </DetailTable>
              ) : null}
            </CollapsibleSection>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}
