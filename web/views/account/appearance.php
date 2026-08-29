<?php

declare(strict_types=1);

use App\Models\User;
use Store\Core\Html;

/** @var User $user */
/** @var string $csrf */

$csrf = $csrf ?? '';
$theme = in_array((string) $user->theme, [User::THEME_LIGHT, User::THEME_DARK, User::THEME_SYSTEM], true)
    ? (string) $user->theme
    : User::THEME_SYSTEM;

$themes = [
    User::THEME_LIGHT => ['label' => 'Светла', 'hint' => 'Бял фон', 'icon' => 'sun', 'preview' => 'light'],
    User::THEME_DARK => ['label' => 'Тъмна', 'hint' => 'Черен фон', 'icon' => 'moon', 'preview' => 'dark'],
    User::THEME_SYSTEM => ['label' => 'Системна', 'hint' => 'Като устройството', 'icon' => 'monitor', 'preview' => 'system'],
];
?>
<p class="mt-0 mb-5 text-muted">Изборът се пази в профила и важи само за магазина, не за админ панела.</p>

<form method="post" action="/account/theme">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="store-theme-grid" role="radiogroup" aria-label="Тема на сайта">
        <?php foreach ($themes as $value => $option): ?>
            <button
                type="submit"
                name="theme"
                value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
                class="store-theme-option <?= $theme === $value ? 'is-active' : '' ?>"
                <?= $theme === $value ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>
                @click="$store.theme.set('<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>')"
            >
                <span class="store-theme-preview store-theme-preview--<?= htmlspecialchars($option['preview'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                    <b></b><i></i>
                </span>
                <span class="flex items-center gap-1.5 text-sm font-semibold">
                    <span class="store-shortcut-icon"><?= Html::iconSvg($option['icon']) ?></span>
                    <?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="text-xs text-muted"><?= htmlspecialchars($option['hint'], ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</form>
