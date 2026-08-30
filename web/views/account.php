<?php

declare(strict_types=1);

use App\Models\User;
use Store\Controllers\AccountController;
use Store\Core\Html;

/** @var User $user */
/** @var string $section */
/** @var string $csrf */
/** @var array<string, string> $profile */
/** @var array<string, mixed> $profileErrors */
/** @var array<string, mixed> $passwordErrors */
/** @var string|null $message */
/** @var bool $isError */

$csrf = $csrf ?? '';
$section = $section ?? 'dashboard';
$profile = $profile ?? [];
$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];
$message = $message ?? null;
$isError = $isError ?? false;

$initials = mb_strtoupper(mb_substr((string) $user->first_name, 0, 1) . mb_substr((string) $user->last_name, 0, 1));

if ($initials === '') {
    $initials = mb_strtoupper(mb_substr((string) $user->email, 0, 1));
}

$displayName = $user->fullName() !== '' ? $user->fullName() : 'Клиент';
$memberSince = $user->created_at?->timezone('Europe/Sofia')->format('d.m.Y');
/** @var string|null $avatarUrl */
$avatarUrl = $avatarUrl ?? null;
/** @var list<array{id: string, url: string, label: string}> $avatarPresets */
$avatarPresets = $avatarPresets ?? [];
$avatarConfig = [
    'csrf' => $csrf,
    'url' => $avatarUrl,
    'initials' => $initials,
    'presets' => $avatarPresets,
];

$fieldError = static function (array $errors, string $key): ?string {
    $value = $errors[$key] ?? null;

    if (is_array($value)) {
        $first = $value[0] ?? null;

        return is_string($first) ? $first : null;
    }

    return is_string($value) ? $value : null;
};

$errorMap = static function (array $errors) use ($fieldError): array {
    $map = [];

    foreach ($errors as $key => $value) {
        if (!is_string($key)) {
            continue;
        }

        $message = $fieldError($errors, $key);

        if (is_string($message) && $message !== '') {
            $map[$key] = $message;
        }
    }

    return $map;
};

$alpineJson = static function (mixed $data): string {
    return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
};

$inputClass = 'store-input h-[42px] w-full border border-line bg-canvas px-3 text-ink';
$navItems = [
    'dashboard' => ['label' => 'Табло', 'icon' => 'layout'],
    'profile' => ['label' => 'Профил', 'icon' => 'user'],
    'details' => ['label' => 'Данни на акаунта', 'icon' => 'pencil'],
    'password' => ['label' => 'Парола', 'icon' => 'lock'],
    'orders' => ['label' => 'Поръчки', 'icon' => 'package'],
    'addresses' => ['label' => 'Адреси', 'icon' => 'map-pin'],
    'appearance' => ['label' => 'Изглед', 'icon' => 'sun'],
];
?>
<section class="store-profile">
    <div class="store-account-layout">
        <nav class="store-account-nav" aria-label="Акаунт">
            <?php foreach ($navItems as $key => $item): ?>
                <?php $href = $key === 'dashboard' ? '/account' : '/account/' . $key; ?>
                <a class="<?= $section === $key ? 'is-active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="store-account-nav-icon"><?= Html::iconSvg($item['icon']) ?></span>
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
            <form method="post" action="/logout">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">
                    <span class="store-account-nav-icon"><?= Html::iconSvg('log-out') ?></span>
                    Изход
                </button>
            </form>
        </nav>

        <div class="store-account-panel">
            <p class="store-account-kicker"><?= htmlspecialchars(AccountController::SECTIONS[$section] ?? 'Акаунт', ENT_QUOTES, 'UTF-8') ?></p>

            <?php if ($message): ?>
                <p class="mb-5 border border-line bg-canvas px-3 py-2.5 <?= $isError ? 'border-ink font-medium' : '' ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php
            require __DIR__ . '/account/' . $section . '.php';
            ?>
        </div>
    </div>
</section>
