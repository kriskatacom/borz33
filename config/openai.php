<?php

declare(strict_types=1);

return [
    'api_key' => (string) (getenv('OPENAI_API_KEY') ?: ''),
    'api_url' => rtrim((string) (getenv('OPENAI_API_URL') ?: 'https://api.openai.com/v1'), '/'),
    'product_model' => (string) (getenv('OPENAI_PRODUCT_MODEL') ?: 'gpt-4.1-mini'),
    'transcribe_model' => (string) (getenv('OPENAI_TRANSCRIBE_MODEL') ?: 'gpt-transcribe'),
    'timeout_seconds' => max(10, min(120, (int) (getenv('OPENAI_TIMEOUT_SECONDS') ?: 60))),
];
