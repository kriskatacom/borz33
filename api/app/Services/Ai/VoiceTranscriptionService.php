<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Exceptions\AuthException;

final class VoiceTranscriptionService
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 4) . '/config/openai.php';
    }

    /** @param array<string, mixed>|null $audio */
    public function transcribe(?array $audio): string
    {
        if (($this->config['admin_assistant_enabled'] ?? true) !== true) {
            throw new AuthException('Гласовото въвеждане е изключено от конфигурацията.', 503);
        }
        if ($audio === null || (int) ($audio['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new AuthException('Добавете валиден аудио запис.', 422);
        }

        $size = (int) ($audio['size'] ?? 0);
        $path = (string) ($audio['tmp_name'] ?? '');
        if ($size < 1 || $size > self::MAX_BYTES || !is_file($path)) {
            throw new AuthException('Аудио записът трябва да е до 10 MB.', 422);
        }

        $name = (string) ($audio['name'] ?? 'dictation.webm');
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
        $uploadedMime = strtolower(trim(explode(';', (string) ($audio['type'] ?? ''))[0]));
        $allowedExtensions = ['webm', 'ogg', 'mp3', 'mp4', 'm4a', 'wav'];
        $allowedMimes = ['audio/webm', 'video/webm', 'audio/ogg', 'application/ogg', 'audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/wav', 'audio/x-wav', 'application/octet-stream'];
        if (!in_array($extension, $allowedExtensions, true) || (!in_array($detectedMime, $allowedMimes, true) && !in_array($uploadedMime, $allowedMimes, true))) {
            throw new AuthException('Неподдържан формат на аудио записа.', 422);
        }

        $apiKey = trim((string) ($this->config['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new AuthException('OpenAI не е конфигуриран. Добавете OPENAI_API_KEY в средата на backend услугата.', 503);
        }

        $curl = curl_init(rtrim((string) $this->config['api_url'], '/') . '/audio/transcriptions');
        if ($curl === false) {
            throw new AuthException('AI услугата не може да бъде стартирана.', 503);
        }

        $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name) ?: 'dictation.webm';
        $transferMime = [
            'webm' => 'audio/webm', 'ogg' => 'audio/ogg', 'mp3' => 'audio/mpeg',
            'mp4' => 'audio/mp4', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav',
        ][$extension];
        $payload = [
            'file' => new \CURLFile($path, $transferMime, $name),
            'model' => (string) ($this->config['transcribe_model'] ?? 'gpt-transcribe'),
            'language' => 'bg',
            'prompt' => 'Текстът е на български за административен панел на онлайн магазин. Запазвай правилно имена на продукти, марки, размери, цени и пунктуация.',
        ];

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => (int) ($this->config['timeout_seconds'] ?? 60),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($raw === false) {
            error_log('OpenAI transcription failed [transport=curl].');
            throw new AuthException('Връзката с услугата за гласово писане е неуспешна. Опитайте отново.', 503);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new AuthException('Услугата за гласово писане върна невалиден отговор.', 502);
        }

        if ($status < 200 || $status >= 300) {
            error_log('OpenAI transcription rejected [status=' . $status . '].');
            $message = is_string($decoded['error']['message'] ?? null) ? $decoded['error']['message'] : 'заявката беше отхвърлена';
            throw new AuthException('Гласовото писане е неуспешно: ' . $message, 502);
        }

        $text = trim((string) ($decoded['text'] ?? ''));
        if ($text === '') {
            throw new AuthException('Не беше разпознат текст в записа. Опитайте отново.', 422);
        }

        return $text;
    }
}
