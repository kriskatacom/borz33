import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { CopyPlus, Plus, Save, Trash2 } from 'lucide-react';
import { listCategoryTree, type CategoryTreeNode } from '@/api/categories';
import { createProductAttributeTemplate, deleteProductAttributeTemplate, listProductAttributeTemplates, type ProductAttributeTemplate } from '@/api/products';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { PageHeader } from '@/components/page-header';
import { PageTreeSelect } from '@/features/pages/PageTreeSelect';
import { flattenCategoryTree } from '@/features/categories/categoryTree';
import { toast, toastError } from '@/lib/toast';

type ParameterRow = { key: string; name: string; value: string };
type OptionRow = { key: string; name: string; values: string };
let sequence = 0;
const key = () => `template-${++sequence}`;

export function ProductTemplatesPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [templates, setTemplates] = useState<ProductAttributeTemplate[]>([]);
  const [tree, setTree] = useState<CategoryTreeNode[]>([]);
  const [name, setName] = useState('');
  const [categoryId, setCategoryId] = useState('none');
  const [parameters, setParameters] = useState<ParameterRow[]>([]);
  const [options, setOptions] = useState<OptionRow[]>([]);
  const [busy, setBusy] = useState(false);

  async function load() {
    try {
      const [templatesResponse, treeResponse] = await Promise.all([listProductAttributeTemplates(token), listCategoryTree(token)]);
      setTemplates(templatesResponse.data.templates);
      setTree(treeResponse.data.categories);
    } catch (error) { toastError(error, 'Шаблоните не можаха да се заредят.'); }
  }
  useEffect(() => { void load(); }, [token]);

  async function submit(event: FormEvent) {
    event.preventDefault(); setBusy(true);
    try {
      const response = await createProductAttributeTemplate(token, {
        name: name.trim(), category_id: categoryId === 'none' ? null : Number(categoryId),
        parameters: parameters.map(({ name: parameterName, value }) => ({ name: parameterName.trim(), value: value.trim() })),
        options: options.map((option) => ({ name: option.name.trim(), values: option.values.split(',').map((value) => value.trim()).filter(Boolean).map((value) => ({ name: value })) })),
      });
      setTemplates((current) => [...current, response.data.template].sort((a, b) => a.name.localeCompare(b.name, 'bg')));
      setName(''); setCategoryId('none'); setParameters([]); setOptions([]);
      toast.success(response.message || 'Шаблонът е създаден.');
    } catch (error) { toastError(error, 'Шаблонът не можа да бъде създаден.'); }
    finally { setBusy(false); }
  }

  async function remove(template: ProductAttributeTemplate) {
    if (!window.confirm(`Да бъде ли изтрит шаблонът „${template.name}“? Това не променя вече приложени продукти.`)) return;
    try { const response = await deleteProductAttributeTemplate(token, template.id); setTemplates((current) => current.filter((item) => item.id !== template.id)); toast.success(response.message || 'Шаблонът е изтрит.'); }
    catch (error) { toastError(error, 'Шаблонът не можа да бъде изтрит.'); }
  }

  return <div className="page">
    <PageHeader title="Шаблони за продукти" help="Еднократно подгответе параметри, опции и стойности. При прилагане данните се копират в продукта и остават независими от шаблона." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Продукти', to: routes.products }, { label: 'Шаблони' }]} actions={<Button asChild variant="outline"><Link to={routes.products}><CopyPlus />Към продуктите</Link></Button>} />
    <form className="grid gap-4 border border-border bg-card p-4" onSubmit={(event) => void submit(event)}>
      <h2 className="m-0 text-xl">Нов шаблон</h2>
      <div className="form-grid"><Field id="template-name" label="Име" help="Например „Тениска с размер и цвят“." value={name} onChange={(event) => setName(event.target.value)} /><PageTreeSelect id="template-category" label="Предложи за категория" help="Незадължително. Шаблонът ще се отбелязва като подходящ при тази категория." value={categoryId} options={flattenCategoryTree(tree)} extra={[{ value: 'none', label: 'Всички категории' }]} onValueChange={setCategoryId} /></div>
      <div className="grid gap-3 md:grid-cols-2">
        <section className="grid gap-2 rounded-[6px] border border-border p-3"><h3 className="m-0 text-base">Параметри</h3><p className="m-0 text-sm text-muted-foreground">Стойностите са начални и могат да се редактират след прилагане.</p>{parameters.map((row, index) => <div key={row.key} className="grid grid-cols-[1fr_1fr_auto] gap-2"><Field id={`${row.key}-name`} label="" placeholder="Материя" value={row.name} onChange={(event) => setParameters((items) => items.map((item, i) => i === index ? { ...item, name: event.target.value } : item))} /><Field id={`${row.key}-value`} label="" placeholder="100% памук" value={row.value} onChange={(event) => setParameters((items) => items.map((item, i) => i === index ? { ...item, value: event.target.value } : item))} /><Button type="button" variant="outline" size="icon" aria-label="Премахни параметър" onClick={() => setParameters((items) => items.filter((_, i) => i !== index))}><Trash2 /></Button></div>)}<Button type="button" variant="outline" onClick={() => setParameters((items) => [...items, { key: key(), name: '', value: '' }])}><Plus />Параметър</Button></section>
        <section className="grid gap-2 rounded-[6px] border border-border p-3"><h3 className="m-0 text-base">Опции и стойности</h3><p className="m-0 text-sm text-muted-foreground">Стойностите се разделят със запетая, напр. S, M, L, XL.</p>{options.map((row, index) => <div key={row.key} className="grid grid-cols-[minmax(8rem,.8fr)_minmax(10rem,1.2fr)_auto] gap-2"><Field id={`${row.key}-name`} label="" placeholder="Размер" value={row.name} onChange={(event) => setOptions((items) => items.map((item, i) => i === index ? { ...item, name: event.target.value } : item))} /><Field id={`${row.key}-values`} label="" placeholder="S, M, L" value={row.values} onChange={(event) => setOptions((items) => items.map((item, i) => i === index ? { ...item, values: event.target.value } : item))} /><Button type="button" variant="outline" size="icon" aria-label="Премахни опция" onClick={() => setOptions((items) => items.filter((_, i) => i !== index))}><Trash2 /></Button></div>)}<Button type="button" variant="outline" onClick={() => setOptions((items) => [...items, { key: key(), name: '', values: '' }])}><Plus />Опция</Button></section>
      </div><div><Button type="submit" disabled={busy}><Save />{busy ? 'Запис…' : 'Създай шаблон'}</Button></div>
    </form>
    <section className="mt-4 grid gap-3"><h2 className="m-0 text-xl">Запазени шаблони</h2>{templates.length === 0 ? <p className="m-0 text-muted-foreground">Все още няма шаблони.</p> : templates.map((template) => <article key={template.id} className="grid gap-2 border border-border bg-card p-4"><div className="flex flex-wrap items-center justify-between gap-2"><div><h3 className="m-0 text-lg">{template.name}</h3><p className="m-0 text-sm text-muted-foreground">{template.category?.name ?? 'Всички категории'} · {template.parameters.length} параметъра · {template.options.length} опции</p></div><Button type="button" variant="outline" size="icon" aria-label={`Изтрий ${template.name}`} onClick={() => void remove(template)}><Trash2 /></Button></div>{template.options.length ? <p className="m-0 text-sm">{template.options.map((option) => `${option.name}: ${option.values.map((value) => value.name).join(', ')}`).join(' · ')}</p> : null}</article>)}</section>
  </div>;
}
