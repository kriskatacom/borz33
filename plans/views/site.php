<?php

declare(strict_types=1);

const SITE_NAME = 'Проект Онлайн Магазин';

function page_url(string $page): string
{
    return $page === 'home' ? '/index.php' : '/' . $page . '.php';
}

function nav_items(): array
{
    return [
        'home' => 'Преглед',
        'store' => 'Страници',
        'checkout' => 'Поръчка',
        'improvements' => 'Предложения',
        'management' => 'Управление',
        'roadmap' => 'Етапи',
        'preparation' => 'Подготовка',
    ];
}

function render_header(string $active, string $title, string $description): void
{
    $items = nav_items();
    ?>
<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="theme-color" content="#173f32">
    <title><?= htmlspecialchars($title) ?> · <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
    <?php if (filter_var(getenv('DEV_RELOAD_ENABLED') ?: false, FILTER_VALIDATE_BOOL)): ?>
    <script>
        new EventSource('/__dev/reload').addEventListener('reload', function () { window.location.reload(); });
    </script>
    <?php endif; ?>
</head>
<body class="min-h-screen antialiased">
<div class="bg-forest px-4 py-2 text-center text-xs font-bold tracking-wide text-sage">
    Представяне на плана за изработка · това не е самият онлайн магазин
</div>
<header class="relative z-50 border-b border-forest/10 bg-cream/90 backdrop-blur-xl">
    <div class="page-shell flex min-h-20 items-center justify-between gap-5">
        <a href="/index.php" class="group flex items-center gap-3" aria-label="Към началната страница">
            <span class="flex size-10 items-center justify-center rounded-full bg-forest text-lg font-extrabold text-white transition group-hover:rotate-6">M</span>
            <span class="leading-tight">
                <span class="block text-xs font-extrabold uppercase tracking-[0.17em] text-moss">Вашият нов</span>
                <span class="block font-display text-lg font-bold text-ink">онлайн магазин</span>
            </span>
        </a>
        <button class="flex size-11 items-center justify-center rounded-full border border-forest/15 lg:hidden" type="button" data-menu-button aria-expanded="false" aria-controls="mobile-menu" aria-label="Отвори менюто">
            <span class="text-xl" aria-hidden="true">≡</span>
        </button>
        <nav class="hidden items-center gap-0.5 lg:flex" aria-label="Основна навигация">
            <?php foreach ($items as $key => $label): ?>
                <a href="<?= page_url($key) ?>" class="rounded-full px-3 py-2 text-xs font-bold transition xl:text-sm <?= $active === $key ? 'bg-forest text-white' : 'text-ink/65 hover:bg-white hover:text-ink' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <nav id="mobile-menu" class="page-shell hidden border-t border-forest/10 py-4 lg:hidden" data-mobile-menu aria-label="Мобилна навигация">
        <div class="grid gap-2">
            <?php foreach ($items as $key => $label): ?>
                <a href="<?= page_url($key) ?>" class="rounded-2xl px-4 py-3 text-sm font-bold <?= $active === $key ? 'bg-forest text-white' : 'bg-white/60 text-ink' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
<main>
    <?php
}

function render_footer(): void
{
    ?>
</main>
<footer class="border-t border-forest/10 bg-forest text-white">
    <div class="page-shell grid gap-10 py-12 md:grid-cols-[1.2fr_0.8fr] md:items-end">
        <div>
            <p class="mb-3 text-xs font-extrabold uppercase tracking-[0.2em] text-sage">Следващата стъпка</p>
            <p class="max-w-2xl font-display text-3xl leading-tight">Заедно уточняваме приоритетите и превръщаме този план във Вашия магазин.</p>
        </div>
        <div class="md:text-right">
            <a class="inline-flex rounded-full bg-sun px-6 py-3 text-sm font-extrabold text-ink transition hover:-translate-y-0.5" href="/roadmap.php#start">Вижте как започваме</a>
            <p class="mt-5 text-sm text-white/55">План за преглед и одобрение преди изработката</p>
        </div>
    </div>
</footer>
</body>
</html>
    <?php
}

function feature_icon(string $symbol, string $tone = 'bg-sage'): string
{
    return '<span class="flex size-12 items-center justify-center rounded-2xl ' . $tone . ' text-xl font-extrabold text-forest" aria-hidden="true">' . htmlspecialchars($symbol) . '</span>';
}
