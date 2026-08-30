<?php

declare(strict_types=1);

/** @var \App\Models\ContactMessage $contactMessage */
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$adminConversationUrl = (string) ($adminConversationUrl ?? '');
?>
<p style="margin:0 0 20px;font-size:18px;">Ново съобщение от контактната форма</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;background:#f6f1e7;">
    <tr><td style="padding:9px 14px;color:#6b645b;">Име</td><td style="padding:9px 14px;font-weight:bold;"><?= $escape($contactMessage->name) ?></td></tr>
    <tr><td style="padding:9px 14px;color:#6b645b;">Имейл</td><td style="padding:9px 14px;"><a href="mailto:<?= $escape($contactMessage->email) ?>"><?= $escape($contactMessage->email) ?></a></td></tr>
    <tr><td style="padding:9px 14px;color:#6b645b;">Телефон</td><td style="padding:9px 14px;"><?= $escape($contactMessage->phone ?: '—') ?></td></tr>
    <tr><td style="padding:9px 14px;color:#6b645b;">Тема</td><td style="padding:9px 14px;font-weight:bold;"><?= $escape($contactMessage->subject) ?></td></tr>
</table>
<div style="padding:18px;border-left:4px solid #173f32;background:#eef2ed;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;white-space:pre-wrap;"><?= $escape($contactMessage->message) ?></div>
<?php if (!empty($attachmentLinks)): ?><p style="margin:18px 0 8px;font-weight:bold;">Прикачени файлове</p><?php foreach ($attachmentLinks as $file): ?><p style="margin:5px 0;"><a href="<?= $escape($file['url']) ?>"><?= $escape($file['name']) ?></a></p><?php endforeach; ?><?php endif; ?>
<p style="margin:22px 0 0;"><a href="<?= $escape($adminConversationUrl) ?>" style="display:inline-block;padding:12px 20px;background:#173f32;color:#fff;text-decoration:none;font-weight:bold;">Отвори разговора</a></p>
