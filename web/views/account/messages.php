<?php
declare(strict_types=1);

use Store\Core\Html;

$contactMessages = $contactMessages ?? collect();
$activeContactMessage = $activeContactMessage ?? null;
$conversationStarted = (bool) ($conversationStarted ?? false);
$conversationEmailSent = (bool) ($conversationEmailSent ?? false);
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatDate = static fn ($date): string => $date?->timezone('Europe/Sofia')->format('d.m.Y, H:i') ?? '';
$isoDate = static fn ($date): string => $date?->toIso8601String() ?? '';
$formatSize = static fn (int $bytes): string => $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', ' ') . ' MB' : max(1, (int) ceil($bytes / 1024)) . ' KB';
$renderAttachments = static function ($attachments) use ($escape, $formatSize): void {
    if ($attachments->isEmpty()) return;
    echo '<div class="store-message-attachments">';
    foreach ($attachments as $attachment) {
        $file = $attachment->file;
        if ($file === null) continue;
        echo '<a href="/' . $escape(ltrim((string) $file->path, '/')) . '" target="_blank" rel="noopener"><span>' . Html::iconSvg('paperclip') . '</span><span><strong>' . $escape($file->original_name) . '</strong><small>' . $escape($formatSize((int) $file->size)) . '</small></span></a>';
    }
    echo '</div>';
};
?>
<p class="store-account-messages-intro">Вашите запитвания и отговорите от нашия екип.</p>

<?php if ($contactMessages->isEmpty()): ?>
    <section class="store-account-messages-empty">
        <span aria-hidden="true"><?= Html::iconSvg('mail') ?></span>
        <h3>Все още нямате разговори</h3>
        <p>Изпратете ни запитване и ще можете да продължите разговора оттук.</p>
        <a class="store-btn" href="/contact">Ново запитване</a>
    </section>
<?php else: ?>
    <div class="store-account-messages-layout">
        <nav class="store-account-conversations" aria-label="Вашите разговори">
            <?php foreach ($contactMessages as $thread): ?>
                <a href="/account/messages?conversation=<?= (int) $thread->id ?>#conversation" class="<?= $activeContactMessage?->id === $thread->id ? 'is-active' : '' ?>">
                    <strong><?= $escape($thread->subject) ?></strong>
                    <span><?= $escape(mb_strimwidth((string) ($thread->replies->last()?->body ?? $thread->message), 0, 80, '…')) ?></span>
                    <small><?= $escape($formatDate($thread->replies->last()?->created_at ?? $thread->created_at)) ?></small>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($activeContactMessage): ?>
            <section id="conversation" class="store-account-conversation" aria-labelledby="conversation-title" data-account-conversation>
                <header><div><small>Разговор</small><h3 id="conversation-title"><?= $escape($activeContactMessage->subject) ?></h3></div><span>#<?= (int) $activeContactMessage->id ?></span></header>
                <div class="store-account-chat" aria-live="polite">
                    <article class="is-customer"><div><?= nl2br($escape($activeContactMessage->message)) ?><?php $renderAttachments($activeContactMessage->attachments); ?></div><small>Вие · <time datetime="<?= $escape($isoDate($activeContactMessage->created_at)) ?>"><?= $escape($formatDate($activeContactMessage->created_at)) ?></time></small></article>
                    <?php foreach ($activeContactMessage->replies as $reply): ?>
                        <?php $customer = $reply->sender_type === 'customer'; ?>
                        <article class="<?= $customer ? 'is-customer' : 'is-admin' ?>">
                            <div><?= nl2br($escape($reply->body)) ?><?php $renderAttachments($reply->attachments); ?></div>
                            <small><?= $customer ? 'Вие' : $escape($reply->admin?->fullName() ?: 'Екипът на Borz33') ?> · <time datetime="<?= $escape($isoDate($reply->created_at)) ?>"><?= $escape($formatDate($reply->created_at)) ?></time></small>
                        </article>
                    <?php endforeach; ?>
                </div>
                <form class="store-account-message-reply" method="post" action="/account/messages/<?= (int) $activeContactMessage->id ?>/reply" data-account-message-reply>
                    <input type="hidden" name="_token" value="<?= $escape($csrf) ?>">
                    <label for="account-message-reply">Вашият отговор</label>
                    <textarea id="account-message-reply" name="body" minlength="2" maxlength="10000" required placeholder="Напишете отговор…"></textarea>
                    <div><small data-account-message-status>Отговорът ще бъде добавен към този разговор.</small><button class="store-account-message-send" type="submit"><span data-submit-label>Изпрати</span><span class="store-account-message-send-icon" aria-hidden="true"><?= Html::iconSvg('send') ?></span></button></div>
                </form>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($conversationStarted && $activeContactMessage): ?>
    <dialog class="store-conversation-welcome" data-conversation-welcome aria-labelledby="conversation-welcome-title" aria-describedby="conversation-welcome-description">
        <button class="store-conversation-welcome-close" type="button" data-conversation-welcome-close aria-label="Затвори">×</button>
        <span class="store-conversation-welcome-icon" aria-hidden="true"><?= Html::iconSvg('mail') ?></span>
        <p class="store-conversation-welcome-kicker">Запитването е изпратено</p>
        <h2 id="conversation-welcome-title">Разговорът Ви е създаден</h2>
        <p id="conversation-welcome-description">Оттук можете да следите цялата кореспонденция и да отговаряте директно на нашия екип.</p>
        <ul>
            <li>Всеки отговор остава запазен в този разговор.</li>
            <li>При нов отговор ще получите известие и по имейл.</li>
            <li><?= $conversationEmailSent ? 'Изпратихме потвърждение на посочения от Вас имейл.' : 'Разговорът е записан, но потвърждението по имейл не можа да бъде изпратено.' ?></li>
        </ul>
        <button class="store-conversation-welcome-action" type="button" data-conversation-welcome-close>Към разговора</button>
    </dialog>
<?php endif; ?>
