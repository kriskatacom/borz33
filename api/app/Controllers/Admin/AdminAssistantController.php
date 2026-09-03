<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Ai\AdminAssistantService;

final class AdminAssistantController extends Controller
{
    public function __construct(private readonly AdminAssistantService $assistant = new AdminAssistantService()) {}

    public function ask(): never
    {
        $message = (string) Request::input('message', '');
        $path = (string) Request::input('current_path', '/');
        $this->ok($this->assistant->answer($message, $path), 'Справката е готова.');
    }
}
