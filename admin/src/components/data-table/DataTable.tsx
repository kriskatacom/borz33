import { useState } from 'react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { useTable, type ColumnDef, type RowData, type SortingState } from '@tanstack/react-table';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { dataTableFeatures, type DataTableFeatures } from '@/components/data-table/features';
import { cn } from '@/lib/utils';

const EMPTY_DATA: never[] = [];

export type DataTablePagination = {
  page: number;
  lastPage: number;
  total: number;
  onPageChange: (page: number) => void;
};

type DataTableProps<TData extends RowData> = {
  columns: ColumnDef<DataTableFeatures, TData>[];
  data: TData[];
  getRowId?: (row: TData, index: number) => string;
  loading?: boolean;
  emptyMessage?: string;
  caption?: string;
  pagination?: DataTablePagination;
};

export function DataTable<TData extends RowData>({
  columns,
  data,
  getRowId,
  loading = false,
  emptyMessage = 'Няма записи.',
  caption,
  pagination,
}: DataTableProps<TData>) {
  const [sorting, setSorting] = useState<SortingState>([]);
  const table = useTable({
    features: dataTableFeatures,
    columns,
    data: data.length === 0 ? (EMPTY_DATA as TData[]) : data,
    getRowId,
    state: { sorting },
    onSortingChange: setSorting,
  });
  const pageRows = table.getRowModel().rows;

  return (
    <div className="grid gap-3">
      <div className="overflow-hidden rounded-[22px] border border-border bg-card" aria-busy={loading}>
        <Table>
          {caption ? <caption className="sr-only">{caption}</caption> : null}
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id} className="hover:bg-transparent">
                {headerGroup.headers.map((header) => {
                  const meta = header.column.columnDef.meta;
                  const canSort = header.column.getCanSort();
                  const sorted = header.column.getIsSorted();

                  return (
                    <TableHead
                      key={header.id}
                      className={cn(
                        'bg-card px-4 py-3 font-sans text-xs font-extrabold tracking-wide text-muted-foreground uppercase',
                        meta?.sticky && 'sticky left-0 z-10 shadow-[4px_0_8px_-6px_rgb(28_25_23/0.35)]',
                        meta?.className
                      )}
                    >
                      {header.isPlaceholder ? null : canSort ? (
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
                      ) : (
                        <table.FlexRender header={header} />
                      )}
                    </TableHead>
                  );
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {loading && pageRows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center font-sans text-muted-foreground">
                  Зареждане…
                </TableCell>
              </TableRow>
            ) : pageRows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center font-sans text-muted-foreground">
                  {emptyMessage}
                </TableCell>
              </TableRow>
            ) : (
              pageRows.map((row) => (
                <TableRow key={row.id} className="group">
                  {row.getAllCells().map((cell) => {
                    const meta = cell.column.columnDef.meta;

                    return (
                      <TableCell
                        key={cell.id}
                        className={cn(
                          'px-4 py-3 font-sans',
                          meta?.sticky &&
                            'sticky left-0 z-10 bg-card shadow-[4px_0_8px_-6px_rgb(28_25_23/0.35)] group-hover:bg-muted/50',
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
          <button
            type="button"
            className="btn btn-ghost"
            disabled={pagination.page <= 1 || loading}
            onClick={() => pagination.onPageChange(pagination.page - 1)}
          >
            Назад
          </button>
          <p className="m-0 font-sans text-sm text-muted-foreground">
            Страница {pagination.page} от {pagination.lastPage} · {pagination.total} записа
          </p>
          <button
            type="button"
            className="btn btn-ghost"
            disabled={pagination.page >= pagination.lastPage || loading}
            onClick={() => pagination.onPageChange(pagination.page + 1)}
          >
            Напред
          </button>
        </div>
      ) : null}
    </div>
  );
}
