import { useEffect, useRef, useState, type ReactNode } from 'react';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight, Trash2, X } from 'lucide-react';
import { useTable, type ColumnDef, type RowData, type SortingState } from '@tanstack/react-table';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/Button';
import { dataTableFeatures, type DataTableFeatures } from '@/components/data-table/features';
import { HelpHint } from '@/components/ui/HelpHint';
import { Tooltip } from '@/components/ui/Tooltip';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { scrollPageToTop } from '@/lib/scroll';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { toastError } from '@/lib/toast';

export const DATA_TABLE_PAGE_SIZES = [10, 20, 50, 100] as const;
export const DEFAULT_PAGE_SIZE = 20;

export { scrollPageToTop };

function goToPage(pagination: DataTablePagination, page: number) {
  if (page === pagination.page) {
    return;
  }

  pagination.onPageChange(page);
  scrollPageToTop();
}

const EMPTY_DATA: never[] = [];

function getPageItems(page: number, lastPage: number): Array<number | 'ellipsis'> {
  const total = Math.max(1, lastPage);

  if (total <= 7) {
    return Array.from({ length: total }, (_, index) => index + 1);
  }

  const start = Math.max(2, page - 1);
  const end = Math.min(total - 1, page + 1);
  const items: Array<number | 'ellipsis'> = [1];

  if (start > 2) {
    items.push('ellipsis');
  }

  for (let current = start; current <= end; current += 1) {
    items.push(current);
  }

  if (end < total - 1) {
    items.push('ellipsis');
  }

  items.push(total);

  return items;
}

export type DataTablePagination = {
  page: number;
  lastPage: number;
  total: number;
  pageSize: number;
  pageSizeOptions?: readonly number[];
  onPageChange: (page: number) => void;
  onPageSizeChange: (pageSize: number) => void;
};

type DataTableProps<TData extends RowData> = {
  columns: ColumnDef<DataTableFeatures, TData>[];
  data: TData[];
  getRowId?: (row: TData, index: number) => string;
  loading?: boolean;
  emptyMessage?: string;
  caption?: string;
  pagination?: DataTablePagination;
  onBulkDelete?: (rows: TData[]) => Promise<void>;
  renderBulkActions?: (context: { rows: TData[]; busy: boolean; run: (action: () => Promise<void>) => void; clear: () => void }) => ReactNode;
  isRowSelectable?: (row: TData) => boolean;
};

function SelectionCheckbox({ checked, indeterminate = false, label, disabled = false, onChange }: { checked: boolean; indeterminate?: boolean; label: string; disabled?: boolean; onChange: (checked: boolean) => void }) {
  const ref = useRef<HTMLInputElement>(null);
  useEffect(() => { if (ref.current) ref.current.indeterminate = indeterminate; }, [indeterminate]);
  return <input ref={ref} type="checkbox" className="data-table-checkbox" checked={checked} disabled={disabled} aria-label={label} onChange={(event) => onChange(event.target.checked)} />;
}

