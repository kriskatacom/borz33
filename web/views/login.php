<?php

declare(strict_types=1);

use Store\Core\Html;

/** @var string $step */
/** @var string $email */
/** @var array<string, mixed> $errors */
/** @var string|null $message */
/** @var bool $isError */
/** @var string $csrf */
/** @var array<string, string> $register */
/** @var array<string, mixed> $registerErrors */
/** @var string|null $registerMessage */
/** @var bool $registerIsError */
/** @var bool $showVerify */
/** @var string $returnTo */

$step = $step ?? 'credentials';
$email = $email ?? '';
$errors = $errors ?? [];
$message = $message ?? null;
$isError = $isError ?? false;
$csrf = $csrf ?? '';
$register = $register ?? ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];
$registerErrors = $registerErrors ?? [];
$registerMessage = $registerMessage ?? null;
$registerIsError = $registerIsError ?? false;
$showVerify = $showVerify ?? false;
$returnTo = $returnTo ?? '';

$fieldError = static function (array $errors, string $key): ?string {
    $value = $errors[$key] ?? null;

    if (is_array($value)) {
        $first = $value[0] ?? null;

        return is_string($first) ? $first : null;
    }

    return is_string($value) ? $value : null;
};

$inputClass = 'h-[42px] w-full border border-line bg-canvas px-3 text-ink';
?>
<section
    class="<?= $step === 'device' ? 'max-w-md' : 'grid items-start gap-10 md:grid-cols-2 md:gap-14' ?>"
    x-data="{ showLoginPassword: false, showRegisterPassword: false, showRegisterConfirm: false }"
