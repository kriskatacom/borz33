<?php

declare(strict_types=1);

use Store\Core\Html;

/** @var array<string, mixed> $passwordErrors */
/** @var string $csrf */
/** @var string $inputClass */
/** @var callable $fieldError */

$csrf = $csrf ?? '';
?>
<p class="mt-0 mb-5 text-muted">Новата парола трябва да е поне 8 символа. След смяна оставате влезли само в този браузър.</p>

<form class="grid max-w-md gap-4" method="post" action="/account/password" novalidate x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="grid gap-1.5">
        <label class="text-sm font-semibold" for="password-current">Текуща парола</label>
        <div class="flex items-stretch gap-1">
            <input class="<?= $inputClass ?>" id="password-current" name="current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password" required>
            <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showCurrent = !showCurrent" :aria-label="showCurrent ? 'Скрий паролата' : 'Покажи паролата'">
                <span x-show="!showCurrent"><?= Html::iconSvg('eye') ?></span>
                <span x-cloak x-show="showCurrent"><?= Html::iconSvg('eye-off') ?></span>
            </button>
        </div>
        <?php if ($fieldError($passwordErrors, 'current_password')): ?>
            <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($passwordErrors, 'current_password'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="grid gap-1.5">
        <label class="text-sm font-semibold" for="password-new">Нова парола</label>
        <div class="flex items-stretch gap-1">
            <input class="<?= $inputClass ?>" id="password-new" name="password" :type="showNew ? 'text' : 'password'" autocomplete="new-password" minlength="8" required>
            <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showNew = !showNew" :aria-label="showNew ? 'Скрий паролата' : 'Покажи паролата'">
                <span x-show="!showNew"><?= Html::iconSvg('eye') ?></span>
                <span x-cloak x-show="showNew"><?= Html::iconSvg('eye-off') ?></span>
            </button>
        </div>
        <?php if ($fieldError($passwordErrors, 'password')): ?>
            <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($passwordErrors, 'password'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="grid gap-1.5">
        <label class="text-sm font-semibold" for="password-confirm">Потвърдете новата парола</label>
        <div class="flex items-stretch gap-1">
            <input class="<?= $inputClass ?>" id="password-confirm" name="password_confirmation" :type="showConfirm ? 'text' : 'password'" autocomplete="new-password" minlength="8" required>
            <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showConfirm = !showConfirm" :aria-label="showConfirm ? 'Скрий паролата' : 'Покажи паролата'">
                <span x-show="!showConfirm"><?= Html::iconSvg('eye') ?></span>
                <span x-cloak x-show="showConfirm"><?= Html::iconSvg('eye-off') ?></span>
            </button>
        </div>
        <?php if ($fieldError($passwordErrors, 'password_confirmation')): ?>
            <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($passwordErrors, 'password_confirmation'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <button type="submit" class="inline-flex h-[42px] w-fit items-center justify-center border border-accent bg-accent px-4 font-semibold text-on-accent">Смени паролата</button>
</form>