export function DataTable<TData extends RowData>({
  columns,
  data,
  getRowId,
  loading = false,
  emptyMessage = 'Няма записи.',
  caption,
  pagination,
  onBulkDelete,
  renderBulkActions,
  isRowSelectable = () => true,
}: DataTableProps<TData>) {
  const [sorting, setSorting] = useState<SortingState>([]);
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [confirmBulkDelete, setConfirmBulkDelete] = useState(false);
  const [bulkBusy, setBulkBusy] = useState(false);
  const bulkEnabled = Boolean(onBulkDelete || renderBulkActions);
  const table = useTable({
    features: dataTableFeatures,
    columns,
    data: data.length === 0 ? (EMPTY_DATA as TData[]) : data,
    getRowId,
    state: { sorting },
    onSortingChange: setSorting,
  });
  const pageRows = table.getRowModel().rows;
  const selectableRows = pageRows.filter((row) => isRowSelectable(row.original));
  const selectedRows = pageRows.filter((row) => selected.has(row.id));
  const allSelected = selectableRows.length > 0 && selectableRows.every((row) => selected.has(row.id));
  const someSelected = selectableRows.some((row) => selected.has(row.id)) && !allSelected;

  useEffect(() => {
    const available = new Set(pageRows.map((row) => row.id));
    setSelected((current) => new Set([...current].filter((id) => available.has(id))));
  }, [data]);

  function selectAll(checked: boolean) {
    setSelected(checked ? new Set(selectableRows.map((row) => row.id)) : new Set());
  }

  async function bulkDelete() {
    if (!onBulkDelete || selectedRows.length === 0) return;
    setBulkBusy(true);
    try {
      await onBulkDelete(selectedRows.map((row) => row.original));
      setSelected(new Set());
      setConfirmBulkDelete(false);
    } catch (error) { toastError(error, 'Масовото действие не беше успешно.'); }
    finally { setBulkBusy(false); }
  }

  async function runBulkAction(action: () => Promise<void>) {
    setBulkBusy(true);
    try {
      await action();
      setSelected(new Set());
    } catch (error) { toastError(error, 'Масовото действие не беше успешно.'); }
    finally { setBulkBusy(false); }
  }

  return (
    <div className="grid gap-3">
      {bulkEnabled && selectedRows.length > 0 ? <div className="data-table-bulk-bar" role="toolbar" aria-label="Масови действия"><strong>{selectedRows.length} избрани</strong><div>{renderBulkActions?.({ rows: selectedRows.map((row) => row.original), busy: bulkBusy, run: (action) => void runBulkAction(action), clear: () => setSelected(new Set()) })}{onBulkDelete ? <Button type="button" variant="destructive" size="sm" disabled={bulkBusy} onClick={() => setConfirmBulkDelete(true)}><Trash2 />Изтрий избраните</Button> : null}<Button type="button" variant="ghost" size="sm" disabled={bulkBusy} onClick={() => setSelected(new Set())}><X />Откажи избора</Button></div></div> : null}
      <div className="overflow-hidden border border-border bg-card" aria-busy={loading}>
        <Table>
          {caption ? <caption className="sr-only">{caption}</caption> : null}
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id} className="hover:bg-transparent">
                {bulkEnabled ? <TableHead className="w-12 bg-muted px-4 py-3"><SelectionCheckbox checked={allSelected} indeterminate={someSelected} disabled={selectableRows.length === 0 || loading} label="Избери всички записи на страницата" onChange={selectAll} /></TableHead> : null}
                {headerGroup.headers.map((header) => {
                  const meta = header.column.columnDef.meta;
                  const canSort = header.column.getCanSort();
                  const sorted = header.column.getIsSorted();

                  return (
                    <TableHead
                      key={header.id}
                      className={cn(
                        'bg-muted px-4 py-3 font-sans text-sm font-extrabold tracking-wide text-muted-foreground uppercase',
                        meta?.sticky && `sticky ${bulkEnabled ? 'left-12' : 'left-0'} z-10 bg-muted`,
                        meta?.className
                      )}
                    >
                      {header.isPlaceholder ? null : (
                        <div
                          className={cn(
                            'flex items-center gap-1',
                            meta?.className?.includes('text-right') && 'justify-end'
                          )}
                        >
                          {canSort ? (
                            <Tooltip
                              content={
                                sorted === 'asc'
                                  ? 'Сортирано възходящо. Кликни за низходящо.'
                                  : sorted === 'desc'
                                    ? 'Сортирано низходящо. Кликни, за да махнеш сортирането.'
                                    : 'Сортирай тази колона.'
                              }
                            >
                              <button
                                type="button"
                                className="inline-flex items-center gap-1.5 rounded-md px-1 py-1 font-[inherit] text-inherit uppercase hover:text-foreground"
                                onClick={header.column.getToggleSortingHandler()}
                              >
                                <table.FlexRender header={header} />
                                {sorted === 'asc' ? (
                                  <ArrowUp className="size-3.5" aria-hidden />
                                ) : sorted === 'desc' ? (
                                  <ArrowDown className="size-3.5" aria-hidden />
                                ) : (
                                  <ArrowUpDown className="size-3.5 opacity-50" aria-hidden />
                                )}
                              </button>
                            </Tooltip>
                          ) : (
                            <table.FlexRender header={header} />
                          )}
                          {meta?.help ? (
                            <HelpHint label={String(header.column.columnDef.header || header.id)} className="normal-case tracking-normal">
                              {meta.help}
                            </HelpHint>
                          ) : null}
                        </div>
                      )}
                    </TableHead>
                  );
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {pageRows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={columns.length + (bulkEnabled ? 1 : 0)} className="h-24 text-center font-sans text-muted-foreground">
                  {emptyMessage}
                </TableCell>
              </TableRow>
            ) : (
              pageRows.map((row) => (
                <TableRow key={row.id} className="group">
                  {bulkEnabled ? <TableCell className="w-12 px-4 py-3"><SelectionCheckbox checked={selected.has(row.id)} disabled={!isRowSelectable(row.original) || loading || bulkBusy} label={`Избери запис ${row.id}`} onChange={(checked) => setSelected((current) => { const next = new Set(current); if (checked) next.add(row.id); else next.delete(row.id); return next; })} /></TableCell> : null}
                  {row.getAllCells().map((cell) => {
                    const meta = cell.column.columnDef.meta;

                    return (
                      <TableCell
                        key={cell.id}
                        className={cn(
                          'px-4 py-3 font-sans',
                          meta?.sticky && `sticky ${bulkEnabled ? 'left-12' : 'left-0'} z-10 bg-card group-hover:bg-muted`,
                          meta?.className
                        )}
                      >
                        <table.FlexRender cell={cell} />
                      </TableCell>
                    );
                  })}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {pagination ? (
        <div className="pager">
          <p className="m-0 font-sans text-sm text-muted-foreground">
            Страница {pagination.page} от {Math.max(1, pagination.lastPage)} · {pagination.total} записа
          </p>
          <div className="flex flex-wrap items-center gap-3">
            <label className="flex items-center gap-2 font-sans text-sm text-muted-foreground">
              <span>На страница</span>
              <Select
                value={String(pagination.pageSize)}
                disabled={loading}
                onValueChange={(value) => {
                  const nextSize = Number(value);
                  if (nextSize === pagination.pageSize) {
                    return;
                  }
                  pagination.onPageSizeChange(nextSize);
                  scrollPageToTop();
                }}
              >
                <SelectTrigger
                  id="page-size"
                  size="sm"
                  className="w-[4.75rem] min-h-9 font-sans"
                  aria-label="Записи на страница"
                >
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {(pagination.pageSizeOptions ?? DATA_TABLE_PAGE_SIZES).map((size) => (
                    <SelectItem key={size} value={String(size)}>
                      {size}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </label>
            <nav className="flex flex-wrap items-center gap-1" aria-label="Страници">
              <Button
                type="button"
                variant="outline"
                size="icon"
                disabled={pagination.page <= 1 || loading}
                aria-label="Предишна страница"
                onClick={() => goToPage(pagination, pagination.page - 1)}
              >
                <ChevronLeft />
              </Button>
              {getPageItems(pagination.page, pagination.lastPage).map((item, index) =>
                item === 'ellipsis' ? (
                  <span
                    key={`ellipsis-${index}`}
                    className="grid size-9 place-items-center font-sans text-sm text-muted-foreground"
                    aria-hidden
                  >
                    …
                  </span>
                ) : (
                  <Button
                    key={item}
                    type="button"
                    variant={item === pagination.page ? 'default' : 'outline'}
                    size="icon"
                    disabled={loading || item === pagination.page}
                    aria-label={`Страница ${item}`}
                    aria-current={item === pagination.page ? 'page' : undefined}
                    onClick={() => goToPage(pagination, item)}
                  >
                    {item}
                  </Button>
                )
              )}
              <Button
                type="button"
                variant="outline"
                size="icon"
                disabled={pagination.page >= pagination.lastPage || loading || pagination.lastPage <= 1}
                aria-label="Следваща страница"
                onClick={() => goToPage(pagination, pagination.page + 1)}
              >
                <ChevronRight />
              </Button>
            </nav>
          </div>
        </div>
      ) : null}
      {confirmBulkDelete ? <ConfirmDialog title="Масово изтриване" message={`Ще бъдат изтрити ${selectedRows.length} избрани записа. Те могат да бъдат възстановени по-късно.`} confirmLabel="Изтрий избраните" busy={bulkBusy} onCancel={() => setConfirmBulkDelete(false)} onConfirm={() => void bulkDelete()} /> : null}
    </div>
  );
}
