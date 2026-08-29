<?php

declare(strict_types=1);

use Store\Core\DateFormat;
use Store\Core\Html;

/** @var \App\Models\User $user */

$phone = trim((string) ($user->phone ?? ''));
$firstName = trim((string) $user->first_name);
$greeting = DateFormat::greeting();
$lead = $firstName !== '' ? $greeting . ', ' . $firstName . '.' : $greeting . '.';
$emailVerified = $user->hasVerifiedEmail();

$stats = [
    [
        'href' => '/account/orders',
        'icon' => 'package',
        'value' => '0',
        'label' => 'Поръчки',
        'hint' => 'Ще се появят тук',
    ],
    [
        'href' => '/favorites',
        'icon' => 'heart',
        'value' => '0',
        'label' => 'Любими',
        'hint' => 'Продукти, които следите',
    ],
    [
        'href' => '/cart',
        'icon' => 'cart',
        'value' => '0',
        'label' => 'Количка',
        'hint' => 'Готови за поръчка',
    ],
];
?>
<p class="store-account-lead"><?= htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') ?> Ето какво е важно в акаунта в момента.</p>

<div class="store-account-stats">
    <?php foreach ($stats as $stat): ?>
        <a class="store-card store-account-stat" href="<?= htmlspecialchars($stat['href'], ENT_QUOTES, 'UTF-8') ?>">
            <span class="store-account-stat-icon"><?= Html::iconSvg($stat['icon']) ?></span>
            <strong><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="store-account-stat-label"><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="store-account-stat-hint"><?= htmlspecialchars($stat['hint'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="store-account-stat-go" aria-hidden="true"><?= Html::iconSvg('chevron-right') ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="store-account-dash">
    <article class="store-card">
        <h2>Активност</h2>
        <ul class="store-account-facts">
            <li>
                <span class="store-account-fact-icon"><?= Html::iconSvg('clock') ?></span>
                <div>
                    <span>Последен вход</span>
                    <strong><?php Html::timeAgo($user->last_login_at, 'В този браузър'); ?></strong>
                </div>
            </li>
            <li>
                <span class="store-account-fact-icon"><?= Html::iconSvg('user') ?></span>
                <div>
                    <span>Клиент от</span>
                    <strong><?php Html::timeAgo($user->created_at); ?></strong>
                </div>
            </li>
            <li>
                <span class="store-account-fact-icon"><?= Html::iconSvg($emailVerified ? 'badge-check' : 'mail') ?></span>
                <div>
                    <span>Имейл</span>
                    <strong><?= $emailVerified ? 'Потвърден' : 'Очаква потвърждение' ?></strong>
                </div>
            </li>
            <li>
                <span class="store-account-fact-icon"><?= Html::iconSvg('phone') ?></span>
                <div>
                    <span>Телефон</span>
                    <strong><?= $phone !== '' ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : 'Не е посочен' ?></strong>
                </div>
            </li>
        </ul>
    </article>

    <article class="store-card">
        <h2>Бързи действия</h2>
        <div class="mt-3">
            <a class="store-shortcut" href="/account/details">
                <span class="store-shortcut-icon"><?= Html::iconSvg('user') ?></span>
                <span class="min-w-0 flex-1">
                    <strong>Данни на акаунта</strong>
                    <span><?= $phone === '' ? 'Добавете телефон и проверете имейла' : 'Име, имейл и телефон' ?></span>
                </span>
                <?= Html::iconSvg('chevron-right') ?>
            </a>
            <a class="store-shortcut" href="/account/password">
                <span class="store-shortcut-icon"><?= Html::iconSvg('lock') ?></span>
                <span class="min-w-0 flex-1">
                    <strong>Смяна на парола</strong>
                    <span>Нова парола за този профил</span>
                </span>
                <?= Html::iconSvg('chevron-right') ?>
            </a>
            <a class="store-shortcut" href="/catalog">
                <span class="store-shortcut-icon"><?= Html::iconSvg('package') ?></span>
                <span class="min-w-0 flex-1">
                    <strong>Към каталога</strong>
                    <span>Продължете пазаруването</span>
                </span>
                <?= Html::iconSvg('chevron-right') ?>
            </a>
        </div>
    </article>
</div>