>
    <div>
        <h1 class="m-0 text-2xl leading-snug font-semibold"><?= $step === 'device' ? 'Потвърдете устройството' : 'Вход' ?></h1>
        <p class="mt-3 text-muted">
            <?php if ($step === 'device'): ?>
                Изпратихме 6-цифрен код на имейла. Въведете го, за да доверим този браузър.
            <?php else: ?>
                Влезте с имейл и парола, за да управлявате профила и темата на сайта.
            <?php endif; ?>
        </p>

        <?php if ($message): ?>
            <p class="mt-4 border border-line bg-canvas px-3 py-2.5 <?= $isError ? 'border-ink font-medium' : '' ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form class="mt-5 grid gap-4" method="post" action="/login" novalidate>
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="step" value="<?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($returnTo !== ''): ?><input type="hidden" name="return" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>

            <?php if ($step === 'credentials'): ?>
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="login-email">Имейл</label>
                    <input class="<?= $inputClass ?>" id="login-email" name="email" type="email" autocomplete="username" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($fieldError($errors, 'email')): ?>
                        <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($errors, 'email'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="login-password">Парола</label>
                    <div class="flex items-stretch gap-1">
                        <input class="<?= $inputClass ?>" id="login-password" name="password" :type="showLoginPassword ? 'text' : 'password'" autocomplete="current-password" required>
                        <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showLoginPassword = !showLoginPassword" :aria-label="showLoginPassword ? 'Скрий паролата' : 'Покажи паролата'">
                            <span x-show="!showLoginPassword"><?= Html::iconSvg('eye') ?></span>
                            <span x-cloak x-show="showLoginPassword"><?= Html::iconSvg('eye-off') ?></span>
                        </button>
                    </div>
                    <?php if ($fieldError($errors, 'password')): ?>
                        <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($errors, 'password'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-accent bg-accent px-4 font-semibold text-on-accent">Вход</button>
            <?php else: ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                <div class="grid gap-1.5">
                    <label class="text-sm font-semibold" for="login-code">Код</label>
                    <input class="<?= $inputClass ?>" id="login-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="\d{6}" required>
                    <?php if ($fieldError($errors, 'code')): ?>
                        <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($errors, 'code'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-accent bg-accent px-4 font-semibold text-on-accent">Потвърди</button>
            <?php endif; ?>
        </form>

        <?php if ($step === 'device'): ?>
            <form class="mt-3" method="post" action="/login/code">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($returnTo !== ''): ?><input type="hidden" name="return" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-ink bg-transparent px-4 font-semibold text-ink">Изпрати нов код</button>
            </form>
            <p class="mt-3 text-muted"><a class="underline" href="/login">Назад към входа</a></p>
        <?php endif; ?>
    </div>

    <?php if ($step !== 'device'): ?>
        <div class="border-t border-line pt-10 md:border-t-0 md:border-l md:pt-0 md:pl-14">
            <h2 class="m-0 text-2xl leading-snug font-semibold">Регистрация</h2>
            <p class="mt-3 text-muted">Създайте профил, за да пазите любими продукти, поръчки и тема на сайта.</p>

            <?php if ($registerMessage): ?>
                <p class="mt-4 border border-line bg-canvas px-3 py-2.5 <?= $registerIsError ? 'border-ink font-medium' : '' ?>" role="status"><?= htmlspecialchars($registerMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($showVerify): ?>
                <form class="mt-5 grid gap-4" method="post" action="/register/verify" novalidate>
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars((string) $register['email'], ENT_QUOTES, 'UTF-8') ?>">
                    <p class="m-0 text-sm text-muted">Кодът е изпратен на <?= htmlspecialchars((string) $register['email'], ENT_QUOTES, 'UTF-8') ?>.</p>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-semibold" for="register-code">Код за потвърждение</label>
                        <input class="<?= $inputClass ?>" id="register-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="\d{6}" required>
                        <?php if ($fieldError($registerErrors, 'code')): ?>
                            <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($registerErrors, 'code'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-accent bg-accent px-4 font-semibold text-on-accent">Потвърди имейла</button>
                </form>
                <form class="mt-3" method="post" action="/register/resend">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars((string) $register['email'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-ink bg-transparent px-4 font-semibold text-ink">Изпрати нов код</button>
                </form>
            <?php else: ?>
                <form class="mt-5 grid gap-4" method="post" action="/register" novalidate>
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <label class="text-sm font-semibold" for="first_name">Име</label>
                            <input class="<?= $inputClass ?>" id="first_name" name="first_name" type="text" autocomplete="given-name" required value="<?= htmlspecialchars((string) $register['first_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($fieldError($registerErrors, 'first_name')): ?>
                                <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($registerErrors, 'first_name'), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="grid gap-1.5">
                            <label class="text-sm font-semibold" for="last_name">Фамилия</label>
                            <input class="<?= $inputClass ?>" id="last_name" name="last_name" type="text" autocomplete="family-name" required value="<?= htmlspecialchars((string) $register['last_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($fieldError($registerErrors, 'last_name')): ?>
                                <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($registerErrors, 'last_name'), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-semibold" for="register-email">Имейл</label>
                        <input class="<?= $inputClass ?>" id="register-email" name="email" type="email" autocomplete="email" required value="<?= htmlspecialchars((string) $register['email'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($fieldError($registerErrors, 'email')): ?>
                            <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($registerErrors, 'email'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-semibold" for="phone">Телефон <span class="font-normal text-muted">(по желание)</span></label>
                        <input class="<?= $inputClass ?>" id="phone" name="phone" type="tel" autocomplete="tel" value="<?= htmlspecialchars((string) $register['phone'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($fieldError($registerErrors, 'phone')): ?>
                            <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($registerErrors, 'phone'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-semibold" for="register-password">Парола</label>
                        <div class="flex items-stretch gap-1">
                            <input class="<?= $inputClass ?>" id="register-password" name="password" :type="showRegisterPassword ? 'text' : 'password'" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showRegisterPassword = !showRegisterPassword" :aria-label="showRegisterPassword ? 'Скрий паролата' : 'Покажи паролата'">
                                <span x-show="!showRegisterPassword"><?= Html::iconSvg('eye') ?></span>
                                <span x-cloak x-show="showRegisterPassword"><?= Html::iconSvg('eye-off') ?></span>
                            </button>
                        </div>
                        <p class="m-0 text-sm text-muted">Минимум 8 символа.</p>
                        <?php if ($fieldError($registerErrors, 'password')): ?>
                            <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($registerErrors, 'password'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-sm font-semibold" for="password_confirmation">Потвърдете паролата</label>
                        <div class="flex items-stretch gap-1">
                            <input class="<?= $inputClass ?>" id="password_confirmation" name="password_confirmation" :type="showRegisterConfirm ? 'text' : 'password'" autocomplete="new-password" minlength="8" required>
                            <button type="button" class="store-icon-btn store-icon-btn--sm" @click="showRegisterConfirm = !showRegisterConfirm" :aria-label="showRegisterConfirm ? 'Скрий паролата' : 'Покажи паролата'">
                                <span x-show="!showRegisterConfirm"><?= Html::iconSvg('eye') ?></span>
                                <span x-cloak x-show="showRegisterConfirm"><?= Html::iconSvg('eye-off') ?></span>
                            </button>
                        </div>
                        <?php if ($fieldError($registerErrors, 'password_confirmation')): ?>
                            <p class="m-0 text-sm text-muted"><?= htmlspecialchars($fieldError($registerErrors, 'password_confirmation'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-accent bg-accent px-4 font-semibold text-on-accent">Създай профил</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
