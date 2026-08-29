<?php

declare(strict_types=1);

use App\Models\UserAddress;
use Store\Core\Html;
use Store\Support\EuropeanCountries;

/** @var \Illuminate\Support\Collection<int, UserAddress> $billingAddresses */
/** @var array<string, mixed> $addressForm */
/** @var array<string, mixed> $addressErrors */
/** @var int|null $editingAddressId */
/** @var string $csrf */
/** @var string $inputClass */
/** @var callable $alpineJson */
/** @var callable $errorMap */

$billingAddresses = $billingAddresses ?? new \Illuminate\Support\Collection();
$addressForm = $addressForm ?? [];
$addressErrors = $addressErrors ?? [];
$editingAddressId = isset($editingAddressId) ? (is_int($editingAddressId) ? $editingAddressId : null) : null;
$csrf = $csrf ?? '';
$party = (string) ($addressForm['party'] ?? UserAddress::PARTY_PERSON);
$formAction = $editingAddressId !== null ? '/account/addresses/' . $editingAddressId : '/account/addresses';
$isDefault = (bool) ($addressForm['is_default'] ?? false);
$saveLabel = $editingAddressId !== null ? 'Запази адреса' : 'Запиши адреса';
$foldOpen = $editingAddressId !== null || $addressErrors !== [];
$europeanCountries = EuropeanCountries::names();
$countryValue = trim((string) ($addressForm['country'] ?? 'България'));

if ($countryValue === '') {
    $countryValue = 'България';
}

if (!in_array($countryValue, $europeanCountries, true)) {
    $europeanCountries[] = $countryValue;
    sort($europeanCountries, SORT_STRING);
}
$formConfig = $alpineJson([
    'kind' => 'address',
    'party' => $party,
    'errors' => $errorMap($addressErrors),
    'idleLabel' => $saveLabel,
]);
?>
<p class="mt-0 mb-5 text-muted">Запишете няколко адреса за фактура — за себе си или за фирми. При поръчка ще може да изберете кой да се използва.</p>

