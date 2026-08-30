<?php
declare(strict_types=1);
/** @var \App\Models\ContactMessage $contactMessage */
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<p style="margin:0 0 8px;font-size:18px;">Здравейте, <?= $escape($contactMessage->name) ?>.</p>
<p style="margin:0 0 18px;line-height:1.7;">Получихме запитването Ви „<?= $escape($contactMessage->subject) ?>“. Ще Ви отговорим възможно най-скоро.</p>
<div style="margin:0 0 22px;padding:18px;border-left:4px solid #173f32;background:#eef2ed;line-height:1.7;white-space:pre-wrap;"><?= $escape($contactMessage->message) ?></div>
<?php if (!empty($attachmentLinks)): ?><p style="margin:0 0 8px;font-weight:bold;">Прикачени файлове</p><?php foreach ($attachmentLinks as $file): ?><p style="margin:5px 0;"><a href="<?= $escape($file['url']) ?>"><?= $escape($file['name']) ?></a></p><?php endforeach; ?><?php endif; ?>
<p style="margin:0;"><a href="<?= $escape($customerConversationUrl) ?>" style="display:inline-block;padding:12px 20px;background:#173f32;color:#fff;text-decoration:none;font-weight:bold;"><?= !empty($hasConversation) ? 'Отвори разговора' : 'Вход в профила' ?></a></p>
