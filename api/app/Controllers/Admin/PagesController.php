<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\PageResource;
use App\Services\Pages\PageAdminService;
use App\Validation\PageValidator;

class PagesController extends Controller
{
    public function __construct(
        private readonly PageAdminService $pages = new PageAdminService(),
        private readonly PageValidator $validator = new PageValidator()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->pages->paginate(Request::query()), 'Списък със страници.');
    }

    public function show(string $id): never
    {
        $page = $this->pages->find($this->id($id), true);

        $this->ok(['page' => PageResource::toAdminArray($page)]);
    }

    public function store(): never
    {
        $payload = $this->validator->validate(Request::input());
        $page = $this->pages->create($payload);

        $this->created(['page' => PageResource::toAdminArray($page)], 'Страницата е създадена.');
    }

    public function update(string $id): never
    {
        $page = $this->pages->find($this->id($id));
        $payload = $this->validator->validate(Request::input(), $page->id);
        $page = $this->pages->update($page, $payload);

        $this->ok(['page' => PageResource::toAdminArray($page)], 'Страницата е обновена.');
    }

    public function destroy(string $id): never
    {
        $this->pages->delete($this->pages->find($this->id($id)));

        $this->ok([], 'Страницата е изтрита.');
    }

    public function restore(string $id): never
    {
        $page = $this->pages->restore($this->id($id));

        $this->ok(['page' => PageResource::toAdminArray($page)], 'Страницата е възстановена.');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Страницата не е намерена.', 404);
        }

        return (int) $id;
    }
}
