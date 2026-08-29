<?php

declare(strict_types=1);

/** @var array<string, string> $profile */
/** @var array<string, mixed> $profileErrors */
/** @var string $csrf */
/** @var string $inputClass */
/** @var callable $fieldError */

$profile = $profile ?? [];
$csrf = $csrf ?? '';
?>
<p class="mt-0 mb-5 text-muted">Име, имейл и телефон за поръчки и известия. Смяната на имейл изисква текущата парола.</p>

<form class="grid gap-4" method="post" action="/account/profile" novalidate>
    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-1.5">
            <label class="text-sm font-semibold" for="account-first-name">Име</label>
            <input class="<?= $inputClass ?>" id="account-first-name" name="first_name" type="text" autocomplete="given-name" required value="<?= htmlspecialchars($profile['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($fieldError($profileErrors, 'first_name')): ?>
                <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($profileErrors, 'first_name'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <div class="grid gap-1.5">
            <label class="text-sm font-semibold" for="account-last-name">Фамилия</label>
            <input class="<?= $inputClass ?>" id="account-last-name" name="last_name" type="text" autocomplete="family-name" required value="<?= htmlspecialchars($profile['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($fieldError($profileErrors, 'last_name')): ?>
                <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($profileErrors, 'last_name'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid gap-1.5">
        <label class="text-sm font-semibold" for="account-email">Имейл</label>
        <input class="<?= $inputClass ?>" id="account-email" name="email" type="email" autocomplete="email" required value="<?= htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($fieldError($profileErrors, 'email')): ?>
            <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($profileErrors, 'email'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="grid gap-1.5">
        <label class="text-sm font-semibold" for="account-phone">Телефон</label>
        <input class="<?= $inputClass ?>" id="account-phone" name="phone" type="tel" autocomplete="tel" value="<?= htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($fieldError($profileErrors, 'phone')): ?>
            <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($profileErrors, 'phone'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="grid gap-1.5">
        <label class="text-sm font-semibold" for="account-current-password">Текуща парола</label>
        <input class="<?= $inputClass ?>" id="account-current-password" name="current_password" type="password" autocomplete="current-password">
        <p class="m-0 text-sm text-muted">Нужна е само ако сменяте имейла.</p>
        <?php if ($fieldError($profileErrors, 'current_password')): ?>
            <p class="m-0 text-sm text-muted"><?= htmlspecialchars((string) $fieldError($profileErrors, 'current_password'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <button type="submit" class="inline-flex h-[42px] w-fit items-center justify-center border border-accent bg-accent px-4 font-semibold text-on-accent">Запази данните</button>
</form>
