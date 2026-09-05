<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$email = (string) ($email ?? '');
$errors = (array) ($errors ?? []);
$message = $message ?? null;
$isError = (bool) ($isError ?? false);
$emailError = $errors['email'] ?? null;
$emailError = is_array($emailError) ? ($emailError[0] ?? null) : $emailError;
?>
<section class="store-auth store-auth--single">
    <div class="store-auth-column">
        <h1>Забравена парола</h1>
        <p class="store-auth-lead">Въведете имейла си и ще изпратим линк за създаване на нова парола.</p>
        <?php if ($message !== null): ?>
            <p class="store-auth-message <?= $isError ? 'is-error' : '' ?>" role="status"><?= $escape($message) ?></p>
        <?php endif; ?>
        <form class="store-auth-form" method="post" action="/forgot-password" novalidate>
            <input type="hidden" name="_token" value="<?= $escape($csrf ?? '') ?>">
            <label>Имейл
                <input name="email" type="email" autocomplete="email" value="<?= $escape($email) ?>" required>
                <?php if (is_string($emailError) && $emailError !== ''): ?><small><?= $escape($emailError) ?></small><?php endif; ?>
            </label>
            <button class="store-button" type="submit">Изпрати линк</button>
        </form>
        <a class="store-auth-back" href="/login">Назад към входа</a>
    </div>
</section>
