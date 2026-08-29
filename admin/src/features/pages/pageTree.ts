export type PageTreeNode = {
  id: number;
  title: string;
  slug?: string;
  parent_id: number | null;
  sort_order?: number;
};

export type PageTreeOption = {
  id: number;
  title: string;
  depth: number;
  label: string;
};

export const PAGE_TREE_DASH = '—';

export function pageTreePrefix(depth: number): string {
  if (depth <= 0) {
    return '';
  }

  return `${PAGE_TREE_DASH.repeat(depth)} `;
}

export function pageTreeLabel(title: string, depth: number): string {
  return `${pageTreePrefix(depth)}${title}`;
}

function sortPages(left: PageTreeNode, right: PageTreeNode): number {
  const order = (left.sort_order ?? 0) - (right.sort_order ?? 0);

  if (order !== 0) {
    return order;
  }

  return left.title.localeCompare(right.title, 'bg');
}

function groupedChildren(pages: PageTreeNode[]): Map<number | 'root', PageTreeNode[]> {
  const ids = new Set(pages.map((page) => page.id));
  const groups = new Map<number | 'root', PageTreeNode[]>();

  for (const page of pages) {
    const key = page.parent_id !== null && ids.has(page.parent_id) ? page.parent_id : 'root';
    const siblings = groups.get(key) ?? [];
    siblings.push(page);
    groups.set(key, siblings);
  }

  for (const siblings of groups.values()) {
    siblings.sort(sortPages);
  }

  return groups;
}

export function flattenPageTree(pages: PageTreeNode[], excludeIds: Iterable<number> = []): PageTreeOption[] {
  const excluded = new Set(excludeIds);
  const groups = groupedChildren(pages);
  const items: PageTreeOption[] = [];

  function visit(key: number | 'root', depth: number) {
    for (const page of groups.get(key) ?? []) {
      if (excluded.has(page.id)) {
        continue;
      }

      items.push({
        id: page.id,
        title: page.title,
        depth,
        label: pageTreeLabel(page.title, depth),
      });
      visit(page.id, depth + 1);
    }
  }

  visit('root', 0);

  return items;
}

export function pageDescendantIds(pages: PageTreeNode[], rootId: number): Set<number> {
  const groups = groupedChildren(pages);
  const ids = new Set<number>([rootId]);
  const stack = [rootId];

  while (stack.length > 0) {
    const current = stack.pop();

    if (current === undefined) {
      break;
    }

    for (const child of groups.get(current) ?? []) {
      if (!ids.has(child.id)) {
        ids.add(child.id);
        stack.push(child.id);
      }
    }
  }

  return ids;
}
