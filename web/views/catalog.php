<?php

declare(strict_types=1);

use Store\Core\Banners;

/** @var string $query */
/** @var \App\Models\Category|null $category */

$query = $query ?? '';
$category = $category ?? null;

Banners::render($category !== null ? 'catalog-' . $category->slug : 'catalog');
?>
<?php if ($query !== ''): ?>
    <p class="m-0 text-2xl leading-snug font-semibold">Няма резултати за „<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>“.</p>
    <p class="mt-3 text-muted">Каталогът още се подготвя.</p>
<?php elseif ($category !== null): ?>
    <p class="m-0 text-2xl leading-snug font-semibold"><?= htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') ?></p>
    <p class="mt-3 text-muted">Продуктите в тази категория ще се появят тук.</p>
<?php else: ?>
    <p class="m-0 text-2xl leading-snug font-semibold">Каталог</p>
    <p class="mt-3 text-muted">Продуктите ще се появят тук.</p>
<?php endif; ?>
