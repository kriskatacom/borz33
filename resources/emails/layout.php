<?php

declare(strict_types=1);

/** @var array<string, string> $company */
/** @var string $content */
/** @var string $title */
/** @var string $preheader */

$company = $company ?? [];
$title = $title ?? ($company['name'] ?? 'Съобщение');
$preheader = $preheader ?? '';
$vatLine = ($company['vat'] ?? '') !== '' ? 'ДДС № ' . htmlspecialchars($company['vat']) . '<br>' : '';
$phoneLine = ($company['phone'] ?? '') !== '' ? ' · ' . htmlspecialchars($company['phone']) : '';
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
</head>
<body style="margin:0;padding:0;background:#f3efe6;font-family:Georgia,'Times New Roman',serif;color:#1c1917;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;"><?= htmlspecialchars($preheader) ?></div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3efe6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fffdf8;border:1px solid #e4ddd0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#173f32;padding:28px 32px;">
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;color:#c5d5cc;"><?= htmlspecialchars($company['name'] ?? '') ?></p>
                            <p style="margin:8px 0 0;font-size:22px;line-height:1.3;color:#fffdf8;"><?= htmlspecialchars($company['legal_name'] ?? '') ?></p>
                            <p style="margin:12px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#c5d5cc;">
                                <?= htmlspecialchars(trim(($company['address'] ?? '') . ', ' . ($company['postal_code'] ?? '') . ' ' . ($company['city'] ?? ''))) ?><br>
                                <?= htmlspecialchars($company['country'] ?? '') ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <?= $content ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;color:#6b645b;">
                            <p style="margin:0 0 12px;border-top:1px solid #e4ddd0;padding-top:20px;">
                                <?= htmlspecialchars($company['legal_name'] ?? '') ?><br>
                                ЕИК <?= htmlspecialchars($company['eik'] ?? '') ?><br>
                                <?= $vatLine ?>
                                <?= htmlspecialchars($company['address'] ?? '') ?>, <?= htmlspecialchars($company['postal_code'] ?? '') ?> <?= htmlspecialchars($company['city'] ?? '') ?>, <?= htmlspecialchars($company['country'] ?? '') ?><br>
                                <?= htmlspecialchars($company['email'] ?? '') ?><?= $phoneLine ?>
                            </p>
                            <p style="margin:0;">
                                <a href="<?= htmlspecialchars($company['website'] ?? '#') ?>" style="color:#173f32;text-decoration:underline;">Уебсайт</a>
                                ·
                                <a href="<?= htmlspecialchars($company['privacy_url'] ?? '#') ?>" style="color:#173f32;text-decoration:underline;">Политика за поверителност</a>
                                ·
                                <a href="<?= htmlspecialchars($company['terms_url'] ?? '#') ?>" style="color:#173f32;text-decoration:underline;">Общи условия</a>
                            </p>
                            <p style="margin:14px 0 0;font-size:11px;color:#8a8278;">
                                Получавате това съобщение, защото имате регистрация или заявка към <?= htmlspecialchars($company['name'] ?? '') ?>.
                                Обработваме данните съгласно GDPR и Закона за защита на личните данни. Това е служебно съобщение и не изисква съгласие за маркетинг.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
