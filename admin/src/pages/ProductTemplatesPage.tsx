import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { CopyPlus, Pencil, Plus, Save, Trash2, X } from 'lucide-react';
import { listCategoryTree, type CategoryTreeNode } from '@/api/categories';
import { createProductAttributeTemplate, deleteProductAttributeTemplate, listProductAttributeTemplates, updateProductAttributeTemplate, type ProductAttributeTemplate } from '@/api/products';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { HelpHint } from '@/components/ui/HelpHint';
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
  const [editingId, setEditingId] = useState<number | null>(null);
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
      const body = {
        name: name.trim(), category_id: categoryId === 'none' ? null : Number(categoryId),
        parameters: parameters.map(({ name: parameterName, value }) => ({ name: parameterName.trim(), value: value.trim() })),
        options: options.map((option) => ({ name: option.name.trim(), values: option.values.split(',').map((value) => value.trim()).filter(Boolean).map((value) => ({ name: value })) })),
      };
      const response = editingId === null
        ? await createProductAttributeTemplate(token, body)
        : await updateProductAttributeTemplate(token, editingId, body);
      setTemplates((current) => {
        const next = editingId === null
          ? [...current, response.data.template]
          : current.map((item) => item.id === response.data.template.id ? response.data.template : item);
        return next.sort((a, b) => a.name.localeCompare(b.name, 'bg'));
      });
      resetForm();
      toast.success(response.message || (editingId === null ? 'Шаблонът е създаден.' : 'Шаблонът е обновен.'));
    } catch (error) { toastError(error, 'Шаблонът не можа да бъде създаден.'); }
    finally { setBusy(false); }
  }

  function resetForm() {
    setEditingId(null); setName(''); setCategoryId('none'); setParameters([]); setOptions([]);
  }

  function edit(template: ProductAttributeTemplate) {
    setEditingId(template.id);
    setName(template.name);
    setCategoryId(template.category_id === null ? 'none' : String(template.category_id));
    setParameters(template.parameters.map((row) => ({ key: key(), name: row.name, value: row.value })));
    setOptions(template.options.map((option) => ({ key: key(), name: option.name, values: option.values.map((value) => value.name).join(', ') })));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  async function remove(template: ProductAttributeTemplate) {
    if (!window.confirm(`Да бъде ли изтрит шаблонът „${template.name}“? Това не променя вече приложени продукти.`)) return;
    try { const response = await deleteProductAttributeTemplate(token, template.id); setTemplates((current) => current.filter((item) => item.id !== template.id)); toast.success(response.message || 'Шаблонът е изтрит.'); }
    catch (error) { toastError(error, 'Шаблонът не можа да бъде изтрит.'); }
  }

  return <div className="page">
    <PageHeader title="Шаблони за продукти" help="Еднократно подгответе параметри, опции и стойности. При прилагане данните се копират в продукта и остават независими от шаблона." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Продукти', to: routes.products }, { label: 'Шаблони' }]} actions={<Button asChild variant="outline"><Link to={routes.products}><CopyPlus />Към продуктите</Link></Button>} />
    <form className="grid gap-4 border border-border bg-card p-4" onSubmit={(event) => void submit(event)}>
      <div className="flex flex-wrap items-center justify-between gap-2"><h2 className="m-0 text-xl">{editingId === null ? 'Нов шаблон' : 'Редактиране на шаблон'}</h2>{editingId !== null ? <Button type="button" variant="outline" onClick={resetForm}><X />Отказ</Button> : null}</div>
      <div className="form-grid"><Field id="template-name" label="Име" help="Вътрешно име, по което ще намирате шаблона при редакция на продукт. То не се показва на клиентите." value={name} onChange={(event) => setName(event.target.value)} /><PageTreeSelect id="template-category" label="Предложи за категория" help="Категория, за която шаблонът е подходящ. Това е ориентир за администратора и не променя автоматично продуктите." value={categoryId} options={flattenCategoryTree(tree)} extra={[{ value: 'none', label: 'Всички категории' }]} onValueChange={setCategoryId} /></div>
      <div className="grid min-w-0 gap-3">
        <section className="grid min-w-0 gap-3 rounded-[10px] border border-border bg-card p-3 sm:p-4"><div className="flex items-center gap-1"><h3 className="m-0 text-base">Параметри</h3><HelpHint label="Параметри">Характеристики на самия продукт, например материя, кройка или грамаж. Те се показват в таб „Параметри“ в клиентската зона и не създават отделни варианти.</HelpHint></div><p className="m-0 text-sm leading-relaxed text-muted-foreground">Използвайте ги за обща информация, една стойност за целия продукт. Всеки ред е отделен параметър, например „Материя → 100% памук“.</p>{parameters.map((row, index) => <div key={row.key} className="grid min-w-0 grid-cols-1 gap-2 border-t border-border pt-3 first:border-t-0 first:pt-0 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end sm:border-t-0 sm:pt-0"><div className="flex items-center justify-between gap-2 sm:col-span-2"><span className="text-xs font-bold uppercase tracking-[0.08em] text-muted-foreground">Параметър {index + 1}</span><Button type="button" variant="outline" aria-label={`Премахни параметър ${index + 1}`} onClick={() => setParameters((items) => items.filter((_, i) => i !== index))}><Trash2 />Премахни</Button></div><span className="hidden sm:block" aria-hidden="true" /><Field id={`${row.key}-name`} label="Име на параметъра" placeholder="Материя" help="Например „Материя“ или „Грамаж“." value={row.name} onChange={(event) => setParameters((items) => items.map((item, i) => i === index ? { ...item, name: event.target.value } : item))} /><Field id={`${row.key}-value`} label="Стойност" placeholder="100% памук" help="Стойността, която ще се запише към продукта и ще се вижда от клиента." value={row.value} onChange={(event) => setParameters((items) => items.map((item, i) => i === index ? { ...item, value: event.target.value } : item))} /></div>)}<Button type="button" variant="outline" className="w-full sm:w-fit" onClick={() => setParameters((items) => [...items, { key: key(), name: '', value: '' }])}><Plus />Параметър</Button></section>
        <section className="grid min-w-0 gap-3 rounded-[10px] border border-border bg-card p-3 sm:p-4"><div className="flex items-center gap-1"><h3 className="m-0 text-base">Опции и стойности</h3><HelpHint label="Опции и стойности">Избори за вариантите, например Размер и Цвят. Всяка стойност участва в комбинациите и се показва като избор на продуктовата страница.</HelpHint></div><p className="m-0 text-sm leading-relaxed text-muted-foreground">Всяка група има име на опция и списък от стойности. Стойностите се разделят със запетая, например „Размер → S, M, L“.</p>{options.map((row, index) => <div key={row.key} className="grid min-w-0 grid-cols-1 gap-2 border-t border-border pt-3 first:border-t-0 first:pt-0 sm:grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)_auto] sm:items-end sm:border-t-0 sm:pt-0"><div className="flex items-center justify-between gap-2 sm:col-span-2"><span className="text-xs font-bold uppercase tracking-[0.08em] text-muted-foreground">Опция {index + 1}</span><Button type="button" variant="outline" aria-label={`Премахни опция ${index + 1}`} onClick={() => setOptions((items) => items.filter((_, i) => i !== index))}><Trash2 />Премахни</Button></div><span className="hidden sm:block" aria-hidden="true" /><Field id={`${row.key}-name`} label="Име на опцията" placeholder="Размер" help="Например „Размер“, „Цвят“ или „Материя“." value={row.name} onChange={(event) => setOptions((items) => items.map((item, i) => i === index ? { ...item, name: event.target.value } : item))} /><Field id={`${row.key}-values`} label="Стойности на опцията" placeholder="S, M, L" help="Всяка стойност се разделя със запетая, например S, M, L, XL." value={row.values} onChange={(event) => setOptions((items) => items.map((item, i) => i === index ? { ...item, values: event.target.value } : item))} /></div>)}<Button type="button" variant="outline" className="w-full sm:w-fit" onClick={() => setOptions((items) => [...items, { key: key(), name: '', values: '' }])}><Plus />Опция</Button></section>
      </div><div><Button type="submit" disabled={busy}><Save />{busy ? 'Запис…' : editingId === null ? 'Създай шаблон' : 'Запази промените'}</Button></div>
    </form>
    <section className="mt-4 grid gap-3"><h2 className="m-0 text-xl">Запазени шаблони</h2>{templates.length === 0 ? <p className="m-0 text-muted-foreground">Все още няма шаблони.</p> : templates.map((template) => <article key={template.id} className="grid gap-2 border border-border bg-card p-4"><div className="flex flex-wrap items-center justify-between gap-2"><div><h3 className="m-0 text-lg">{template.name}</h3><p className="m-0 text-sm text-muted-foreground">{template.category?.name ?? 'Всички категории'} · {template.parameters.length} параметъра · {template.options.length} опции</p></div><div className="flex gap-2"><Button type="button" variant="outline" size="icon" aria-label={`Редактирай ${template.name}`} onClick={() => edit(template)}><Pencil /></Button><Button type="button" variant="outline" size="icon" aria-label={`Изтрий ${template.name}`} onClick={() => void remove(template)}><Trash2 /></Button></div></div>{template.options.length ? <p className="m-0 text-sm">{template.options.map((option) => `${option.name}: ${option.values.map((value) => value.name).join(', ')}`).join(' · ')}</p> : null}</article>)}</section>
  </div>;
}
