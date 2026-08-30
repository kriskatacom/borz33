<?php

declare(strict_types=1);

/** @var \App\Models\ContactMessage $contactMessage */
/** @var \App\Models\ContactMessageReply $reply */
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$customerConversationUrl = (string) ($customerConversationUrl ?? '');
?>
<p style="margin:0 0 8px;font-size:18px;">Здравейте, <?= $escape($contactMessage->name) ?>.</p>
<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6b645b;">Отговаряме на запитването Ви „<?= $escape($contactMessage->subject) ?>“.</p>
<div style="margin:0 0 22px;padding:18px;border-left:4px solid #173f32;background:#eef2ed;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.75;white-space:pre-wrap;"><?= $escape($reply->body) ?></div>
<p style="margin:0 0 18px;"><a href="<?= $escape($customerConversationUrl) ?>" style="display:inline-block;padding:12px 20px;background:#173f32;color:#fff;text-decoration:none;font-weight:bold;"><?= !empty($hasConversation) ? 'Виж и отговори' : 'Вход в профила' ?></a></p>
<?php if (!empty($hasConversation)): ?><p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#6b645b;">Разговорът е достъпен в раздел „Съобщения“ на профила Ви.</p><?php endif; ?>
