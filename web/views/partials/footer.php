<?php

declare(strict_types=1);

use Store\Core\Html;

/** @var \App\Models\User|null $currentUser */
/** @var \Illuminate\Support\Collection<int, \App\Models\Category> $navCategories */
/** @var array{url: string, alt: string}|null $siteLogo */

$company = require dirname(__DIR__, 3) . '/config/company.php';
$footerEscape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$footerInternalPath = static function (mixed $value, string $fallback): string {
    $path = parse_url(trim((string) $value), PHP_URL_PATH);
    $path = is_string($path) && trim($path) !== '' ? $path : $fallback;

    return '/' . ltrim($path, '/');
};
$footerCategories = ($navCategories ?? collect())->take(4);
$phone = trim((string) ($company['phone'] ?? ''));
$email = trim((string) ($company['email'] ?? ''));
$email = str_ends_with($email, '.local') ? '' : $email;
$streetAddress = trim((string) ($company['address'] ?? ''));
$streetAddress = $streetAddress === 'ул. Примерна 1' ? '' : $streetAddress;
$address = $streetAddress !== ''
    ? implode(', ', array_filter([
        $streetAddress,
        trim((string) ($company['postal_code'] ?? '')),
        trim((string) ($company['city'] ?? '')),
    ]))
    : '';
$siteLogo = is_array($siteLogo ?? null) ? $siteLogo : null;
$siteName = trim((string) ($company['name'] ?? 'Borz33')) ?: 'Borz33';
?>
<footer class="store-footer">
    <div class="store-footer-inner">
        <section class="store-footer-benefits" aria-label="Предимства на магазина">
            <div>
                <span aria-hidden="true"><?= Html::iconSvg('package') ?></span>
                <p><strong>Доставка с Еконт</strong><small>До удобен офис или личен адрес</small></p>
            </div>
            <div>
                <span aria-hidden="true"><?= Html::iconSvg('badge-check') ?></span>
                <p><strong>Сигурна поръчка</strong><small>Ясна крайна цена преди завършване</small></p>
            </div>
            <div>
                <span aria-hidden="true"><?= Html::iconSvg('phone') ?></span>
                <p><strong>Лично отношение</strong><small>Свързваме се с вас при необходимост</small></p>
            </div>
        </section>

        <div class="store-footer-main">
            <section class="store-footer-brand" aria-labelledby="footer-brand-title">
                <a class="store-footer-logo" href="/" id="footer-brand-title" aria-label="<?= $footerEscape($company['name'] ?? 'Borz33') ?>, начало">
                    <?php if ($siteLogo !== null): ?>
                        <img class="store-footer-logo-image" src="<?= $footerEscape($siteLogo['url']) ?>" alt="<?= $footerEscape($siteLogo['alt'] !== '' ? $siteLogo['alt'] : $siteName) ?>">
                    <?php else: ?>
                        <?= $footerEscape($siteName) ?>
                    <?php endif; ?>
                </a>
                <p>Подбрани продукти с характер, създадени за вашия стил и ежедневие.</p>
                <?php if ($email !== ''): ?>
                    <a class="store-footer-contact-link" href="mailto:<?= $footerEscape($email) ?>">
                        <span aria-hidden="true"><?= Html::iconSvg('mail') ?></span>
                        <?= $footerEscape($email) ?>
                    </a>
                <?php endif; ?>
            </section>

            <nav class="store-footer-nav" aria-label="Пазаруване">
                <h2>Пазаруване</h2>
                <a href="/catalog">Всички продукти</a>
                <?php foreach ($footerCategories as $category): ?>
                    <a href="/catalog/<?= $footerEscape($category->slug) ?>"><?= $footerEscape($category->name) ?></a>
                <?php endforeach; ?>
                <a href="/favorites">Любими продукти</a>
            </nav>

            <nav class="store-footer-nav" aria-label="Полезни връзки">
                <h2>Полезно</h2>
                <a href="/cart">Количка</a>
                <a href="<?= $currentUser !== null ? '/account/orders' : '/login' ?>">
                    <?= $currentUser !== null ? 'Моите поръчки' : 'Вход в профила' ?>
                </a>
                <a href="<?= $footerEscape($footerInternalPath($company['terms_url'] ?? null, '/terms')) ?>">Общи условия</a>
                <a href="<?= $footerEscape($footerInternalPath($company['privacy_url'] ?? null, '/privacy')) ?>">Поверителност</a>
                <a href="/ceni-za-dostavka">Цени за доставка</a>
            </nav>

            <section class="store-footer-contact" aria-labelledby="footer-contact-title">
                <h2 id="footer-contact-title">Контакти</h2>
                <?php if ($phone !== ''): ?>
                    <a href="tel:<?= $footerEscape(preg_replace('/[^\d+]/', '', $phone) ?? $phone) ?>">
                        <span aria-hidden="true"><?= Html::iconSvg('phone') ?></span>
                        <?= $footerEscape($phone) ?>
                    </a>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <a href="mailto:<?= $footerEscape($email) ?>">
                        <span aria-hidden="true"><?= Html::iconSvg('mail') ?></span>
                        <?= $footerEscape($email) ?>
                    </a>
                <?php endif; ?>
                <a href="/contact"><span aria-hidden="true"><?= Html::iconSvg('mail') ?></span>Изпратете съобщение</a>
                <?php if ($address !== ''): ?>
                    <p>
                        <span aria-hidden="true"><?= Html::iconSvg('map-pin') ?></span>
                        <?= $footerEscape($address) ?>
                    </p>
                <?php endif; ?>
            </section>
        </div>

        <div class="store-footer-bottom">
            <p>© <?= date('Y') ?> <?= $footerEscape($company['legal_name'] ?? $company['name'] ?? 'Borz33') ?>. Всички права запазени.</p>
            <p>
                <?php if (trim((string) ($company['eik'] ?? '')) !== '' && trim((string) $company['eik']) !== '000000000'): ?>
                    ЕИК <?= $footerEscape($company['eik']) ?>
                <?php endif; ?>
                <?php if (trim((string) ($company['vat'] ?? '')) !== ''): ?>
                    <span>ДДС № <?= $footerEscape($company['vat']) ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</footer>
