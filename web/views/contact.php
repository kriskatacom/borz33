<?php

declare(strict_types=1);

/** @var array<string, string> $form */
/** @var array<string, string> $errors */
/** @var string $csrf */
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$error = static fn (string $key): string => isset($errors[$key]) ? (string) $errors[$key] : '';
?>
<article class="store-contact-page">
    <header class="store-contact-head">
        <p>Ще Ви отговорим възможно най-скоро</p>
        <h1>Свържете се с нас</h1>
        <p>Изпратете въпрос за продукт, поръчка, доставка или персонализация.</p>
    </header>

    <?php if ($sent): ?>
        <div class="store-contact-success" role="status"><strong>Съобщението е изпратено.</strong><span>Благодарим Ви. Нашият екип ще се свърже с Вас на посочения имейл.</span></div>
    <?php endif; ?>
    <?php if ($message): ?><p class="store-contact-error" role="alert"><?= $escape($message) ?><?= $error('form') ? ' ' . $escape($error('form')) : '' ?></p><?php endif; ?>

    <form class="store-contact-form" method="post" action="/contact" novalidate>
        <input type="hidden" name="_token" value="<?= $escape($csrf) ?>">
        <label class="store-contact-honeypot" aria-hidden="true">Уебсайт<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        <div class="store-checkout-fields store-checkout-fields--two">
            <label class="<?= $error('name') ? 'has-error' : '' ?>"><span>Име <em>*</em></span><input name="name" value="<?= $escape($form['name'] ?? '') ?>" maxlength="160" autocomplete="name" required><?php if ($error('name')): ?><small><?= $escape($error('name')) ?></small><?php endif; ?></label>
            <label class="<?= $error('email') ? 'has-error' : '' ?>"><span>Имейл <em>*</em></span><input type="email" name="email" value="<?= $escape($form['email'] ?? '') ?>" maxlength="191" autocomplete="email" required><?php if ($error('email')): ?><small><?= $escape($error('email')) ?></small><?php endif; ?></label>
        </div>
        <div class="store-checkout-fields store-checkout-fields--two">
            <label class="<?= $error('phone') ? 'has-error' : '' ?>"><span>Телефон</span><input type="tel" name="phone" value="<?= $escape($form['phone'] ?? '') ?>" maxlength="40" autocomplete="tel"><?php if ($error('phone')): ?><small><?= $escape($error('phone')) ?></small><?php endif; ?></label>
            <label class="<?= $error('subject') ? 'has-error' : '' ?>"><span>Тема <em>*</em></span><input name="subject" value="<?= $escape($form['subject'] ?? '') ?>" maxlength="191" required><?php if ($error('subject')): ?><small><?= $escape($error('subject')) ?></small><?php endif; ?></label>
        </div>
        <label class="store-checkout-notes <?= $error('message') ? 'has-error' : '' ?>"><span>Съобщение *</span><textarea name="message" rows="8" minlength="10" maxlength="5000" required><?= $escape($form['message'] ?? '') ?></textarea><?php if ($error('message')): ?><small><?= $escape($error('message')) ?></small><?php endif; ?></label>
        <div class="store-contact-actions"><p>С изпращането приемате съобщението да бъде обработено с цел отговор на запитването.</p><button class="store-btn" type="submit">Изпрати съобщението</button></div>
    </form>
</article>
