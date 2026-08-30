<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Models\Page;
use Store\Core\View;

class ContentPageController extends Controller
{
    public function show(string $slug): never
    {
        $slug = trim((string) preg_replace('#/+#', '/', strtolower($slug)), '/');
        $page = Page::query()
            ->with('pageTemplate')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($page === null) {
            View::renderError('Страницата не е намерена.', 404);
        }

        $view = (string) ($page->pageTemplate?->view ?? 'page-templates/default');
        $view = in_array($view, ['page-templates/default'], true) ? $view : 'page-templates/default';
        $breadcrumbs = $this->breadcrumbs($page);

        $this->view($view, [
            'title' => trim((string) $page->meta_title) !== '' ? (string) $page->meta_title : (string) $page->title,
            'metaDescription' => (string) ($page->meta_description ?? ''),
            'canonicalPath' => '/' . $page->slug,
            'compactMainBottom' => true,
            'flushMainTop' => true,
            'page' => $page,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /** @return list<array{label: string, href: string|null}> */
    private function breadcrumbs(Page $page): array
    {
        $ancestors = [];
        $seen = [(int) $page->id => true];
        $parentId = $page->parent_id !== null ? (int) $page->parent_id : null;

        while ($parentId !== null && !isset($seen[$parentId]) && count($ancestors) < 32) {
            $seen[$parentId] = true;
            $parent = Page::query()->find($parentId);

            if ($parent === null) {
                break;
            }

            $ancestors[] = [
                'label' => (string) $parent->title,
                'href' => $parent->is_active ? '/' . $parent->slug : null,
            ];
            $parentId = $parent->parent_id !== null ? (int) $parent->parent_id : null;
        }

        return [
            ['label' => 'Начало', 'href' => '/'],
            ...array_reverse($ancestors),
            ['label' => (string) $page->title, 'href' => null],
        ];
    }
}
