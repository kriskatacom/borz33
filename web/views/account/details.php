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
$phoneCountries = [
    ['България', '+359'], ['Гърция', '+30'], ['Румъния', '+40'], ['Сърбия', '+381'],
    ['Северна Македония', '+389'], ['Турция', '+90'], ['Германия', '+49'], ['Австрия', '+43'],
    ['Швейцария', '+41'], ['Италия', '+39'], ['Испания', '+34'], ['Португалия', '+351'],
    ['Франция', '+33'], ['Белгия', '+32'], ['Нидерландия', '+31'], ['Обединеното кралство', '+44'],
    ['Ирландия', '+353'], ['Дания', '+45'], ['Швеция', '+46'], ['Норвегия', '+47'],
    ['Финландия', '+358'], ['Полша', '+48'], ['Чехия', '+420'], ['Унгария', '+36'],
    ['Хърватия', '+385'], ['Словения', '+386'], ['Украйна', '+380'], ['Грузия', '+995'],
    ['Съединени щати', '+1'], ['Канада', '+1'], ['Австралия', '+61'],
];
$phone = trim((string) ($profile['phone'] ?? ''));
$phoneCountry = '+359';
$phoneNumber = $phone;
foreach ($phoneCountries as [$countryName, $countryCode]) {
    if (str_starts_with($phone, $countryCode)) {
        $phoneCountry = $countryCode;
        $phoneNumber = trim(substr($phone, strlen($countryCode)));
        break;
    }
}
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
        x-init="initPhone()"
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
            <div class="store-phone-input">
                <select
                    class="<?= $inputClass ?> store-phone-country"
                    id="account-phone-country"
                    x-ref="phoneCountry"
                    aria-label="Код на държавата за телефон"
                    @change="syncPhone"
                >
                    <?php foreach ($phoneCountries as [$countryName, $countryCode]): ?>
                        <option value="<?= htmlspecialchars($countryCode, ENT_QUOTES, 'UTF-8') ?>"<?= $phoneCountry === $countryCode ? ' selected' : '' ?>><?= htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($countryCode, ENT_QUOTES, 'UTF-8') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input
                    class="<?= $inputClass ?>"
                    id="account-phone-number"
                    x-ref="phoneNumber"
                    type="tel"
                    autocomplete="tel-national"
                    inputmode="tel"
                    placeholder="88 123 4567"
                    value="<?= htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8') ?>"
                    @input="syncPhone"
                >
            </div>
            <input
                class="sr-only"
                id="account-phone"
                name="phone"
                type="tel"
                maxlength="32"
                value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                :class="{ 'is-invalid': invalid('phone') }"
                :aria-invalid="invalid('phone')"
                aria-describedby="account-phone-error"
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
