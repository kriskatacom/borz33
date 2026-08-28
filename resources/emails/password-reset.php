<?php

declare(strict_types=1);

/** @var string $first_name */
/** @var string $reset_url */
/** @var int $expires_minutes */
?>
<p style="margin:0 0 16px;font-size:18px;">Здравейте, <?= htmlspecialchars($first_name) ?>.</p>
<p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:#3f3a34;">
    Получихме заявка за нова парола към админ панела. Линкът е валиден <?= (int) $expires_minutes ?> минути
    и може да се използва само веднъж.
</p>
<p style="margin:0 0 24px;text-align:center;">
    <a href="<?= htmlspecialchars($reset_url) ?>" style="display:inline-block;background:#173f32;color:#fffdf8;text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:14px 22px;border-radius:999px;">
        Задай нова парола
    </a>
</p>
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#6b645b;">
    Ако не сте заявили смяна, игнорирайте писмото. Паролата няма да се промени.
</p>