<article class="store-card store-address-fold" x-data="{ open: <?= $foldOpen ? 'true' : 'false' ?> }" :class="open && 'is-open'">
    <button
        type="button"
        class="store-address-fold-toggle"
        @click="open = !open"
        :aria-expanded="open"
        aria-controls="address-fold-panel"
    >
        <span class="store-address-fold-copy">
            <span class="store-address-fold-title"><?= $editingAddressId !== null ? 'Редакция на адрес' : 'Нов адрес за фактуриране' ?></span>
            <span class="store-address-fold-hint">Добавете адрес за себе си или за фирма</span>
        </span>
        <span class="store-address-fold-icon" aria-hidden="true"><?= Html::iconSvg('chevron-down') ?></span>
    </button>
    <div id="address-fold-panel" x-show="open" <?php if (!$foldOpen): ?>x-cloak<?php endif; ?>>
    <form
        class="store-form"
        method="post"
        action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
        novalidate
        x-data='storeAccountForm(<?= $formConfig ?>)'
        :class="busy && 'is-saving'"
        :aria-busy="busy"
        @submit="onSubmit"
    >
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="store-form-section">
            <p class="store-form-section-title" id="address-party-label">Тип</p>
            <div class="store-form-section-body">
            <input type="hidden" name="party" value="<?= htmlspecialchars($party, ENT_QUOTES, 'UTF-8') ?>" :value="party">
            <div class="store-switch" role="group" aria-labelledby="address-party-label">
                <button type="button" class="store-switch-option" :class="party === 'person' && 'is-active'" @click="setParty('person')">Физическо лице</button>
                <button
                    type="button"
                    class="store-switch-track"
                    role="switch"
                    :aria-checked="party === 'company'"
                    aria-label="Фирма"
                    @click="toggleParty()"
                >
                    <span class="store-switch-knob"></span>
                </button>
                <button type="button" class="store-switch-option" :class="party === 'company' && 'is-active'" @click="setParty('company')">Фирма</button>
            </div>
            </div>
        </div>

        <div class="store-form-section">
            <p class="store-form-section-title">Име на записа</p>
            <div class="store-form-section-body">
            <div class="grid gap-1.5">
                <label class="text-sm font-semibold" for="address-label">Име на адреса <span class="font-normal text-muted">(по желание)</span></label>
                <input class="<?= $inputClass ?>" id="address-label" name="label" type="text" maxlength="80" placeholder="Напр. Домашен, Офис, Фирма 2" value="<?= htmlspecialchars((string) ($addressForm['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('label') }" :aria-invalid="invalid('label')" @input="onInput('label')" @blur="onBlur('label')">
                <p class="store-field-error" x-cloak x-show="invalid('label')" x-text="error('label')"></p>
            </div>
            </div>
        </div>

        <div class="store-form-section">
            <p class="store-form-section-title">Получател</p>
            <div class="store-form-section-body">
            <div class="grid gap-4 sm:grid-cols-2" x-show="party === 'person'"<?php if ($party === UserAddress::PARTY_COMPANY): ?> style="display:none"<?php endif; ?>>
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="address-first-name">Име</label>
                    <input class="<?= $inputClass ?>" id="address-first-name" name="first_name" type="text" autocomplete="given-name" maxlength="100" value="<?= htmlspecialchars((string) ($addressForm['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('first_name') }" :aria-invalid="invalid('first_name')" @input="onInput('first_name')" @blur="onBlur('first_name')">
                    <p class="store-field-error" x-cloak x-show="invalid('first_name')" x-text="error('first_name')"></p>
                </div>
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="address-last-name">Фамилия</label>
                    <input class="<?= $inputClass ?>" id="address-last-name" name="last_name" type="text" autocomplete="family-name" maxlength="100" value="<?= htmlspecialchars((string) ($addressForm['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('last_name') }" :aria-invalid="invalid('last_name')" @input="onInput('last_name')" @blur="onBlur('last_name')">
                    <p class="store-field-error" x-cloak x-show="invalid('last_name')" x-text="error('last_name')"></p>
                </div>
            </div>
            <div class="grid gap-4" x-show="party === 'company'"<?php if ($party !== UserAddress::PARTY_COMPANY): ?> x-cloak<?php endif; ?>>
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="address-company">Име на фирмата</label>
                    <input class="<?= $inputClass ?>" id="address-company" name="company_name" type="text" autocomplete="organization" maxlength="191" value="<?= htmlspecialchars((string) ($addressForm['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('company_name') }" :aria-invalid="invalid('company_name')" @input="onInput('company_name')" @blur="onBlur('company_name')">
                    <p class="store-field-error" x-cloak x-show="invalid('company_name')" x-text="error('company_name')"></p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <label class="text-sm font-semibold" for="address-eik">ЕИК</label>
                        <input class="<?= $inputClass ?>" id="address-eik" name="eik" type="text" inputmode="numeric" maxlength="9" value="<?= htmlspecialchars((string) ($addressForm['eik'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('eik') }" :aria-invalid="invalid('eik')" @input="onInput('eik')" @blur="onBlur('eik')">
                        <p class="store-field-error" x-cloak x-show="invalid('eik')" x-text="error('eik')"></p>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-semibold" for="address-vat">ДДС номер <span class="font-normal text-muted">(по желание)</span></label>
                        <input class="<?= $inputClass ?>" id="address-vat" name="vat_number" type="text" maxlength="16" placeholder="BG123456789" value="<?= htmlspecialchars((string) ($addressForm['vat_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('vat_number') }" :aria-invalid="invalid('vat_number')" @input="onInput('vat_number')" @blur="onBlur('vat_number')">
                        <p class="store-field-error" x-cloak x-show="invalid('vat_number')" x-text="error('vat_number')"></p>
                    </div>
                </div>
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="address-mol">МОЛ</label>
                    <input class="<?= $inputClass ?>" id="address-mol" name="mol" type="text" maxlength="191" value="<?= htmlspecialchars((string) ($addressForm['mol'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('mol') }" :aria-invalid="invalid('mol')" @input="onInput('mol')" @blur="onBlur('mol')">
                    <p class="store-field-error" x-cloak x-show="invalid('mol')" x-text="error('mol')"></p>
                </div>
            </div>
            </div>
        </div>

        <div class="store-form-section">
            <p class="store-form-section-title">Адрес</p>
            <div class="store-form-section-body">
            <div class="grid gap-1.5">
                <label class="text-sm font-semibold" for="address-line1">Улица и номер</label>
                <input class="<?= $inputClass ?>" id="address-line1" name="line1" type="text" autocomplete="street-address" required maxlength="191" value="<?= htmlspecialchars((string) ($addressForm['line1'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('line1') }" :aria-invalid="invalid('line1')" @input="onInput('line1')" @blur="onBlur('line1')">
                <p class="store-field-error" x-cloak x-show="invalid('line1')" x-text="error('line1')"></p>
            </div>
            </div>
        </div>

        <div class="store-form-section">
            <p class="store-form-section-title">Населено място</p>
            <div class="store-form-section-body">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="address-city">Град</label>
                    <input class="<?= $inputClass ?>" id="address-city" name="city" type="text" autocomplete="address-level2" required maxlength="100" value="<?= htmlspecialchars((string) ($addressForm['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('city') }" :aria-invalid="invalid('city')" @input="onInput('city')" @blur="onBlur('city')">
                    <p class="store-field-error" x-cloak x-show="invalid('city')" x-text="error('city')"></p>
                </div>
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="address-postal">Пощенски код</label>
                    <input class="<?= $inputClass ?>" id="address-postal" name="postal_code" type="text" inputmode="numeric" maxlength="4" autocomplete="postal-code" required value="<?= htmlspecialchars((string) ($addressForm['postal_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" :class="{ 'is-invalid': invalid('postal_code') }" :aria-invalid="invalid('postal_code')" @input="onInput('postal_code')" @blur="onBlur('postal_code')">
                    <p class="store-field-error" x-cloak x-show="invalid('postal_code')" x-text="error('postal_code')"></p>
                </div>
            </div>
            </div>
        </div>

        <div class="store-form-section">
            <p class="store-form-section-title" id="address-country-label">Държава</p>
            <div class="store-form-section-body">
            <div class="grid gap-1.5">
                <select
                    class="<?= $inputClass ?>"
                    id="address-country"
                    name="country"
                    required
                    aria-labelledby="address-country-label"
                    :class="{ 'is-invalid': invalid('country') }"
                    :aria-invalid="invalid('country')"
                    @change="onInput('country')"
                    @blur="onBlur('country')"
                >
                    <?php foreach ($europeanCountries as $countryName): ?>
                        <option value="<?= htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8') ?>" <?= $countryName === $countryValue ? 'selected' : '' ?>><?= htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="store-field-error" x-cloak x-show="invalid('country')" x-text="error('country')"></p>
            </div>
            </div>
        </div>

        <div class="store-form-section">
            <p class="store-form-section-title">Настройки</p>
            <div class="store-form-section-body">
            <label class="store-check">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" <?= $isDefault ? 'checked' : '' ?>>
                <span class="store-check-box" aria-hidden="true"></span>
                Основен адрес за фактуриране
            </label>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="store-submit">
                    <span class="store-submit-spinner" x-cloak x-show="busy"></span>
                    <span x-text="busy ? 'Записване…' : idleLabel"><?= htmlspecialchars($saveLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <?php if ($editingAddressId !== null): ?>
                    <a class="inline-flex h-[42px] items-center border border-line bg-canvas px-4 font-semibold" href="/account/addresses">Отказ</a>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </form>
    </div>
</article>

<?php if ($billingAddresses->isNotEmpty()): ?>
    <p class="store-address-list-title">Записани адреси</p>
    <div class="store-address-list">
        <?php foreach ($billingAddresses as $address): ?>
            <article class="store-address-card" x-data="{ busy: false }" :class="busy && 'is-saving'">
                <div class="store-address-card-head">
                    <h2><?= htmlspecialchars($address->title() !== '' ? $address->title() : 'Адрес', ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="store-address-tags">
                        <span class="store-profile-tag"><?= $address->isCompany() ? 'Фирма' : 'Физическо лице' ?></span>
                        <?php if ($address->is_default): ?>
                            <span class="store-profile-tag">Основен</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="store-address-lines">
                    <?php foreach ($address->lines() as $line): ?>
                        <?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?><br>
                    <?php endforeach; ?>
                </p>
                <div class="store-address-actions">
                    <a class="store-address-btn store-address-btn--primary" href="/account/addresses?edit=<?= (int) $address->id ?>">
                        <?= Html::iconSvg('pencil') ?>
                        Редактирай
                    </a>
                    <?php if (!$address->is_default): ?>
                        <form method="post" action="/account/addresses/<?= (int) $address->id ?>/default" @submit="busy = true">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="store-address-btn">
                                <?= Html::iconSvg('star') ?>
                                Основен
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="/account/addresses/<?= (int) $address->id ?>/delete" @submit="if (!confirm('Изтриване на този адрес за фактуриране?')) { $event.preventDefault(); return; } busy = true">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="store-address-btn store-address-btn--danger">
                            <?= Html::iconSvg('trash') ?>
                            Изтрий
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
