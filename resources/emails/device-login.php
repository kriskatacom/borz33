<?php

declare(strict_types=1);

/** @var string $first_name */
/** @var string $code */
/** @var int $expires_minutes */

$digits = str_split($code);
?>
<p style="margin:0 0 16px;font-size:18px;">Здравейте, <?= htmlspecialchars($first_name) ?>.</p>
<p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:#3f3a34;">
    Открихме опит за вход от устройство, което не е използвано при създаването на профила.
    Въведете кода, за да потвърдите, че сте Вие. Валиден е <?= (int) $expires_minutes ?> минути.
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <?php foreach ($digits as $digit): ?>
            <td style="padding:0 4px;">
                <div style="width:40px;height:48px;border:1px solid #d7d0c4;border-radius:8px;background:#f6f1e7;text-align:center;font-size:22px;line-height:48px;color:#173f32;">
                    <?= htmlspecialchars($digit) ?>
                </div>
            </td>
        <?php endforeach; ?>
    </tr>
</table>
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#6b645b;">
    Ако това не сте Вие, игнорирайте писмото и сменете паролата си. Кодът не дава достъп без да го въведете.
</p>
