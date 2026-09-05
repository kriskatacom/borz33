<?php

declare(strict_types=1);

namespace App\Services\Pages;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Page;
use App\Models\PageField;
use App\Resources\PageResource;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PageAdminService
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $query = $this->filteredQuery($filters);
        $total = (clone $query)->count();
        $pages = $query
            ->with(['parent', 'pageTemplate'])
            ->withCount('fields')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'pages' => PageResource::collection($pages),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function find(int $id, bool $withTrashed = false): Page
    {
        $query = $withTrashed ? Page::withTrashed() : Page::query();
        $page = $query->find($id);

        if ($page === null) {
            throw new AuthException('Страницата не е намерена.', 404);
        }

        return $page;
    }

    /** @return list<array<string, mixed>> */
    public function tree(): array
    {
        $pages = Page::query()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'parent_id', 'sort_order']);

        $items = [];

        foreach ($pages as $page) {
            $items[] = [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'parent_id' => $page->parent_id,
                'sort_order' => $page->sort_order,
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Page
    {
        return Capsule::connection()->transaction(function () use ($data): Page {
            $page = new Page();
            $page->forceFill($this->pageAttributes($data, null, null))->save();
            $this->syncFields($page, $data);

            return $this->fresh($page);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Page $page, array $data): Page
    {
        return Capsule::connection()->transaction(function () use ($page, $data): Page {
            $attributes = $this->pageAttributes($data, (int) $page->id, $page);

            if ($attributes !== []) {
                $page->forceFill($attributes)->save();
            }

            $this->syncFields($page, $data);

            return $this->fresh($page);
        });
    }

    public function delete(Page $page): void
    {
        if ($page->trashed()) {
            throw new AuthException('Страницата вече е изтрита.');
        }

        $page->delete();
    }

    public function restore(int $id): Page
    {
        $page = $this->find($id, true);

        if ($page->deleted_at === null) {
            throw new AuthException('Страницата не е изтрита.');
        }

        $page->restore();

        return $this->fresh($page);
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $status = (string) ($filters['status'] ?? 'all');
        $query = match ($status) {
            'deleted' => Page::onlyTrashed(),
            'inactive' => Page::query()->where('is_active', false),
            'active' => Page::query()->where('is_active', true),
            default => Page::query(),
        };

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('meta_title', 'like', $like);
            });
        }

        $parent = trim((string) ($filters['parent'] ?? ''));

        if ($parent === 'root' || $parent === 'none' || $parent === '0') {
            $query->whereNull('parent_id');
        } elseif (ctype_digit($parent) && (int) $parent > 0) {
            $query->where('parent_id', (int) $parent);
        }

        return $query;
    }

    /** @param array<string, mixed> $data */
    private function pageAttributes(array $data, ?int $pageId, ?Page $existing): array
    {
        if ($existing === null) {
            $slug = trim((string) ($data['slug'] ?? ''));

            if ($slug === '') {
                $slug = $this->uniqueSlug((string) $data['title'], $pageId);
            }

            return [
                'title' => $data['title'],
                'slug' => $slug,
                'parent_id' => $this->resolvedParentId($data['parent_id'] ?? null, null),
                'page_template_id' => isset($data['page_template_id']) ? (int) $data['page_template_id'] : null,
                'is_active' => (bool) $data['is_active'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'content' => $this->sanitizeContent($data['content'] ?? null),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ];
        }

        $attributes = [];

        if (array_key_exists('title', $data)) {
            $attributes['title'] = $data['title'];
        }

        if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
            $slug = trim((string) ($data['slug'] ?? $existing->slug ?? ''));

            if ($slug === '') {
                $slug = $this->uniqueSlug((string) ($data['title'] ?? $existing->title), $pageId);
            }

            $attributes['slug'] = $slug;
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = (bool) $data['is_active'];
        }

        if (array_key_exists('parent_id', $data)) {
            $attributes['parent_id'] = $this->resolvedParentId($data['parent_id'], $pageId);
        }

        if (array_key_exists('page_template_id', $data)) {
            $attributes['page_template_id'] = $data['page_template_id'] === null
                ? null
                : (int) $data['page_template_id'];
        }

        if (array_key_exists('sort_order', $data)) {
            $attributes['sort_order'] = (int) $data['sort_order'];
        }

        if (array_key_exists('content', $data)) {
            $attributes['content'] = $this->sanitizeContent($data['content']);
        }

        foreach (['meta_title', 'meta_description'] as $nullable) {
            if (array_key_exists($nullable, $data)) {
                $attributes[$nullable] = $data[$nullable];
            }
        }

        return $attributes;
    }

    private function sanitizeContent(mixed $content): ?string
    {
        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $html = strip_tags($content, '<p><br><hr><h1><h2><h3><h4><h5><h6><strong><em><u><ul><ol><li><a>');
        $html = preg_replace_callback(
            '/<(p|br|hr|h1|h2|h3|h4|h5|h6|strong|em|u|ul|ol|li|a)\b([^>]*)>/i',
            static function (array $match): string {
                $tag = strtolower((string) $match[1]);

                if ($tag !== 'a') {
                    if (in_array($tag, ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)
                        && preg_match('/\btext-align\s*:\s*(left|center|right|justify)\b/i', (string) $match[2], $alignMatch) === 1
                    ) {
                        return '<' . $tag . ' style="text-align: ' . strtolower((string) $alignMatch[1]) . '">';
                    }

                    return '<' . $tag . '>';
                }

                if (preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/i', (string) $match[2], $hrefMatch) !== 1) {
                    return '<a>';
                }

                $href = trim(html_entity_decode((string) $hrefMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $allowed = preg_match('#^(?:https?://|mailto:|tel:|/|\#)#i', $href) === 1;

                if (!$allowed) {
                    return '<a>';
                }

                $newTab = preg_match('/\btarget\s*=\s*(["\'])_blank\1/i', (string) $match[2]) === 1;

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
                    . ($newTab ? ' target="_blank" rel="noopener noreferrer"' : '')
                    . '>';
            },
            $html
        ) ?? '';

        return trim($html) !== '' ? trim($html) : null;
    }

    /** @param array<string, mixed> $data */
    private function syncFields(Page $page, array $data): void
    {
        if (!array_key_exists('fields', $data)) {
            return;
        }

        $rows = is_array($data['fields']) ? $data['fields'] : [];
        $keep = [];

        foreach ($rows as $row) {
            if (isset($row['id']) && (is_int($row['id']) || (is_string($row['id']) && ctype_digit((string) $row['id'])))) {
                $keep[] = (int) $row['id'];
            }
        }

        $stale = PageField::query()->where('page_id', $page->id);

        if ($keep !== []) {
            $stale->whereNotIn('id', $keep);
        }

        $stale->delete();

        $usedSlugs = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $field = $this->ownedField($page, $row['id'] ?? null);
            $type = (string) ($row['field_type'] ?? PageField::TYPE_TEXT);
            $isFile = $type === PageField::TYPE_FILE;
            $isRequired = (bool) ($row['is_required'] ?? false);
            $value = $isFile ? null : (isset($row['value']) && is_string($row['value']) ? $row['value'] : null);
            $mediaId = $isFile ? $this->nullableId($row['media_file_id'] ?? null) : null;

            if ($isRequired && $isFile && $mediaId === null) {
                throw new ValidationException(['fields' => ['Задължително файлово поле трябва да има файл.']]);
            }

            if ($isRequired && !$isFile && ($value === null || trim($value) === '')) {
                throw new ValidationException(['fields' => ['Задължително текстово поле трябва да има стойност.']]);
            }

            $slug = $this->uniqueFieldSlug(
                $page,
                (string) $row['name'],
                trim((string) ($row['slug'] ?? '')),
                $field->id ? (int) $field->id : null,
                $usedSlugs
            );
            $usedSlugs[] = $slug;

            $field->forceFill([
                'page_id' => $page->id,
                'name' => $row['name'],
                'slug' => $slug,
                'field_type' => $type,
                'value' => $value,
                'media_file_id' => $mediaId,
                'is_required' => $isRequired,
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ])->save();
        }
    }

    private function ownedField(Page $page, mixed $id): PageField
    {
        if (is_int($id) || (is_string($id) && ctype_digit($id))) {
            $field = PageField::query()
                ->where('page_id', $page->id)
                ->where('id', (int) $id)
                ->first();

            if ($field !== null) {
                return $field;
            }
        }

        return new PageField();
    }

    /** @param list<string> $used */
    private function uniqueFieldSlug(Page $page, string $name, string $slug, ?int $ignoreId, array $used): string
    {
        $base = $slug !== '' ? strtolower($slug) : Str::slug($name, '-', 'bg');
        $base = $base !== '' ? $base : 'field';
        $candidate = $base;
        $suffix = 2;

        while (
            in_array($candidate, $used, true)
            || PageField::query()
                ->where('page_id', $page->id)
                ->where('slug', $candidate)
                ->when($ignoreId !== null, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title, '-', 'bg');
        $base = $base !== '' ? $base : 'page';
        $candidate = $base;
        $suffix = 2;

        while (
            Page::withTrashed()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function resolvedParentId(mixed $value, ?int $pageId): ?int
    {
        $parentId = $this->nullableId($value);

        if ($parentId === null) {
            return null;
        }

        if ($pageId !== null && $parentId === $pageId) {
            throw new ValidationException(['parent_id' => ['Страницата не може да бъде родител на себе си.']]);
        }

        $current = $parentId;
        $guard = 0;

        while ($current !== null && $guard < 64) {
            if ($pageId !== null && $current === $pageId) {
                throw new ValidationException(['parent_id' => ['Не може да изберете дете като родител.']]);
            }

            $current = Page::query()->where('id', $current)->value('parent_id');
            $current = is_numeric($current) ? (int) $current : null;
            $guard++;
        }

        return $parentId;
    }

    private function nullableId(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function fresh(Page $page): Page
    {
        $fresh = Page::query()->with(['parent', 'pageTemplate'])->find($page->id);

        return $fresh ?? $page;
    }
}
