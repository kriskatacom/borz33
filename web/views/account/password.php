<?php

declare(strict_types=1);

use Store\Core\Html;

/** @var array<string, mixed> $passwordErrors */
/** @var string $csrf */
/** @var string $inputClass */
/** @var callable $alpineJson */
/** @var callable $errorMap */

$csrf = $csrf ?? '';
$formConfig = $alpineJson([
    'kind' => 'password',
    'errors' => $errorMap($passwordErrors ?? []),
    'idleLabel' => 'Смени паролата',
]);
?>
<p class="mt-0 mb-5 text-muted">Новата парола трябва да е поне 8 символа. След смяна оставате влезли само в този браузър.</p>

<article class="store-card">
    <form
        class="store-form grid max-w-md gap-4"
        method="post"
        action="/account/password"
        novalidate
        x-data='storeAccountForm(<?= $formConfig ?>)'
        :class="busy && 'is-saving'"
        :aria-busy="busy"
        @submit="onSubmit"
    >
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="grid gap-1.5">
            <label class="text-sm font-semibold" for="password-current">Текуща парола</label>
            <div class="flex items-stretch gap-1">
                <input
                    class="<?= $inputClass ?>"
                    id="password-current"
                    name="current_password"
                    :type="showCurrent ? 'text' : 'password'"
                    autocomplete="current-password"
                    required
                    :class="{ 'is-invalid': invalid('current_password') }"
                    :aria-invalid="invalid('current_password')"
                    aria-describedby="password-current-error"
                    @input="onInput('current_password')"
                    @blur="onBlur('current_password')"
                >
                <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showCurrent = !showCurrent" :aria-label="showCurrent ? 'Скрий паролата' : 'Покажи паролата'">
                    <span x-show="!showCurrent"><?= Html::iconSvg('eye') ?></span>
                    <span x-cloak x-show="showCurrent"><?= Html::iconSvg('eye-off') ?></span>
                </button>
            </div>
            <p class="store-field-error" id="password-current-error" x-cloak x-show="invalid('current_password')" x-text="error('current_password')"></p>
        </div>

        <div class="grid gap-1.5">
            <label class="text-sm font-semibold" for="password-new">Нова парола</label>
            <div class="flex items-stretch gap-1">
                <input
                    class="<?= $inputClass ?>"
                    id="password-new"
                    name="password"
                    :type="showNew ? 'text' : 'password'"
                    autocomplete="new-password"
                    minlength="8"
                    required
                    :class="{ 'is-invalid': invalid('password') }"
                    :aria-invalid="invalid('password')"
                    aria-describedby="password-new-error"
                    @input="onInput('password')"
                    @blur="onBlur('password')"
                >
                <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showNew = !showNew" :aria-label="showNew ? 'Скрий паролата' : 'Покажи паролата'">
                    <span x-show="!showNew"><?= Html::iconSvg('eye') ?></span>
                    <span x-cloak x-show="showNew"><?= Html::iconSvg('eye-off') ?></span>
                </button>
            </div>
            <p class="store-field-error" id="password-new-error" x-cloak x-show="invalid('password')" x-text="error('password')"></p>
        </div>

        <div class="grid gap-1.5">
            <label class="text-sm font-semibold" for="password-confirm">Потвърдете новата парола</label>
            <div class="flex items-stretch gap-1">
                <input
                    class="<?= $inputClass ?>"
                    id="password-confirm"
                    name="password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    autocomplete="new-password"
                    minlength="8"
                    required
                    :class="{ 'is-invalid': invalid('password_confirmation') }"
                    :aria-invalid="invalid('password_confirmation')"
                    aria-describedby="password-confirm-error"
                    @input="onInput('password_confirmation')"
                    @blur="onBlur('password_confirmation')"
                >
                <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showConfirm = !showConfirm" :aria-label="showConfirm ? 'Скрий паролата' : 'Покажи паролата'">
                    <span x-show="!showConfirm"><?= Html::iconSvg('eye') ?></span>
                    <span x-cloak x-show="showConfirm"><?= Html::iconSvg('eye-off') ?></span>
                </button>
            </div>
            <p class="store-field-error" id="password-confirm-error" x-cloak x-show="invalid('password_confirmation')" x-text="error('password_confirmation')"></p>
        </div>

        <button type="submit" class="store-submit">
            <span class="store-submit-spinner" x-cloak x-show="busy"></span>
            <span x-text="busy ? 'Записване…' : idleLabel">Смени паролата</span>
        </button>
    </form>
</article>
