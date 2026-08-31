<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
/** @var string $currentPath */
/** @var array<string, mixed> $seo */
/** @var bool $compactMainBottom */
/** @var bool $flushMainTop */
/** @var array{threshold:float,subtotal:float}|null $freeShippingNotice */

$title = $title ?? 'Borz33';
$currentPath = $currentPath ?? '/';
$seo = $seo ?? [];
$compactMainBottom = (bool) ($compactMainBottom ?? false);
$flushMainTop = (bool) ($flushMainTop ?? false);
$seoTitle = (string) ($seo['title'] ?? $title);
$seoDescription = (string) ($seo['description'] ?? 'Borz33 — онлайн магазин.');
$seoCanonical = (string) ($seo['canonical'] ?? '');
$seoRobots = (string) ($seo['robots'] ?? 'index, follow');
$seoImage = is_string($seo['image'] ?? null) ? (string) $seo['image'] : null;
$seoImageAlt = (string) ($seo['imageAlt'] ?? $seoTitle);
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
/** @var \App\Models\User|null $currentUser */
$currentUser = $currentUser ?? null;
$accountTheme = null;
$viteOrigin = \Store\Core\Vite::origin();
$freeShippingNotice = $freeShippingNotice ?? null;
$freeShippingUnlocked = $freeShippingNotice !== null && $freeShippingNotice['subtotal'] > $freeShippingNotice['threshold'];
$freeShippingRemaining = $freeShippingNotice !== null ? max(0.01, round($freeShippingNotice['threshold'] + 0.01 - $freeShippingNotice['subtotal'], 2)) : 0.0;
$freeShippingMessage = $freeShippingNotice === null
    ? ''
    : ($freeShippingUnlocked
        ? 'Поздравления — отключихте безплатна доставка!'
        : ($freeShippingNotice['subtotal'] <= 0
            ? 'Безплатна доставка за поръчки над ' . \Store\Services\ProductPage::money($freeShippingNotice['threshold']) . '.'
            : 'Добавете още ' . \Store\Services\ProductPage::money($freeShippingRemaining) . ', за да отключите безплатна доставка.'));

if ($currentUser !== null) {
    $value = (string) $currentUser->theme;
    if (in_array($value, ['light', 'dark', 'system'], true)) {
        $accountTheme = $value;
    }
}

function store_nav_active(string $href, string $path): bool
{
    if ($href === '/') {
        return $path === '/';
    }

    if ($href === '/catalog') {
        return $path === '/catalog';
    }

    return $path === $href || str_starts_with($path, $href . '/');
}

