<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
/** @var string $currentPath */

$title = $title ?? 'Borz33';
$currentPath = $currentPath ?? '/';
/** @var \App\Models\User|null $currentUser */
$currentUser = $currentUser ?? null;
$accountTheme = null;
$viteOrigin = \Store\Core\Vite::origin();

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
    <meta name="theme-color" content="#ffffff">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
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
        <?php require dirname(__DIR__) . '/views/partials/header.php'; ?>
        <main id="content" class="mx-auto w-[min(1120px,calc(100%-2rem))] flex-1 pb-14 <?= $currentPath === '/' ? 'pt-3' : 'pt-7' ?>">
            <?= $content ?>
        </main>
        <footer class="border-t border-line text-sm text-muted">
            <div class="mx-auto w-[min(1120px,calc(100%-2rem))] py-5 pb-7">
                <p class="m-0">Borz33</p>
            </div>
        </footer>
    </div>
</body>
</html>
