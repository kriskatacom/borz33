import { createColumnHelper, type RowData } from '@tanstack/react-table';
import type { DataTableFeatures } from '@/components/data-table/features';

export function createDataTableHelper<TData extends RowData>() {
  return createColumnHelper<DataTableFeatures, TData>();
}
