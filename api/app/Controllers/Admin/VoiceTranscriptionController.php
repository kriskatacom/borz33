<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Ai\VoiceTranscriptionService;

final class VoiceTranscriptionController extends Controller
{
    public function __construct(private readonly VoiceTranscriptionService $transcription = new VoiceTranscriptionService())
    {
    }

    public function store(): never
    {
        $text = $this->transcription->transcribe(Request::file('audio'));

        $this->ok(['text' => $text], 'Текстът е разпознат. Прегледайте го преди запис.');
    }
}
