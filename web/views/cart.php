<?php

declare(strict_types=1);

use Store\Services\ProductPage;

/** @var list<array<string, mixed>> $lines */
/** @var string $total */
/** @var string|null $message */
/** @var bool $isError */
/** @var string $csrf */

$lines = $lines ?? [];
$total = $total ?? ProductPage::money(0);
$message = $message ?? null;
$isError = $isError ?? false;
$csrf = $csrf ?? '';
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
        <div class="store-cart-checkout"><p class="store-cart-total">Общо <?= htmlspecialchars($total, ENT_QUOTES, 'UTF-8') ?></p><a href="/checkout">Към детайли за поръчката</a></div>
    <?php endif; ?>
</section>
