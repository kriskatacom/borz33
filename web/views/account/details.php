<?php

declare(strict_types=1);

/** @var array<string, string> $profile */
/** @var array<string, mixed> $profileErrors */
/** @var string $csrf */
/** @var string $inputClass */
/** @var callable $alpineJson */
/** @var callable $errorMap */

$profile = $profile ?? [];
$csrf = $csrf ?? '';
$formConfig = $alpineJson([
    'kind' => 'profile',
    'errors' => $errorMap($profileErrors ?? []),
    'idleLabel' => 'Запази данните',
]);
?>
<p class="mt-0 mb-5 text-muted">Име и телефон за поръчки и известия. Имейлът не се променя.</p>

<article class="store-card">
    <form
        class="store-form grid gap-4"
        method="post"
        action="/account/profile"
        novalidate
        x-data='storeAccountForm(<?= $formConfig ?>)'
        :class="busy && 'is-saving'"
        :aria-busy="busy"
        @submit="onSubmit"
    >
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <label class="text-sm font-semibold" for="account-first-name">Име</label>
                <input
                    class="<?= $inputClass ?>"
                    id="account-first-name"
                    name="first_name"
                    type="text"
                    autocomplete="given-name"
                    required
                    maxlength="100"
                    value="<?= htmlspecialchars($profile['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    :class="{ 'is-invalid': invalid('first_name') }"
                    :aria-invalid="invalid('first_name')"
                    aria-describedby="account-first-name-error"
                    @input="onInput('first_name')"
                    @blur="onBlur('first_name')"
                >
                <p class="store-field-error" id="account-first-name-error" x-cloak x-show="invalid('first_name')" x-text="error('first_name')"></p>
            </div>
            <div class="grid gap-1.5">
                <label class="text-sm font-semibold" for="account-last-name">Фамилия</label>
                <input
                    class="<?= $inputClass ?>"
                    id="account-last-name"
                    name="last_name"
                    type="text"
                    autocomplete="family-name"
                    required
                    maxlength="100"
                    value="<?= htmlspecialchars($profile['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    :class="{ 'is-invalid': invalid('last_name') }"
                    :aria-invalid="invalid('last_name')"
                    aria-describedby="account-last-name-error"
                    @input="onInput('last_name')"
                    @blur="onBlur('last_name')"
                >
                <p class="store-field-error" id="account-last-name-error" x-cloak x-show="invalid('last_name')" x-text="error('last_name')"></p>
            </div>
        </div>

        <div class="grid gap-1.5">
            <label class="text-sm font-semibold" for="account-email">Имейл</label>
            <input class="<?= $inputClass ?> cursor-default text-muted" id="account-email" type="email" value="<?= htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly tabindex="-1">
            <p class="m-0 text-sm text-muted">Имейлът е за вход и съобщения и не може да се сменя от тук.</p>
        </div>

        <div class="grid gap-1.5">
            <label class="text-sm font-semibold" for="account-phone">Телефон</label>
            <input
                class="<?= $inputClass ?>"
                id="account-phone"
                name="phone"
                type="tel"
                autocomplete="tel"
                maxlength="32"
                value="<?= htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                :class="{ 'is-invalid': invalid('phone') }"
                :aria-invalid="invalid('phone')"
                aria-describedby="account-phone-error"
                @input="onInput('phone')"
                @blur="onBlur('phone')"
            >
            <p class="store-field-error" id="account-phone-error" x-cloak x-show="invalid('phone')" x-text="error('phone')"></p>
        </div>

        <button type="submit" class="store-submit">
            <span class="store-submit-spinner" x-cloak x-show="busy"></span>
            <span x-text="busy ? 'Записване…' : idleLabel">Запази данните</span>
        </button>
    </form>
</article>
