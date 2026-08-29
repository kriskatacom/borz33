<?php

declare(strict_types=1);

use App\Models\User;

/** @var User $user */
/** @var string $csrf */
/** @var string|null $message */
/** @var bool $isError */

$csrf = $csrf ?? '';
$message = $message ?? null;
$isError = $isError ?? false;
$theme = in_array((string) $user->theme, [User::THEME_LIGHT, User::THEME_DARK, User::THEME_SYSTEM], true)
    ? (string) $user->theme
    : User::THEME_SYSTEM;
?>
<section class="max-w-md">
    <h1 class="m-0 text-2xl leading-snug font-semibold">Профил</h1>
    <p class="mt-3 text-muted"><?= htmlspecialchars($user->fullName(), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($message): ?>
        <p class="mt-4 border border-line bg-canvas px-3 py-2.5 <?= $isError ? 'border-ink font-medium' : '' ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h2 class="mt-7 mb-2 text-[1.05rem] font-semibold">Тема на сайта</h2>
    <p class="m-0 text-muted">По подразбиране е системната — следва светлата или тъмната тема на устройството. Изборът се запазва в профила и няма общо с админ панела.</p>

    <form class="mt-3" method="post" action="/account/theme">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="inline-flex border border-line" role="radiogroup" aria-label="Тема на сайта">
            <?php foreach ([User::THEME_LIGHT => 'Светла', User::THEME_DARK => 'Тъмна', User::THEME_SYSTEM => 'Системна'] as $value => $label): ?>
                <button
                    type="submit"
                    name="theme"
                    value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
                    class="h-[38px] border-0 px-2.5 text-sm font-semibold <?= $theme === $value ? 'bg-ink text-on-accent' : 'bg-canvas text-ink' ?>"
                    <?= $theme === $value ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>
                    @click="$store.theme.set('<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>')"
                ><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
            <?php endforeach; ?>
        </div>
    </form>

    <form class="mt-3" method="post" action="/logout">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-ink bg-transparent px-4 font-semibold text-ink">Изход</button>
    </form>
</section>
