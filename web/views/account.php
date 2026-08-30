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
$navGroups = [
    'Преглед' => [
        'dashboard' => ['label' => 'Табло', 'icon' => 'layout'],
        'orders' => ['label' => 'Моите поръчки', 'icon' => 'package'],
    ],
    'Профил' => [
        'profile' => ['label' => 'Публичен профил', 'icon' => 'user'],
        'details' => ['label' => 'Лични данни', 'icon' => 'pencil'],
        'addresses' => ['label' => 'Адреси', 'icon' => 'map-pin'],
    ],
    'Настройки' => [
        'password' => ['label' => 'Сигурност', 'icon' => 'lock'],
        'appearance' => ['label' => 'Изглед', 'icon' => 'sun'],
    ],
];
?>
<section class="store-profile">
    <div class="store-account-layout">
        <aside
            class="store-account-sidebar"
            x-data="{ mobileOpen: false }"
            x-effect="document.documentElement.classList.toggle('is-account-nav-open', mobileOpen)"
            @keydown.escape.window="mobileOpen = false"
        >
            <button
                type="button"
                class="store-account-sidebar-toggle"
                @click="mobileOpen = true"
                :aria-expanded="mobileOpen"
                aria-controls="store-account-sidebar-nav"
            >
                <span>
                    <small>Меню на профила</small>
                    <strong><?= htmlspecialchars(AccountController::SECTIONS[$section] ?? 'Акаунт', ENT_QUOTES, 'UTF-8') ?></strong>
                </span>
                <span aria-hidden="true" :class="{ 'is-open': mobileOpen }"><?= Html::iconSvg('chevron-down') ?></span>
            </button>

            <div class="store-account-sidebar-overlay" x-cloak :class="{ 'is-mobile-open': mobileOpen }" @click.self="mobileOpen = false">
                <div class="store-account-sidebar-panel" :role="mobileOpen ? 'dialog' : null" :aria-modal="mobileOpen ? 'true' : null" aria-label="Меню на профила">
                    <div class="store-account-sidebar-mobile-head">
                        <div>
                            <p>Вашият профил</p>
                            <strong><?= htmlspecialchars(AccountController::SECTIONS[$section] ?? 'Акаунт', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <button type="button" aria-label="Затвори менюто" @click="mobileOpen = false">×</button>
                    </div>

                    <a href="/account/profile" class="store-account-sidebar-user">
                        <span class="store-account-sidebar-avatar">
                            <?php if ($avatarUrl !== null): ?>
                                <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="52" height="52">
                            <?php else: ?>
                                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <span class="store-account-sidebar-identity">
                            <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars((string) $user->email, ENT_QUOTES, 'UTF-8') ?></small>
                        </span>
                        <span class="store-account-sidebar-arrow" aria-hidden="true"><?= Html::iconSvg('chevron-right') ?></span>
                    </a>

                    <nav id="store-account-sidebar-nav" class="store-account-nav" aria-label="Акаунт">
                        <?php foreach ($navGroups as $groupLabel => $items): ?>
                            <div class="store-account-nav-group">
                                <p><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php foreach ($items as $key => $item): ?>
                                    <?php $href = $key === 'dashboard' ? '/account' : '/account/' . $key; ?>
                                    <a class="<?= $section === $key ? 'is-active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>>
                                        <span class="store-account-nav-icon"><?= Html::iconSvg($item['icon']) ?></span>
                                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="store-account-nav-arrow" aria-hidden="true"><?= Html::iconSvg('chevron-right') ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        <form method="post" action="/logout">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit">
                                <span class="store-account-nav-icon"><?= Html::iconSvg('log-out') ?></span>
                                <span>Изход</span>
                            </button>
                        </form>
                    </nav>
                </div>
            </div>
        </aside>

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
