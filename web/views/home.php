<?php

declare(strict_types=1);

use Store\Core\Banners;
use Store\Core\Html;

/** @var list<array<string, mixed>> $newProducts */
/** @var list<array<string, mixed>> $bestSellers */
/** @var list<int> $favoriteIds */
$newProducts = $newProducts ?? [];
$favoriteIds = $favoriteIds ?? [];
$bestSellers = $bestSellers ?? [];
?>
<?php Banners::render('home-page-banner'); ?>

<div class="store-hero">
    <?php Banners::render('proletna-promociya'); ?>
</div>

<?php if ($bestSellers !== []): ?>
    <section class="store-home-products" aria-labelledby="store-best-sellers-title">
        <header class="store-home-products-head">
            <div>
                <p class="store-home-products-eyebrow">Избрано от клиентите</p>
                <h2 id="store-best-sellers-title">Най-продавани продукти</h2>
            </div>
            <a href="/catalog">Вижте всички</a>
        </header>
        <div class="store-home-products-grid">
            <?php foreach ($bestSellers as $product): ?>
                <?php $favorite = in_array((int) $product['id'], $favoriteIds, true); ?>
                <article class="store-favorite-card">
                    <a class="store-favorite-card-media" href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($product['image'] !== null): ?><img src="<?= htmlspecialchars((string) $product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['alt'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="400" loading="lazy"><?php endif; ?>
                    </a>
                    <div class="store-favorite-card-copy">
                        <a href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        <strong><?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="store-card-weight">Продадени <strong><?= (int) $product['soldQuantity'] ?> бр.</strong></span>
                    </div>
                    <button type="button" class="store-favorite-card-button" data-favorite-product="<?= (int) $product['id'] ?>" data-favorite="<?= $favorite ? 'true' : 'false' ?>" aria-label="<?= $favorite ? 'Премахни от любими' : 'Добави в любими' ?>" aria-pressed="<?= $favorite ? 'true' : 'false' ?>"><?= Html::iconSvg('heart') ?></button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($newProducts !== []): ?>
    <section class="store-home-products" aria-labelledby="store-new-products-title">
        <header class="store-home-products-head">
            <div>
                <p class="store-home-products-eyebrow">Ново в Borz33</p>
                <h2 id="store-new-products-title">Най-нови продукти</h2>
            </div>
            <a href="/catalog">Вижте всички</a>
        </header>

        <div class="store-home-products-grid">
            <?php foreach ($newProducts as $product): ?>
                <?php $favorite = in_array((int) $product['id'], $favoriteIds, true); ?>
                <article class="store-favorite-card">
                    <a class="store-favorite-card-media" href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($product['image'] !== null): ?>
                            <img src="<?= htmlspecialchars((string) $product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['alt'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="400" loading="lazy">
                        <?php endif; ?>
                    </a>
                    <div class="store-favorite-card-copy">
                        <a href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        <strong><?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="store-card-weight">Тегло <strong><?= htmlspecialchars((string) $product['weight'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                    </div>
                    <button
                        type="button"
                        class="store-favorite-card-button"
                        data-favorite-product="<?= (int) $product['id'] ?>"
                        data-favorite="<?= $favorite ? 'true' : 'false' ?>"
                        aria-label="<?= $favorite ? 'Премахни от любими' : 'Добави в любими' ?>"
                        aria-pressed="<?= $favorite ? 'true' : 'false' ?>"
                    ><?= Html::iconSvg('heart') ?></button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
