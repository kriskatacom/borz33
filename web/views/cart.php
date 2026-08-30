<?php

declare(strict_types=1);

use Store\Core\Html;
use Store\Services\ProductPage;

/** @var list<array<string, mixed>> $lines */
/** @var string $total */
/** @var string $totalWeight */
/** @var string|null $message */
/** @var bool $isError */
/** @var string $csrf */
/** @var list<array<string, mixed>> $recentlyViewed */
/** @var list<int> $favoriteIds */
/** @var list<int> $cartProductIds */

$lines = $lines ?? [];
$total = $total ?? ProductPage::money(0);
$totalWeight = $totalWeight ?? 'Не е изчислено';
$message = $message ?? null;
$isError = $isError ?? false;
$csrf = $csrf ?? '';
$recentlyViewed = $recentlyViewed ?? [];
$favoriteIds = $favoriteIds ?? [];
$cartProductIds = $cartProductIds ?? [];
?>
<section class="store-cart">
    <h1 class="store-cart-title">Количка</h1>

    <?php if ($message): ?>
        <p class="store-pdp-flash <?= $isError ? 'is-error' : '' ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($lines === []): ?>
        <div class="store-empty-state store-empty-state--cart">
            <img src="/images/empty-cart.webp" alt="" width="768" height="512">
            <div class="store-empty-state-copy">
                <p class="store-empty-state-eyebrow">Вашата количка</p>
                <h2>Тук все още е празно</h2>
                <p>Разгледайте продуктите и добавете нещо, което ви харесва. Избраните артикули ще ви очакват тук.</p>
                <a class="store-empty-state-action" href="/catalog">Разгледайте каталога</a>
            </div>
        </div>
    <?php else: ?>
        <ul class="store-cart-list">
            <?php foreach ($lines as $line): ?>
                <li class="store-cart-item">
                    <a class="store-cart-thumb" href="<?= htmlspecialchars((string) $line['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (!empty($line['image'])): ?>
                            <img src="<?= htmlspecialchars((string) $line['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $line['alt'], ENT_QUOTES, 'UTF-8') ?>" width="96" height="120">
                        <?php endif; ?>
                    </a>
                    <div class="store-cart-info">
                        <a class="store-cart-name" href="<?= htmlspecialchars((string) $line['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $line['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        <?php if ((string) $line['options'] !== ''): ?>
                            <p class="store-cart-meta"><?= htmlspecialchars((string) $line['options'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ((string) $line['sku'] !== ''): ?>
                            <p class="store-cart-meta">Код <?= htmlspecialchars((string) $line['sku'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <p class="store-cart-weight">Тегло: <strong><?= htmlspecialchars((string) $line['weight'], ENT_QUOTES, 'UTF-8') ?></strong> · за това количество: <strong><?= htmlspecialchars((string) $line['total_weight'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                        <?php foreach ($line['notes'] as $note): ?>
                            <p class="store-cart-meta"><?= htmlspecialchars((string) $note, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="store-cart-actions">
                        <form method="post" action="/cart/<?= (int) $line['index'] ?>" class="store-pdp-qty">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" name="qty" value="<?= max(0, (int) $line['qty'] - 1) ?>" aria-label="Намали">−</button>
                            <span><?= (int) $line['qty'] ?></span>
                            <button type="submit" name="qty" value="<?= min(99, (int) $line['qty'] + 1) ?>" aria-label="Увеличи">+</button>
                        </form>
                        <p class="store-cart-price"><?= htmlspecialchars(ProductPage::money($line['total']), ENT_QUOTES, 'UTF-8') ?></p>
                        <form method="post" action="/cart/<?= (int) $line['index'] ?>/delete">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="store-cart-remove">Премахни</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="store-cart-checkout"><div class="store-cart-summary"><p>Общо тегло <strong><?= htmlspecialchars($totalWeight, ENT_QUOTES, 'UTF-8') ?></strong></p><p class="store-cart-total">Общо <?= htmlspecialchars($total, ENT_QUOTES, 'UTF-8') ?></p></div><a href="/checkout">Към детайли за поръчката</a></div>
    <?php endif; ?>
</section>

<?php if ($recentlyViewed !== []): ?>
    <section class="store-recently-viewed" aria-labelledby="store-recently-viewed-title">
        <header class="store-recently-viewed-head">
            <p>Вашата история</p>
            <h2 id="store-recently-viewed-title">Разглеждани преди</h2>
        </header>
        <div class="store-recently-viewed-grid">
            <?php foreach ($recentlyViewed as $product): ?>
                <?php $favorite = in_array((int) $product['id'], $favoriteIds, true); $inCart = in_array((int) $product['id'], $cartProductIds, true); ?>
                <article class="store-favorite-card" data-card-product="<?= (int) $product['id'] ?>">
                    <a class="store-favorite-card-media" href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($product['image'] !== null): ?><img src="<?= htmlspecialchars((string) $product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['alt'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="400" loading="lazy"><?php endif; ?>
                    </a>
                    <?php if ($product['discountPercent'] !== null): ?><span class="store-card-sale-badge">−<?= (int) $product['discountPercent'] ?>%</span><?php endif; ?>
                    <button type="button" class="store-card-quick-overlay" data-quick-view="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Бърз преглед"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    <div class="store-favorite-card-copy">
                        <a href="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        <div class="store-card-price"><strong><?= htmlspecialchars((string) $product['price'], ENT_QUOTES, 'UTF-8') ?></strong><?php if ($product['comparePrice'] !== null): ?><del><?= htmlspecialchars((string) $product['comparePrice'], ENT_QUOTES, 'UTF-8') ?></del><?php endif; ?></div>
                        <span class="store-card-weight">Тегло <strong><?= htmlspecialchars((string) $product['weight'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <div class="store-product-card-actions"><button type="button" class="store-card-cart-button <?= $inCart ? 'is-in-cart' : '' ?>" data-card-cart="<?= htmlspecialchars((string) $product['href'], ENT_QUOTES, 'UTF-8') ?>" data-product-id="<?= (int) $product['id'] ?>" data-in-cart="<?= $inCart ? 'true' : 'false' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 3h1.5l2.1 10.5h11.9l2-7.5H5.1M8.25 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm10.5 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg><span data-cart-label><?= $inCart ? 'В количката' : 'Добави' ?></span></button></div>
                    </div>
                    <button type="button" class="store-favorite-card-button" data-favorite-product="<?= (int) $product['id'] ?>" data-favorite="<?= $favorite ? 'true' : 'false' ?>" aria-label="<?= $favorite ? 'Премахни от любими' : 'Добави в любими' ?>" aria-pressed="<?= $favorite ? 'true' : 'false' ?>"><?= Html::iconSvg('heart') ?></button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
