<?php

declare(strict_types=1);

use Store\Core\Html;

/** @var list<array<string, mixed>> $products */
$products = $products ?? [];
?>
<section class="store-favorites-page">
    <header class="store-favorites-head">
        <h1>Любими продукти</h1>
        <p>Запазените продукти са на едно място.</p>
    </header>

    <div class="store-empty-state store-empty-state--compact" data-favorites-empty <?= $products !== [] ? 'hidden' : '' ?>>
        <span class="store-empty-state-icon" aria-hidden="true"><?= Html::iconSvg('heart') ?></span>
        <div class="store-empty-state-copy">
            <h2>Запазете продуктите, които харесвате</h2>
            <p>Натиснете сърцето върху продукт и той ще се появи тук.</p>
            <a class="store-empty-state-action" href="/catalog">Разгледайте каталога</a>
        </div>
    </div>

    <div class="store-favorites-grid" data-favorites-grid>
        <?php foreach ($products as $product): ?>
            <article class="store-favorite-card" data-favorite-card="<?= (int) $product['id'] ?>">
                <a class="store-favorite-card-media" href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($product['image'] !== null): ?>
                        <img src="<?= htmlspecialchars((string) $product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['alt'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="400">
                    <?php endif; ?>
                </a>
                <div class="store-favorite-card-copy">
                    <a href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                    <strong><?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <button type="button" class="store-favorite-card-button" data-favorite-product="<?= (int) $product['id'] ?>" data-favorite="true" aria-label="Премахни от любими" aria-pressed="true"><?= Html::iconSvg('heart') ?></button>
            </article>
        <?php endforeach; ?>
    </div>
</section>
