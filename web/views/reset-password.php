<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$email = (string) ($email ?? '');
$token = (string) ($token ?? '');
$errors = (array) ($errors ?? []);
$message = $message ?? null;
$isError = (bool) ($isError ?? false);
$done = (bool) ($done ?? false);
$fieldError = static function (array $errors, string $key): string {
    $value = $errors[$key] ?? '';
    $value = is_array($value) ? ($value[0] ?? '') : $value;

    return is_string($value) ? $value : '';
};
?>
<section class="store-auth store-auth--single">
    <div class="store-auth-column">
        <p class="store-auth-eyebrow">Сигурност на профила</p>
        <h1>Нова парола</h1>
        <?php if ($message !== null): ?>
            <p class="store-auth-message <?= $isError ? 'is-error' : '' ?>" role="status"><?= $escape($message) ?></p>
        <?php endif; ?>
        <?php if ($done): ?>
            <a class="store-button store-button-link" href="/login">Към входа</a>
        <?php elseif ($email === '' || strlen($token) < 32): ?>
            <p class="store-auth-lead">Линкът е непълен или невалиден. Поискайте нов от страницата за забравена парола.</p>
            <a class="store-auth-back" href="/forgot-password">Към забравена парола</a>
        <?php else: ?>
            <p class="store-auth-lead">Изберете нова парола за <?= $escape($email) ?>.</p>
            <form class="store-auth-form" method="post" action="/reset-password" novalidate>
                <input type="hidden" name="_token" value="<?= $escape($csrf ?? '') ?>">
                <input type="hidden" name="email" value="<?= $escape($email) ?>">
                <input type="hidden" name="token" value="<?= $escape($token) ?>">
                <label>Нова парола
                    <input name="password" type="password" autocomplete="new-password" required>
                    <?php if (($error = $fieldError($errors, 'password')) !== ''): ?><small><?= $escape($error) ?></small><?php endif; ?>
                </label>
                <label>Потвърдете паролата
                    <input name="password_confirmation" type="password" autocomplete="new-password" required>
                    <?php if (($error = $fieldError($errors, 'password_confirmation')) !== ''): ?><small><?= $escape($error) ?></small><?php endif; ?>
                </label>
                <button class="store-button" type="submit">Запази паролата</button>
            </form>
        <?php endif; ?>
    </div>
</section>
