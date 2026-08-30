<?php

declare(strict_types=1);

use Store\Core\Html;

/** @var list<array<string, mixed>> $products */
/** @var list<int> $cartProductIds */
$products = $products ?? [];
$cartProductIds = $cartProductIds ?? [];
?>
<section class="store-favorites-page">
    <header class="store-favorites-head">
        <h1>Любими продукти</h1>
        <p>Запазените продукти са на едно място.</p>
    </header>

    <div class="store-empty-state store-empty-state--compact store-favorites-empty" data-favorites-empty <?= $products !== [] ? 'hidden' : '' ?>>
        <span class="store-empty-state-icon" aria-hidden="true"><?= Html::iconSvg('heart') ?></span>
        <div class="store-empty-state-copy">
            <h2>Запазете продуктите, които харесвате</h2>
            <p>Натиснете сърцето върху продукт и той ще се появи тук.</p>
            <a class="store-empty-state-action" href="/catalog">Разгледайте каталога</a>
        </div>
    </div>

    <div class="store-favorites-grid" data-favorites-grid>
        <?php foreach ($products as $product): ?>
            <?php $inCart = in_array((int) $product['id'], $cartProductIds, true); ?>
            <article class="store-favorite-card" data-favorite-card="<?= (int) $product['id'] ?>" data-card-product="<?= (int) $product['id'] ?>">
                <a class="store-favorite-card-media" href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($product['image'] !== null): ?>
                        <img src="<?= htmlspecialchars((string) $product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['alt'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="400">
                    <?php endif; ?>
                </a>
                <?php if ($product['discountPercent'] !== null): ?><span class="store-card-sale-badge">−<?= (int) $product['discountPercent'] ?>%</span><?php endif; ?>
                <button type="button" class="store-card-quick-overlay" data-quick-view="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Бърз преглед"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                <div class="store-favorite-card-copy">
                    <a href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                    <div class="store-card-price"><strong><?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?></strong><?php if ($product['comparePrice'] !== null): ?><del><?= htmlspecialchars((string) $product['comparePrice'], ENT_QUOTES, 'UTF-8') ?></del><?php endif; ?></div>
                    <span class="store-card-weight">Тегло <strong><?= htmlspecialchars((string) $product['weight'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <div class="store-product-card-actions">
                        <button type="button" class="store-card-cart-button <?= $inCart ? 'is-in-cart' : '' ?>" data-card-cart="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>" data-product-id="<?= (int) $product['id'] ?>" data-in-cart="<?= $inCart ? 'true' : 'false' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 3h1.5l2.1 10.5h11.9l2-7.5H5.1M8.25 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm10.5 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg><span data-cart-label><?= $inCart ? 'В количката' : 'Добави' ?></span></button>
                    </div>
                </div>
                <button type="button" class="store-favorite-card-button" data-favorite-product="<?= (int) $product['id'] ?>" data-favorite="true" aria-label="Премахни от любими" aria-pressed="true"><?= Html::iconSvg('heart') ?></button>
            </article>
        <?php endforeach; ?>
    </div>
</section>
