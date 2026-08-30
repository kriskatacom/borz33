<?php
declare(strict_types=1);
/** @var \App\Models\ContactMessage $contactMessage */
/** @var \App\Models\ContactMessageReply $reply */
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<p style="margin:0 0 12px;font-size:18px;"><?= $escape($contactMessage->name) ?> изпрати нов отговор</p>
<p style="margin:0 0 18px;color:#6b645b;">Разговор: „<?= $escape($contactMessage->subject) ?>“</p>
<div style="margin:0 0 22px;padding:18px;border-left:4px solid #173f32;background:#eef2ed;line-height:1.7;white-space:pre-wrap;"><?= $escape($reply->body) ?></div>
<p style="margin:0;"><a href="<?= $escape($adminConversationUrl) ?>" style="display:inline-block;padding:12px 20px;background:#173f32;color:#fff;text-decoration:none;font-weight:bold;">Отвори разговора</a></p>
