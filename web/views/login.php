<?php

declare(strict_types=1);

$config = [
    'csrf' => (string) ($csrf ?? ''),
    'step' => (string) ($step ?? 'credentials'),
    'returnTo' => (string) ($returnTo ?? ''),
    'login' => [
        'email' => (string) ($email ?? ''),
        'errors' => (array) ($errors ?? []),
        'message' => $message ?? null,
        'isError' => (bool) ($isError ?? false),
    ],
    'register' => [
        'fields' => (array) ($register ?? []),
        'errors' => (array) ($registerErrors ?? []),
        'message' => $registerMessage ?? null,
        'isError' => (bool) ($registerIsError ?? false),
    ],
];
?>
<div
    id="store-auth-app"
    data-config="<?= htmlspecialchars((string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>"
></div>
<noscript>
    <p class="border border-line bg-canvas p-4">За вход и регистрация е необходимо JavaScript да бъде включен.</p>
</noscript>
