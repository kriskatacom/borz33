<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Resources\MediaFileResource;
use App\Services\Media\MediaService;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaService $media = new MediaService()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->media->paginate(Request::query()), 'Списък с медия файлове.');
    }

    public function show(string $id): never
    {
        $file = $this->media->find($this->id($id));

        $this->ok(['file' => MediaFileResource::toArray($file)]);
    }

    public function store(): never
    {
        $files = Request::files('files');

        if ($files === []) {
            $single = Request::file('file');
            $files = $single === null ? [] : [$single];
        }

        if ($files === []) {
            throw new ValidationException(['file' => ['Изберете поне един файл.']]);
        }

        $stored = [];

        foreach ($files as $file) {
            $originalSize = Request::input('original_size');
            $stored[] = MediaFileResource::toArray($this->media->store($file, is_numeric($originalSize) ? (int) $originalSize : null));
        }

        $this->created(
            ['files' => $stored],
            count($stored) === 1 ? 'Файлът е качен.' : 'Файловете са качени.'
        );
    }

    public function update(string $id): never
    {
        $file = $this->media->update($this->media->find($this->id($id)), Request::input());

        $this->ok(['file' => MediaFileResource::toArray($file)], 'Файлът е обновен.');
    }

    public function destroy(string $id): never
    {
        $this->media->delete($this->media->find($this->id($id)));

        $this->ok([], 'Файлът е изтрит.');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Файлът не е намерен.', 404);
        }

        return (int) $id;
    }
}
