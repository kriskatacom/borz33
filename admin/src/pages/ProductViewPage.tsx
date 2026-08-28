import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Shirt } from 'lucide-react';
import { ApiError } from '@/api/client';
import { getProduct, type AdminProduct } from '@/api/products';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { FormSection } from '@/components/ui/form-section';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatMoney } from '@/lib/format';

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
          setMessage(error instanceof ApiError ? error.message : 'Продуктът не можа да се зареди.');
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
  const frontUrl = product?.front_image?.url;

  return (
    <div className="page">
      <PageHeader
        title={product?.name ?? 'Продукт'}
        help="Преглед на продукт, параметри, опции, варианти и персонализация. Редакцията ще бъде отделен екран."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Продукти', to: routes.products },
          { label: product?.name ?? 'Преглед' },
        ]}
        actions={
          <Button asChild variant="outline">
            <Link to={routes.products}>
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

      {product ? (
        <div className="form-grid">
          <FormSection>
            <div className="flex flex-wrap gap-4">
              <div className="flex size-24 items-center justify-center overflow-hidden rounded-[6px] border border-border bg-muted">
                {frontUrl ? (
                  <img
                    src={frontUrl}
                    alt={product.front_image?.alt || product.name}
                    className="size-full object-cover"
                  />
                ) : (
                  <Shirt className="size-8 text-muted-foreground" aria-hidden />
                )}
              </div>
              <div className="min-w-0 flex-1">
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
              </div>
            </div>
            {product.short_description ? <p className="mb-0 mt-4">{product.short_description}</p> : null}
            {product.description ? (
              <p className="mb-0 mt-3 whitespace-pre-wrap text-muted-foreground">{product.description}</p>
            ) : null}
          </FormSection>

          <FormSection>
            <h2 className="section-label mt-0">Параметри</h2>
            {product.parameters.length === 0 ? (
              <p className="mb-0 text-muted-foreground">Няма информационни параметри.</p>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Име</TableHead>
                    <TableHead>Стойност</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {product.parameters.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell>{row.name}</TableCell>
                      <TableCell>{row.value}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </FormSection>

          <FormSection>
            <h2 className="section-label mt-0">Опции</h2>
            {product.options.length === 0 ? (
              <p className="mb-0 text-muted-foreground">Няма опции за избор.</p>
            ) : (
              <div className="grid gap-4">
                {product.options.map((option) => (
                  <div key={option.id}>
                    <p className="mb-2 mt-0 font-bold">{option.name}</p>
                    <ul className="m-0 flex list-none flex-wrap gap-2 p-0">
                      {option.values.map((value) => (
                        <li key={value.id} className="badge idle gap-2">
                          <ColorDot hex={value.hex_color} />
                          {value.name}
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}
              </div>
            )}
          </FormSection>

          <FormSection>
            <h2 className="section-label mt-0">Варианти</h2>
            {product.variants.length === 0 ? (
              <p className="mb-0 text-muted-foreground">Няма варианти.</p>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Вариант</TableHead>
                    <TableHead>SKU</TableHead>
                    <TableHead>Цена</TableHead>
                    <TableHead>Наличност</TableHead>
                    <TableHead>Статус</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {product.variants.map((variant) => (
                    <TableRow key={variant.id}>
                      <TableCell>
                        <span className="flex flex-wrap items-center gap-2">
                          {variant.option_values.map((value) => (
                            <span key={`${variant.id}-${value.option}-${value.value}`} className="inline-flex items-center gap-1">
                              <ColorDot hex={value.hex_color} />
                              {value.value_name ?? value.value}
                            </span>
                          ))}
                          {variant.is_default ? <span className="badge info">По подразбиране</span> : null}
                        </span>
                      </TableCell>
                      <TableCell>{variant.sku || '—'}</TableCell>
                      <TableCell>{formatMoney(variant.price ?? product.price)}</TableCell>
                      <TableCell>{variant.stock}</TableCell>
                      <TableCell>
                        <span className={`badge ${variant.is_active ? 'ok' : 'idle'}`}>
                          {variant.is_active ? 'Активен' : 'Неактивен'}
                        </span>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </FormSection>

          {product.personalization_enabled ? (
            <FormSection>
              <h2 className="section-label mt-0">Персонализация</h2>
              <p className="mt-0">{product.personalization_label || 'Текст за персонализация'}</p>
              {product.personalization_description ? (
                <p className="text-muted-foreground">{product.personalization_description}</p>
              ) : null}
              {product.personalization_fields.length > 0 ? (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Поле</TableHead>
                      <TableHead>Тип</TableHead>
                      <TableHead>Задължително</TableHead>
                      <TableHead>Макс. дължина</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {product.personalization_fields.map((field) => (
                      <TableRow key={field.id}>
                        <TableCell>
                          <p className="m-0 font-bold">{field.name}</p>
                          {field.description ? (
                            <p className="mb-0 mt-1 text-muted-foreground">{field.description}</p>
                          ) : null}
                        </TableCell>
                        <TableCell>{field.field_type === 'textarea' ? 'Многоредов текст' : 'Текст'}</TableCell>
                        <TableCell>{field.is_required ? 'Да' : 'Не'}</TableCell>
                        <TableCell>{field.max_length ?? '—'}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              ) : null}
            </FormSection>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}
