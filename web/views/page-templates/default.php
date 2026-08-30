<?php

declare(strict_types=1);

use Store\Core\Banners;

/** @var \App\Models\Page $page */
/** @var list<array{label: string, href: string|null}> $breadcrumbs */
$breadcrumbs = $breadcrumbs ?? [];
?>
<article class="store-content-page store-content-page--default">
    <header class="store-content-page-hero">
        <h1 class="store-content-page-title"><?= htmlspecialchars((string) $page->title, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($breadcrumbs !== []): ?>
            <nav class="store-content-breadcrumbs" aria-label="Навигация в страницата">
                <ol>
                    <?php foreach ($breadcrumbs as $index => $item): ?>
                        <li>
                            <?php if ($index > 0): ?><span aria-hidden="true">/</span><?php endif; ?>
                            <?php if (is_string($item['href'])): ?>
                                <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <span aria-current="page"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
    </header>
    <?php if (trim((string) $page->content) !== ''): ?>
        <div class="store-content-page-body"><?= Banners::expandShortcodes((string) $page->content) ?></div>
    <?php endif; ?>
</article>
