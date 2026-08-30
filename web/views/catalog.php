<?php
declare(strict_types=1);
use Store\Core\Banners;
use Store\Core\Html;

$query = $query ?? '';
$category = $category ?? null;
$products = $products ?? [];
$favoriteIds = $favoriteIds ?? [];
$cartProductIds = $cartProductIds ?? [];
$page = max(1, (int) ($page ?? 1));
$lastPage = max(1, (int) ($lastPage ?? 1));
$total = max(0, (int) ($total ?? 0));
$filters = $filters ?? [];
$selectedOptions = is_array($filters['options'] ?? null) ? $filters['options'] : [];
$optionGroups = is_array($filters['optionGroups'] ?? null) ? $filters['optionGroups'] : [];
$basePath = $category !== null ? '/catalog/' . $category->slug : '/catalog';
$params = [];
if ($query !== '') $params['q'] = $query;
if (($filters['minPrice'] ?? null) !== null) $params['min_price'] = $filters['minPrice'];
if (($filters['maxPrice'] ?? null) !== null) $params['max_price'] = $filters['maxPrice'];
if (!empty($filters['inStock'])) $params['availability'] = 'in_stock';
if (!empty($filters['onSale'])) $params['sale'] = '1';
if (($filters['sort'] ?? 'featured') !== 'featured') $params['sort'] = $filters['sort'];
if ($selectedOptions !== []) $params['option'] = $selectedOptions;
$url = static fn (array $values): string => $basePath . ($values ? '?' . http_build_query($values) : '');
$pageUrl = static function (int $target) use ($params, $url): string { $values = $params; if ($target > 1) $values['page'] = $target; return $url($values); };
$removeUrl = static function (string $key, ?string $group = null, ?string $value = null) use ($params, $url): string {
    $values = $params;
    if ($key !== 'option') unset($values[$key]);
    elseif (isset($values['option'][$group])) {
        $values['option'][$group] = array_values(array_filter($values['option'][$group], static fn ($item): bool => $item !== $value));
        if (!$values['option'][$group]) unset($values['option'][$group]);
        if (!$values['option']) unset($values['option']);
    }
    return $url($values);
};
$money = static fn (float $value): string => rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',') . ' €';
$chips = [];
if (($filters['minPrice'] ?? null) !== null) $chips[] = ['От ' . $money((float) $filters['minPrice']), $removeUrl('min_price')];
if (($filters['maxPrice'] ?? null) !== null) $chips[] = ['До ' . $money((float) $filters['maxPrice']), $removeUrl('max_price')];
if (!empty($filters['inStock'])) $chips[] = ['В наличност', $removeUrl('availability')];
if (!empty($filters['onSale'])) $chips[] = ['На промоция', $removeUrl('sale')];
foreach ($optionGroups as $group) foreach ($group['values'] as $value) if (in_array($value['slug'], $selectedOptions[$group['slug']] ?? [], true)) $chips[] = [$group['name'] . ': ' . $value['name'], $removeUrl('option', $group['slug'], $value['slug'])];

