import {
  flattenPageTree,
  pageDescendantIds,
  pageTreePrefix,
  type PageTreeNode,
  type PageTreeOption,
} from '@/features/pages/pageTree';
import type { CategoryTreeNode } from '@/api/categories';

export type CategoryTreeOption = PageTreeOption;

export function categoryTreePrefix(depth: number): string {
  return pageTreePrefix(depth);
}

function asPageNodes(categories: CategoryTreeNode[]): PageTreeNode[] {
  return categories.map((category) => ({
    id: category.id,
    title: category.name,
    slug: category.slug,
    parent_id: category.parent_id,
    sort_order: category.sort_order,
  }));
}

export function flattenCategoryTree(
  categories: CategoryTreeNode[],
  excludeIds: Iterable<number> = []
): CategoryTreeOption[] {
  return flattenPageTree(asPageNodes(categories), excludeIds);
}

export function categoryDescendantIds(categories: CategoryTreeNode[], rootId: number): Set<number> {
  return pageDescendantIds(asPageNodes(categories), rootId);
}