function store_asset(string $path): string
{
    $file = dirname(__DIR__) . '/public' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '0';

    return $path . '?v=' . $version;
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= $escape($seoDescription) ?>">
    <meta name="robots" content="<?= $escape($seoRobots) ?>">
    <meta name="googlebot" content="<?= $escape($seoRobots) ?>">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="application-name" content="<?= $escape((string) ($seo['siteName'] ?? 'Borz33')) ?>">
    <meta name="apple-mobile-web-app-title" content="<?= $escape((string) ($seo['siteName'] ?? 'Borz33')) ?>">
    <meta name="format-detection" content="telephone=no">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff">
    <link rel="canonical" href="<?= $escape($seoCanonical) ?>">
    <?php if (is_string($seo['previous'] ?? null)): ?><link rel="prev" href="<?= $escape((string) $seo['previous']) ?>"><?php endif; ?>
    <?php if (is_string($seo['next'] ?? null)): ?><link rel="next" href="<?= $escape((string) $seo['next']) ?>"><?php endif; ?>
    <link rel="alternate" hreflang="bg-BG" href="<?= $escape($seoCanonical) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= $escape($seoCanonical) ?>">

    <meta property="og:locale" content="bg_BG">
    <meta property="og:type" content="<?= $escape((string) ($seo['type'] ?? 'website')) ?>">
    <meta property="og:site_name" content="<?= $escape((string) ($seo['siteName'] ?? 'Borz33')) ?>">
    <meta property="og:title" content="<?= $escape($seoTitle) ?>">
    <meta property="og:description" content="<?= $escape($seoDescription) ?>">
    <meta property="og:url" content="<?= $escape($seoCanonical) ?>">
    <?php if ($seoImage !== null): ?>
    <meta property="og:image" content="<?= $escape($seoImage) ?>">
    <meta property="og:image:alt" content="<?= $escape($seoImageAlt) ?>">
    <?php endif; ?>
    <?php if (($seo['type'] ?? '') === 'product'): ?>
    <meta property="product:price:amount" content="<?= $escape((string) ($seo['productPrice'] ?? '')) ?>">
    <meta property="product:price:currency" content="<?= $escape((string) ($seo['productCurrency'] ?? 'EUR')) ?>">
    <meta property="product:availability" content="<?= $escape((string) ($seo['productAvailability'] ?? 'in stock')) ?>">
    <?php endif; ?>

    <meta name="twitter:card" content="<?= $escape((string) ($seo['twitterCard'] ?? 'summary')) ?>">
    <meta name="twitter:title" content="<?= $escape($seoTitle) ?>">
    <meta name="twitter:description" content="<?= $escape($seoDescription) ?>">
    <?php if ($seoImage !== null): ?>
    <meta name="twitter:image" content="<?= $escape($seoImage) ?>">
    <meta name="twitter:image:alt" content="<?= $escape($seoImageAlt) ?>">
    <?php endif; ?>
    <title><?= $escape($seoTitle) ?></title>
    <?php foreach (($seo['jsonLd'] ?? []) as $schema): ?>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?></script>
    <?php endforeach; ?>
    <script>
        (function () {
            var server = <?= json_encode($accountTheme, JSON_UNESCAPED_UNICODE) ?>;
            var pref = (server === 'light' || server === 'dark' || server === 'system') ? server : 'system';
            var dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            var theme = dark ? 'dark' : 'light';
            document.documentElement.dataset.theme = theme;
            document.documentElement.dataset.themePreference = pref;
            document.documentElement.style.colorScheme = theme;
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) {
                meta.setAttribute('content', dark ? '#0a0a0a' : '#ffffff');
            }
        })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
        html, body { margin: 0; min-height: 100%; background: #ffffff; color: #0a0a0a; }
        html[data-theme="dark"], html[data-theme="dark"] body { background: #0a0a0a; color: #fafafa; }
    </style>
    <?php if ($viteOrigin !== null): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($viteOrigin, ENT_QUOTES, 'UTF-8') ?>/src/app.css?direct">
    <?php else: ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(store_asset('/build/app.css'), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <script>window.STORE_THEME = <?= json_encode($accountTheme, JSON_UNESCAPED_UNICODE) ?>;</script>
    <?php if ($viteOrigin !== null): ?>
    <script type="module" src="<?= htmlspecialchars($viteOrigin, ENT_QUOTES, 'UTF-8') ?>/@vite/client"></script>
    <script type="module" src="<?= htmlspecialchars($viteOrigin, ENT_QUOTES, 'UTF-8') ?>/src/app.js"></script>
    <?php else: ?>
    <script type="module" src="<?= htmlspecialchars(store_asset('/build/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</head>
<body class="min-h-full bg-canvas text-ink antialiased">
    <a class="absolute left-3 top-[-40px] z-20 bg-canvas px-3 py-2 text-ink focus:top-3" href="#content">Към съдържанието</a>
    <div
        class="flex min-h-screen flex-col"
        x-data='storeHeader(<?= json_encode((string) (\App\Core\Request::query('q') ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'
        @keydown.escape.window="closeAll()"
    >
        <?php if ($freeShippingNotice !== null): ?>
            <aside class="store-free-shipping-notice<?= $freeShippingUnlocked ? ' is-unlocked' : '' ?>" data-free-shipping-notice data-threshold="<?= $escape(number_format($freeShippingNotice['threshold'], 2, '.', '')) ?>" data-subtotal="<?= $escape(number_format($freeShippingNotice['subtotal'], 2, '.', '')) ?>" aria-live="polite">
                <a href="<?= $freeShippingUnlocked ? '/cart' : '/catalog' ?>">
                    <span aria-hidden="true">✦</span>
                    <strong data-free-shipping-message><?= $escape($freeShippingMessage) ?></strong>
                    <span class="store-free-shipping-notice-link" data-free-shipping-action><?= $freeShippingUnlocked ? 'Към количката' : 'Разгледайте продуктите' ?></span>
                </a>
            </aside>
        <?php endif; ?>
        <?php require dirname(__DIR__) . '/views/partials/header.php'; ?>
        <main id="content" class="mx-auto <?= str_starts_with($currentPath, '/account') ? 'w-[min(1440px,calc(100%-2rem))]' : 'w-[min(1120px,calc(100%-2rem))]' ?> flex-1 <?= $compactMainBottom ? 'pb-3' : 'pb-14' ?> <?= $flushMainTop ? 'pt-0' : ($currentPath === '/' ? 'pt-3' : 'pt-7') ?>">
            <?= $content ?>
        </main>
        <?php require dirname(__DIR__) . '/views/partials/footer.php'; ?>
    </div>
</body>
</html>
