<?php

declare(strict_types=1);

/** @var string $displayName */
/** @var string $initials */
/** @var string|null $memberSince */
/** @var string|null $avatarUrl */
/** @var array<string, mixed> $avatarConfig */
/** @var \App\Models\User $user */
/** @var string $csrf */
?>
<?php if (!$user->hasVerifiedEmail()): ?>
    <div class="mb-5 border border-amber-300 bg-amber-50 px-4 py-3 text-amber-950" role="status">
        <p class="m-0 font-semibold">Необходимо е да потвърдите имейл адреса си.</p>
        <p class="mb-3 mt-1 text-sm">Въведете 6-цифрения код, който изпратихме на <?= htmlspecialchars((string) $user->email, ENT_QUOTES, 'UTF-8') ?>.</p>
        <div class="flex flex-wrap items-end gap-2">
            <form class="flex flex-wrap items-end gap-2" method="post" action="/register/verify">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <label class="grid gap-1 text-sm font-semibold" for="profile-email-code">
                    Код за потвърждение
                    <input class="store-input h-[42px] w-44 border border-amber-300 bg-white px-3 text-ink" id="profile-email-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="\d{6}" required>
                </label>
                <button class="store-btn h-[42px] px-4" type="submit">Потвърди</button>
            </form>
            <form method="post" action="/register/resend">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button class="h-[42px] border border-amber-300 bg-transparent px-4 text-sm font-semibold" type="submit">Изпрати нов код</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<p class="mt-0 mb-5 text-muted">Вашата публична профилна информация и профилна снимка.</p>

<div class="store-profile-head store-profile-head--tab">
    <div
        class="store-profile-avatar-wrap"
        x-data='storeAvatar(<?= json_encode($avatarConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'
        @click.outside="open = false"
    >
        <button
            type="button"
            class="store-profile-avatar"
            @click="toggle()"
            :aria-expanded="open"
            aria-haspopup="true"
            aria-controls="store-avatar-panel"
            aria-label="Профилна снимка"
        >
            <img
                class="store-profile-avatar-img"
                x-show="url"
                :src="url"
                alt=""
                <?php if (is_string($avatarUrl) && $avatarUrl !== ''): ?>
                src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                <?php endif; ?>
            >
            <span x-show="!url" x-text="initials"<?php if (is_string($avatarUrl) && $avatarUrl !== ''): ?> style="display:none"<?php endif; ?>><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="store-profile-avatar-hint">Смени</span>
        </button>
        <input
            class="sr-only"
            type="file"
            accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
            x-ref="file"
            @change="onFile($event)"
        >
        <div
            id="store-avatar-panel"
            class="store-avatar-panel"
            x-cloak
            x-show="open"
            x-transition.opacity.duration.120ms
        >
            <p class="store-avatar-panel-title">Профилна снимка</p>
            <p class="store-avatar-panel-error" x-show="error" x-text="error"></p>
            <div class="store-avatar-presets">
                <template x-for="preset in presets" :key="preset.id">
                    <button
                        type="button"
                        class="store-avatar-preset"
                        :class="url === preset.url && 'is-selected'"
                        :aria-pressed="url === preset.url"
                        :aria-label="preset.label"
                        :disabled="busy"
                        @click="applyPreset(preset.id)"
                    >
                        <img :src="preset.url" alt="">
                    </button>
                </template>
            </div>
            <div class="store-avatar-panel-actions">
                <button type="button" class="store-avatar-action" :disabled="busy" @click="pickFile()">Качи снимка</button>
                <button type="button" class="store-avatar-action" x-show="url" :disabled="busy" @click="remove()">Премахни</button>
            </div>
        </div>
    </div>

    <div class="store-profile-copy min-w-0">
        <strong class="store-profile-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
        <p class="store-profile-email"><?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="store-profile-tags">
            <span class="store-profile-tag"><?= $user->hasVerifiedEmail() ? 'Потвърден имейл' : 'Имейлът очаква потвърждение' ?></span>
            <?php if ($memberSince !== null): ?>
                <span class="store-profile-tag">Клиент от <?= htmlspecialchars($memberSince, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
