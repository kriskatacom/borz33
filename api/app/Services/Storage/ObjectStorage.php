<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Aws\S3\S3Client;

final class ObjectStorage
{
    private ?S3Client $client = null;

    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 4) . '/config/storage.php';
    }

    public function enabled(): bool
    {
        $r2 = $this->config['r2'] ?? [];

        return ($this->config['disk'] ?? 'local') === 'r2'
            && is_array($r2)
            && $r2['endpoint'] !== ''
            && $r2['bucket'] !== ''
            && $r2['key'] !== ''
            && $r2['secret'] !== ''
            && $r2['public_url'] !== '';
    }

    public function publicUrl(string $key): string
    {
        $key = ltrim($key, '/');

        if (!$this->enabled()) {
            return '/' . $key;
        }

        $base = (string) (($this->config['r2']['public_url'] ?? '') ?: '');

        if ($base === '') {
            return '/' . $key;
        }

        return $base . '/' . implode('/', array_map('rawurlencode', explode('/', $key)));
    }

    public function put(string $key, string $source, string $contentType): void
    {
        if (!$this->enabled()) {
            return;
        }

        $this->client()->putObject([
            'Bucket' => $this->bucket(),
            'Key' => ltrim($key, '/'),
            'Body' => fopen($source, 'rb'),
            'ContentType' => $contentType,
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function delete(string $key): void
    {
        if (!$this->enabled() || trim($key) === '') {
            return;
        }

        $this->client()->deleteObject([
            'Bucket' => $this->bucket(),
            'Key' => ltrim($key, '/'),
        ]);
    }

    private function bucket(): string
    {
        return (string) $this->config['r2']['bucket'];
    }

    private function client(): S3Client
    {
        if ($this->client instanceof S3Client) {
            return $this->client;
        }

        $r2 = $this->config['r2'];
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => (string) $r2['region'],
            'endpoint' => (string) $r2['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => (string) $r2['key'],
                'secret' => (string) $r2['secret'],
            ],
        ]);

        return $this->client;
    }
}