$pagination = [];
if ($lastPage <= 7) $pagination = range(1, $lastPage);
else {
    $visible = array_values(array_filter(array_unique([1, 2, $page - 1, $page, $page + 1, $lastPage - 1, $lastPage]), static fn (int $item): bool => $item >= 1 && $item <= $lastPage)); sort($visible); $previous = 0;
    foreach ($visible as $item) { if ($previous && $item - $previous > 1) $pagination[] = null; $pagination[] = $item; $previous = $item; }
}
$resetUrl = $query !== '' ? $url(['q' => $query]) : $basePath;
$columnIcons = [
    1 => ['0 0 448 512', 'M64 32h320c35.3 0 64 28.7 64 64v320c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96c0-35.3 28.7-64 64-64z'],
    2 => ['0 0 448 512', 'M0 96c0-35.3 28.7-64 64-64h320c35.3 0 64 28.7 64 64v320c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zm64 64v256h128V160H64zm320 0H256v256h128V160z'],
    3 => ['0 0 320 512', 'M128 40c0-22.1-17.9-40-40-40H40C17.9 0 0 17.9 0 40v48c0 22.1 17.9 40 40 40h48c22.1 0 40-17.9 40-40V40zm0 192c0-22.1-17.9-40-40-40H40c-22.1 0-40 17.9-40 40v48c0 22.1 17.9 40 40 40h48c22.1 0 40-17.9 40-40v-48zM0 424v48c0 22.1 17.9 40 40 40h48c22.1 0 40-17.9 40-40v-48c0-22.1-17.9-40-40-40H40c-22.1 0-40 17.9-40 40zM320 40c0-22.1-17.9-40-40-40h-48c-22.1 0-40 17.9-40 40v48c0 22.1 17.9 40 40 40h48c22.1 0 40-17.9 40-40V40zM192 232v48c0 22.1 17.9 40 40 40h48c22.1 0 40-17.9 40-40v-48c0-22.1-17.9-40-40-40h-48c-22.1 0-40 17.9-40 40zM320 424c0-22.1-17.9-40-40-40h-48c-22.1 0-40 17.9-40 40v48c0 22.1 17.9 40 40 40h48c22.1 0 40-17.9 40-40v-48z'],
    4 => ['0 0 512 512', 'M88 96c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40H40c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48zm192 128h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40zm192 0h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40zm0 192h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40zM280 288c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40h-48c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48zM88 416H40c-22.1 0-40-17.9-40-40v-48c0-22.1 17.9-40 40-40h48c22.1 0 40 17.9 40 40v48c0 22.1-17.9 40-40 40z'],
];
$columnIcons = [
    1 => ['0 0 24 24', 'M5.25 3.75h13.5v16.5H5.25z'],
    2 => ['0 0 24 24', 'M3.75 4.5h16.5v15H3.75zM12 4.5v15'],
    3 => ['0 0 24 24', 'M3 4.5h18v15H3zM9 4.5v15M15 4.5v15'],
    4 => ['0 0 24 24', 'M3.75 3.75h6.75v6.75H3.75zM13.5 3.75h6.75v6.75H13.5zM3.75 13.5h6.75v6.75H3.75zM13.5 13.5h6.75v6.75H13.5z'],
];
Banners::render($category !== null ? 'catalog-' . $category->slug : 'catalog');
?>
<section class="store-catalog-page">
    <header class="store-catalog-head">
        <h1><?php if ($query !== ''): ?>Резултати за „<?= htmlspecialchars($query) ?>“<?php elseif ($category): ?><?= htmlspecialchars($category->name) ?><?php else: ?>Каталог<?php endif; ?></h1>
        <p>Открийте точния продукт за вас сред внимателно подбраните ни предложения.</p>
    </header>
    <div class="store-catalog-layout">
        <aside class="store-catalog-filters" aria-label="Филтри на каталога">
            <div class="store-filter-panel">
                <div class="store-filter-panel-head"><strong>Филтри</strong><button type="button" data-filter-close aria-label="Затвори филтрите"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
                <form class="store-filter-form" method="get" action="<?= htmlspecialchars($basePath) ?>">
                    <?php if ($query !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>"><?php endif; ?>
                    <fieldset><legend>Цена</legend><div class="store-filter-price">
                        <label><span>От</span><input type="number" name="min_price" min="0" step="0.01" placeholder="<?= htmlspecialchars((string) ($filters['priceBounds']['min'] ?? 0)) ?>" value="<?= ($filters['minPrice'] ?? null) !== null ? htmlspecialchars((string) $filters['minPrice']) : '' ?>"></label>
                        <label><span>До</span><input type="number" name="max_price" min="0" step="0.01" placeholder="<?= htmlspecialchars((string) ($filters['priceBounds']['max'] ?? 0)) ?>" value="<?= ($filters['maxPrice'] ?? null) !== null ? htmlspecialchars((string) $filters['maxPrice']) : '' ?>"></label>
                    </div></fieldset>
                    <fieldset><legend>Показвай</legend>
                        <label class="store-filter-check"><input type="checkbox" name="availability" value="in_stock" <?= !empty($filters['inStock']) ? 'checked' : '' ?>><span>Само в наличност</span></label>
                        <label class="store-filter-check"><input type="checkbox" name="sale" value="1" <?= !empty($filters['onSale']) ? 'checked' : '' ?>><span>Само на промоция</span></label>
                    </fieldset>
                    <?php foreach ($optionGroups as $group): ?><fieldset><legend><?= htmlspecialchars((string) $group['name']) ?></legend>
                        <?php foreach ($group['values'] as $value): ?><label class="store-filter-check"><input type="checkbox" name="option[<?= htmlspecialchars((string) $group['slug']) ?>][]" value="<?= htmlspecialchars((string) $value['slug']) ?>" <?= in_array($value['slug'], $selectedOptions[$group['slug']] ?? [], true) ? 'checked' : '' ?>><?php if (!empty($value['hex'])): ?><i class="store-filter-swatch" style="--swatch:<?= htmlspecialchars((string) $value['hex']) ?>" aria-hidden="true"></i><?php endif; ?><span><?= htmlspecialchars((string) $value['name']) ?></span></label><?php endforeach; ?>
                    </fieldset><?php endforeach; ?>
                    <label class="store-filter-sort"><span>Подреди по</span><select name="sort">
                        <?php foreach (['featured'=>'Препоръчани','newest'=>'Най-нови','price_asc'=>'Цена: ниска към висока','price_desc'=>'Цена: висока към ниска','name'=>'Име'] as $value => $label): ?><option value="<?= $value ?>" <?= ($filters['sort'] ?? 'featured') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select></label>
                    <div class="store-filter-actions"><button type="submit">Приложи</button><a href="<?= htmlspecialchars($resetUrl) ?>">Изчисти</a></div>
                </form>
            </div>
        </aside>
        <button type="button" class="store-filter-backdrop" data-filter-close aria-label="Затвори филтрите"></button>
        <div class="store-catalog-results">
            <div class="store-catalog-toolbar">
                <button type="button" class="store-mobile-filter-trigger" data-filter-open aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/></svg><span>Филтри</span></button>
                <div class="store-grid-picker" role="group" aria-label="Брой колони">
                    <?php foreach ($columnIcons as $columns => [$viewBox, $path]): ?>
                        <button type="button" data-catalog-columns="<?= $columns ?>" aria-label="<?= $columns ?> колони" title="<?= $columns ?> колони">
                            <svg viewBox="<?= $viewBox ?>" aria-hidden="true"><path d="<?= $path ?>"/></svg>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p><?= $total ?> <?= $total === 1 ? 'продукт' : 'продукта' ?></p>
            </div>
            <?php if ($chips): ?><div class="store-active-filters" aria-label="Активни филтри"><?php foreach ($chips as [$label, $href]): ?><a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?><span aria-hidden="true">×</span></a><?php endforeach; ?></div><?php endif; ?>
            <?php if ($products): ?>
                <div class="store-catalog-grid">
                    <?php foreach ($products as $product): $favorite = in_array((int) $product['id'], $favoriteIds, true); $inCart = in_array((int) $product['id'], $cartProductIds, true); ?><article class="store-favorite-card" data-card-product="<?= (int) $product['id'] ?>">
                        <a class="store-favorite-card-media" href="<?= htmlspecialchars((string) $product['href']) ?>"><?php if ($product['image'] !== null): ?><img src="<?= htmlspecialchars((string) $product['image']) ?>" alt="<?= htmlspecialchars((string) $product['alt']) ?>" width="320" height="400" loading="lazy"><?php endif; ?></a>
                        <?php if ($product['discountPercent'] !== null): ?><span class="store-card-sale-badge">−<?= (int) $product['discountPercent'] ?>%</span><?php endif; ?>
                        <button type="button" class="store-card-quick-overlay" data-quick-view="<?= htmlspecialchars((string) $product['href']) ?>" aria-label="Бърз преглед"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        <div class="store-favorite-card-copy"><a href="<?= htmlspecialchars((string) $product['href']) ?>"><?= htmlspecialchars((string) $product['name']) ?></a><?php if ($product['description'] !== ''): ?><p class="store-card-description"><?= htmlspecialchars((string) $product['description']) ?></p><?php endif; ?><div class="store-card-price"><strong><?= htmlspecialchars((string) $product['price']) ?></strong><?php if ($product['comparePrice'] !== null): ?><del><?= htmlspecialchars((string) $product['comparePrice']) ?></del><?php endif; ?></div><div class="store-product-card-actions"><div class="store-card-qty" aria-label="Количество"><button type="button" data-card-qty-step="-1" aria-label="Намали">−</button><input type="number" data-card-qty min="1" max="99" value="1" aria-label="Количество"><button type="button" data-card-qty-step="1" aria-label="Увеличи">+</button></div><button type="button" class="store-card-quick-inline" data-quick-view="<?= htmlspecialchars((string) $product['href']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg><span>Бърз преглед</span></button><button type="button" class="store-card-cart-button <?= $inCart ? 'is-in-cart' : '' ?>" data-card-cart="<?= htmlspecialchars((string) $product['href']) ?>" data-product-id="<?= (int) $product['id'] ?>" data-in-cart="<?= $inCart ? 'true' : 'false' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 3h1.5l2.1 10.5h11.9l2-7.5H5.1M8.25 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm10.5 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg><span data-cart-label><?= $inCart ? 'В количката' : 'Добави' ?></span></button></div></div>
                        <button type="button" class="store-favorite-card-button" data-favorite-product="<?= (int) $product['id'] ?>" data-favorite="<?= $favorite ? 'true' : 'false' ?>" aria-label="<?= $favorite ? 'Премахни от любими' : 'Добави в любими' ?>" aria-pressed="<?= $favorite ? 'true' : 'false' ?>"><?= Html::iconSvg('heart') ?></button>
                    </article><?php endforeach; ?>
                </div>
                <?php if ($lastPage > 1): ?><nav class="store-pagination" aria-label="Страници на каталога">
                    <a class="store-pagination-direction<?= $page === 1 ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(max(1, $page - 1))) ?>" <?= $page === 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Назад</a>
                    <div class="store-pagination-pages"><?php foreach ($pagination as $item): ?><?php if ($item === null): ?><span class="store-pagination-ellipsis">…</span><?php else: ?><a href="<?= htmlspecialchars($pageUrl($item)) ?>" class="<?= $item === $page ? 'is-current' : '' ?>" <?= $item === $page ? 'aria-current="page"' : '' ?>><?= $item ?></a><?php endif; ?><?php endforeach; ?></div>
                    <a class="store-pagination-direction<?= $page === $lastPage ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(min($lastPage, $page + 1))) ?>" <?= $page === $lastPage ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Напред</a>
                </nav><?php endif; ?>
            <?php else: ?><div class="store-empty-state store-empty-state--compact"><span class="store-empty-state-icon" aria-hidden="true"><?= Html::iconSvg('search') ?></span><div class="store-empty-state-copy"><h2>Няма продукти с тези филтри</h2><p>Премахнете част от филтрите или опитайте с друг ценови диапазон.</p><a class="store-empty-state-action" href="<?= htmlspecialchars($resetUrl) ?>">Изчисти филтрите</a></div></div><?php endif; ?>
        </div>
    </div>
</section>
