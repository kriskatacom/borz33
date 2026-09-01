<?php

declare(strict_types=1);

/** @var string $first_name */
/** @var string $code */
/** @var int $expires_minutes */

?>
<p style="margin:0 0 16px;font-size:18px;">Здравейте, <?= htmlspecialchars($first_name) ?>.</p>
<p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:#3f3a34;">
    За да потвърдите регистрацията си, въведете следния код. Той е валиден <?= (int) $expires_minutes ?> минути.
</p>
<p style="margin:0 0 24px;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:30px;font-weight:700;line-height:1.3;letter-spacing:0.16em;color:#173f32;">
    <?= htmlspecialchars($code) ?>
</p>
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#6b645b;">
    Ако не сте създавали профил, просто игнорирайте това писмо. Кодът няма да активира нищо без ваше действие.
</p>
