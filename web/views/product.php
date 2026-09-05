<?php

declare(strict_types=1);

use App\Models\Product;
use App\Resources\ProductImageResource;
use Store\Core\Banners;
use Store\Core\Html;
use Store\Services\ProductPage;

/** @var Product $product */
/** @var list<array{label: string, href: string|null}> $crumbs */
/** @var array<string, mixed> $config */
/** @var list<Product> $related */
/** @var string|null $message */
/** @var bool $isError */
/** @var string $csrf */
/** @var list<int> $favoriteIds */
/** @var \Illuminate\Support\Collection<int, \App\Models\ProductReview> $reviews */
/** @var array{can_review: bool, reason: string} $reviewEligibility */
/** @var \App\Models\ProductReview|null $viewerReview */

$product = $product ?? null;
$crumbs = $crumbs ?? [];
$config = $config ?? [];
$related = $related ?? [];
$message = $message ?? null;
$isError = $isError ?? false;
$csrf = $csrf ?? '';
$favoriteIds = $favoriteIds ?? [];
$reviews = $reviews ?? new \Illuminate\Support\Collection();
$reviewEligibility = $reviewEligibility ?? ['can_review' => false, 'reason' => 'login'];
$viewerReview = $viewerReview ?? null;
$viewerId = (int) (\App\Core\Auth::user()?->id ?? 0);
$reviewCount = $reviews->count();
$averageRating = $reviewCount > 0 ? round((float) $reviews->avg('rating'), 1) : null;
$reviewCreateAction = '/products/' . $product->slug . '/reviews';
$alpine = (string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
$defaultVariantId = ProductPage::defaultVariantId($config);
$options = is_array($config['options'] ?? null) ? $config['options'] : [];
$fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
$selected = is_array($config['selected'] ?? null) ? $config['selected'] : [];
$front = $product->frontImage;
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'description' => strip_tags((string) ($product->short_description ?: $product->description)),
    'sku' => $product->sku,
    'image' => $front !== null ? ProductImageResource::toArray($front)['url'] : null,
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'EUR',
        'price' => $product->price,
        'availability' => 'https://schema.org/InStock',
        'url' => '/products/' . $product->slug,
    ],
];
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<article class="store-pdp" x-data='storeProduct(<?= $alpine ?>)'>
    <nav class="store-pdp-crumbs" aria-label="Път">
        <?php foreach ($crumbs as $index => $crumb): ?>
            <?php if ($index > 0): ?>
                <span class="store-pdp-crumbs-sep" aria-hidden="true"><?= Html::iconSvg('chevron-right') ?></span>
            <?php endif; ?>
            <?php if ($crumb['href'] !== null): ?>
                <a href="<?= htmlspecialchars($crumb['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php else: ?>
                <span aria-current="page"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <?php if ($message): ?>
        <p class="store-pdp-flash <?= $isError ? 'is-error' : '' ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="store-pdp-grid">
        <div class="store-pdp-media">
            <div class="store-pdp-stage">
                <template x-if="image">
                    <button type="button" class="store-pdp-photo-btn" @click="openLightbox()" @mouseenter="startZoom($event)" @mousemove="moveZoom($event)" @mouseleave="stopZoom()" aria-label="Отвори снимката">
                        <img class="store-pdp-photo" :src="image.url" :alt="image.alt" width="900" height="1125">
                        <span class="store-pdp-magnifier" :class="zoomActive && 'is-active'" :style="zoomStyle()" aria-hidden="true"></span>
                    </button>
                </template>
                <div class="store-pdp-empty" x-cloak x-show="!image">Няма снимка</div>
                <button type="button" class="store-pdp-nav store-pdp-nav--prev" x-show="images.length > 1" @click="stepImage(-1)" aria-label="Предишна снимка"><?= Html::iconSvg('chevron-right') ?></button>
                <button type="button" class="store-pdp-nav store-pdp-nav--next" x-show="images.length > 1" @click="stepImage(1)" aria-label="Следваща снимка"><?= Html::iconSvg('chevron-right') ?></button>
            </div>
            <div class="store-pdp-thumbs" x-show="images.length > 1">
                <template x-for="(item, index) in images" :key="item.id + '-' + index">
                    <button
                        type="button"
                        class="store-pdp-thumb"
                        :class="imageIndex === index && 'is-active'"
                        @click="setImage(index)"
                    >
                        <img :src="item.url" :alt="item.alt" width="88" height="110">
                    </button>
                </template>
            </div>
        </div>

        <div class="store-pdp-buy">
            <h1 class="store-pdp-title"><?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if ($product->short_description): ?>
                <div class="store-pdp-lead"><?= Banners::expandShortcodes((string) $product->short_description) ?></div>
            <?php endif; ?>

            <p class="store-pdp-price">
                <span class="store-pdp-compare" x-cloak x-show="onSale" x-text="format(compare)"></span>
                <span class="store-pdp-amount" x-text="price ? format(price) : ''"><?= htmlspecialchars(ProductPage::money($product->price), ENT_QUOTES, 'UTF-8') ?></span>
            </p>
            <p class="store-product-weight"><span>Тегло</span><strong><?= htmlspecialchars(ProductPage::weight($product->weight_grams), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p class="store-pdp-meta">
                <span x-show="sku">Код <span x-text="sku"></span></span>
                <span class="store-pdp-stock" :class="{ 'is-in': inStock, 'is-out': !inStock }" x-text="status()"></span>
            </p>
            <form
                class="store-pdp-form"
                method="post"
                action="/products/<?= htmlspecialchars($product->slug, ENT_QUOTES, 'UTF-8') ?>/cart"
                @submit="onSubmit"
            >
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="variant_id" value="<?= (int) $defaultVariantId ?>" :value="variant ? variant.id : ''">

                <?php foreach ($options as $option): ?>
                    <?php
                    $optionSlug = (string) ($option['slug'] ?? '');
                    $optionName = (string) ($option['name'] ?? '');
                    $optionJs = (string) json_encode($optionSlug, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
                    $isSwatch = (bool) ($option['swatch'] ?? false);
                    $values = is_array($option['values'] ?? null) ? $option['values'] : [];
                    ?>
                    <fieldset class="store-pdp-option">
                        <legend><?= htmlspecialchars($optionName, ENT_QUOTES, 'UTF-8') ?></legend>
                        <div class="store-pdp-values<?= $isSwatch ? ' is-swatch' : '' ?>">
                            <?php foreach ($values as $value): ?>
                                <?php
                                $valueSlug = (string) ($value['slug'] ?? '');
                                $valueName = (string) ($value['name'] ?? '');
                                $valueJs = (string) json_encode($valueSlug, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
                                $hex = is_string($value['hex'] ?? null) ? (string) $value['hex'] : '';
                                $isSelected = ($selected[$optionSlug] ?? '') === $valueSlug;
                                ?>
                                <label
                                    class="store-pdp-chip<?= $isSwatch ? ' is-swatch' : '' ?><?= $isSelected ? ' is-active' : '' ?>"
                                    :class='{
                                        "is-active": selected[<?= $optionJs ?>] === <?= $valueJs ?>,
                                        "is-swatch": <?= $isSwatch ? 'true' : 'false' ?>,
                                        "is-unavailable": !available(<?= $optionJs ?>, <?= $valueJs ?>)
                                    }'
                                    <?php if ($isSwatch && $hex !== ''): ?>style="--swatch: <?= htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                    title="<?= htmlspecialchars($valueName, ENT_QUOTES, 'UTF-8') ?>"
                                    aria-label="<?= htmlspecialchars($valueName, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <input
                                        type="radio"
                                        class="sr-only"
                                        name="options[<?= htmlspecialchars($optionSlug, ENT_QUOTES, 'UTF-8') ?>]"
                                        value="<?= htmlspecialchars($valueSlug, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $isSelected ? 'checked' : '' ?>
                                        x-model='selected[<?= $optionJs ?>]'
                                        @change='pick(<?= $optionJs ?>, <?= $valueJs ?>)'
                                    >
                                    <?php if (!$isSwatch): ?>
                                        <span><?= htmlspecialchars($valueName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>

                <?php foreach ($fields as $index => $field): ?>
                    <?php
                    $fieldKey = (string) ($field['key'] ?? '');
                    $fieldName = (string) ($field['name'] ?? '');
                    $fieldId = 'pdp-' . $fieldKey;
                    $required = (bool) ($field['required'] ?? false);
                    $max = $field['max'] ?? null;
                    $placeholder = trim((string) ($field['description'] ?? ''));
                    ?>
                    <div class="store-pdp-field">
                        <label for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!$required): ?>
                                <span class="text-muted"> (по желание)</span>
                            <?php endif; ?>
                        </label>
                        <?php if (($field['type'] ?? '') === 'textarea'): ?>
                            <textarea
                                class="store-input"
                                id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                name="personalization[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>]"
                                <?= $placeholder !== '' ? 'placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                                <?= $max !== null ? 'maxlength="' . (int) $max . '"' : '' ?>
                                <?= $required ? 'required' : '' ?>
                                x-model="fields[<?= (int) $index ?>].value"
                                rows="3"
                            ></textarea>
                        <?php else: ?>
                            <input
                                class="store-input h-[42px] w-full border border-line bg-canvas px-3 text-ink"
                                id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                name="personalization[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>]"
                                type="text"
                                <?= $placeholder !== '' ? 'placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                                <?= $max !== null ? 'maxlength="' . (int) $max . '"' : '' ?>
                                <?= $required ? 'required' : '' ?>
                                x-model="fields[<?= (int) $index ?>].value"
                            >
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <p class="store-pdp-error" x-cloak x-show="error" x-text="error"></p>

                <div class="store-pdp-purchase">
                    <div class="store-pdp-purchase-qty">
                        <span class="store-pdp-purchase-label">Количество</span>
                        <div class="store-pdp-qty" role="group" aria-label="Количество">
                            <button type="button" @click="minus()" aria-label="Намали">−</button>
                            <input
                                type="number"
                                name="qty"
                                min="1"
                                max="99"
                                value="1"
                                x-model.number="qty"
                                aria-label="Количество"
                            >
                            <button type="button" @click="plus()" aria-label="Увеличи">+</button>
                        </div>
                    </div>

                    <button type="submit" class="store-submit store-pdp-add" :disabled="!canBuy || submitting">
                        <span x-text="submitting ? 'Добавяне…' : 'Добави в количката'">Добави в количката</span>
                    </button>

                    <button
                        type="button"
                        class="store-pdp-favorite"
                        data-favorite-product="<?= (int) $product->id ?>"
                        data-favorite="<?= in_array((int) $product->id, $favoriteIds, true) ? 'true' : 'false' ?>"
                        aria-label="<?= in_array((int) $product->id, $favoriteIds, true) ? 'Премахни от любими' : 'Добави в любими' ?>"
                        aria-pressed="<?= in_array((int) $product->id, $favoriteIds, true) ? 'true' : 'false' ?>"
                        title="Любими"
                    >
                        <?= Html::iconSvg('heart') ?>
                    </button>
                </div>
            </form>

            <p class="store-pdp-ship">
                Доставка с Еконт до офис, автомат или адрес. Вижте
                <a href="https://www.econt.com/econt-express/common-terms" target="_blank" rel="noopener noreferrer">условията на Еконт</a>
                и
                <a href="https://www.econt.com/services/courier-services" target="_blank" rel="noopener noreferrer">информацията за куриерските услуги и доставката</a>.
                Точната цена и срок се потвърждават при поръчка.
            </p>
        </div>
    </div>

    <section class="store-pdp-section store-product-tabs" x-data="{ activeTab: ['description', 'parameters', 'reviews'].includes(window.location.hash.slice(1)) ? window.location.hash.slice(1) : 'description' }" x-init="$watch('activeTab', value => history.replaceState(null, '', value === 'description' ? window.location.pathname + window.location.search : '#' + value))">
        <div class="store-product-tab-list" role="tablist" aria-label="Информация за продукта">
            <button type="button" id="product-tab-description" role="tab" aria-controls="product-panel-description" :aria-selected="activeTab === 'description'" :class="{ 'is-active': activeTab === 'description' }" @click="activeTab = 'description'">Описание</button>
            <button type="button" id="product-tab-parameters" role="tab" aria-controls="product-panel-parameters" :aria-selected="activeTab === 'parameters'" :class="{ 'is-active': activeTab === 'parameters' }" @click="activeTab = 'parameters'">Параметри</button>
            <button type="button" id="product-tab-reviews" role="tab" aria-controls="reviews" :aria-selected="activeTab === 'reviews'" :class="{ 'is-active': activeTab === 'reviews' }" @click="activeTab = 'reviews'">Отзиви <span><?= $reviewCount ?></span></button>
        </div>

        <div class="store-product-tab-panels">
            <section id="product-panel-description" class="store-product-tab-panel" role="tabpanel" aria-labelledby="product-tab-description" x-cloak x-show="activeTab === 'description'">
                <h2>Описание</h2>
                <?php if (trim((string) $product->description) !== ''): ?>
                    <div class="store-pdp-copy store-pdp-copy--rich"><?= ProductPage::richText((string) $product->description) ?></div>
                <?php else: ?>
                    <p class="store-product-tab-empty">Все още няма добавено подробно описание за този продукт.</p>
                <?php endif; ?>
            </section>

            <section id="product-panel-parameters" class="store-product-tab-panel" role="tabpanel" aria-labelledby="product-tab-parameters" x-cloak x-show="activeTab === 'parameters'">
                <h2>Параметри</h2>
                <?php if ($product->parameters->isNotEmpty()): ?>
                    <table class="store-pdp-specs-table">
                        <tbody>
                        <?php foreach ($product->parameters as $parameter): ?>
                            <tr>
                                <th scope="row"><?= htmlspecialchars($parameter->name, ENT_QUOTES, 'UTF-8') ?></th>
                                <td><?= htmlspecialchars($parameter->value, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="store-product-tab-empty">Все още няма добавени параметри за този продукт.</p>
                <?php endif; ?>
            </section>

            <section id="reviews" class="store-product-tab-panel store-pdp-reviews" role="tabpanel" aria-labelledby="product-tab-reviews" x-cloak x-show="activeTab === 'reviews'" data-product-reviews data-review-create-url="<?= htmlspecialchars($reviewCreateAction, ENT_QUOTES, 'UTF-8') ?>">
                <header class="store-pdp-reviews-head">
                    <div class="store-reviews-intro">
                        <p>Мнения от клиенти</p>
                        <h2>Отзиви <span data-review-count><?= $reviewCount ?></span></h2>
                        <small>Истински впечатления от хора, които вече избраха този продукт.</small>
                    </div>
                    <div class="store-reviews-summary">
                        <span class="store-review-stars" data-review-average<?= $averageRating === null ? ' hidden' : '' ?> aria-label="<?= $averageRating === null ? '' : 'Средна оценка ' . number_format($averageRating, 1, ',', '') . ' от 5' ?>"><i aria-hidden="true"><?= $averageRating === null ? '' : str_repeat('★', (int) round($averageRating)) . str_repeat('☆', 5 - (int) round($averageRating)) ?></i><strong><?= $averageRating === null ? '' : number_format($averageRating, 1, ',', '') ?></strong></span>
                        <small>Отзиви могат да оставят само клиенти с доставена или платена поръчка.</small>
                    </div>
                </header>

                <?php if ($reviewEligibility['can_review']): ?>
                    <button type="button" class="store-review-create" data-review-create><?= $viewerReview !== null ? 'Напишете нов отзив' : 'Напишете отзив' ?></button>
                <?php elseif ($reviewEligibility['reason'] === 'login'): ?>
                    <p class="store-review-notice">Имате закупен продукта? <a href="/login?return=<?= rawurlencode('/products/' . $product->slug . '#reviews') ?>">Влезте в профила си</a>, за да оставите отзив.</p>
                <?php else: ?>
                    <p class="store-review-notice">След доставена или платена поръчка ще можете да оставите отзив за продукта.</p>
                <?php endif; ?>

                <?php if ($viewerReview !== null || $reviewEligibility['can_review']): ?>
                    <dialog class="store-review-composer" data-review-composer aria-labelledby="product-review-dialog-title">
                        <form class="store-review-form" method="post" action="<?= htmlspecialchars($reviewCreateAction, ENT_QUOTES, 'UTF-8') ?>" data-review-form data-review-create-url="<?= htmlspecialchars($reviewCreateAction, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="store-review-dialog-close" data-review-cancel aria-label="Затвори прозореца">×</button>
                            <div class="store-review-form-head">
                                <strong id="product-review-dialog-title" data-review-form-title>Оставете отзив</strong>
                                <small data-review-form-help>Споделете впечатленията си от продукта.</small>
                            </div>
                            <div class="store-review-rating-picker" data-review-rating-picker>
                                <span>Вашата оценка</span>
                                <input type="hidden" name="rating" value="0" data-review-rating-input>
                                <div role="group" aria-label="Оценка от 1 до 5 звезди">
                                    <?php foreach ([1, 2, 3, 4, 5] as $rating): ?>
                                        <button type="button" data-review-rating-value="<?= $rating ?>" aria-pressed="false" aria-label="<?= $rating ?> <?= $rating === 1 ? 'звезда' : 'звезди' ?>">★</button>
                                    <?php endforeach; ?>
                                </div>
                                <small data-review-rating-label hidden></small>
                            </div>
                            <label for="product-review-body">Споделете впечатлението си</label>
                            <textarea id="product-review-body" name="body" rows="4" minlength="3" maxlength="2000" placeholder="Какво Ви хареса в продукта? (по желание)" data-review-body></textarea>
                            <p class="store-review-form-error" data-review-error hidden role="alert"></p>
                            <footer class="store-review-form-actions">
                                <button type="button" class="store-button-secondary" data-review-cancel>Отказ</button>
                                <button type="submit" class="store-submit store-button" data-review-submit>Публикувай отзив</button>
                            </footer>
                        </form>
                    </dialog>
                <?php endif; ?>

                <p class="store-reviews-empty" data-reviews-empty<?= $reviews->isEmpty() ? '' : ' hidden' ?>>Все още няма отзиви за този продукт.</p>
                <div class="store-reviews-list" data-reviews-list>
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $author = trim((string) ($review->user?->first_name ?? 'Клиент'));
                            $lastName = trim((string) ($review->user?->last_name ?? ''));
                            $author .= $lastName !== '' ? ' ' . $lastName : '';
                            $rating = max(1, min(5, (int) $review->rating));
                            ?>
                            <?php $isOwnedReview = $viewerId > 0 && (int) $review->user_id === $viewerId; ?>
                            <article class="store-review<?= $isOwnedReview ? ' is-owned' : '' ?>" data-review-item="<?= (int) $review->id ?>">
                                <header>
                                    <span><strong><?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></strong><span class="store-review-stars" aria-label="Оценка <?= $rating ?> от 5"><i aria-hidden="true"><?= str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) ?></i></span></span>
                                    <time datetime="<?= htmlspecialchars((string) $review->created_at?->toIso8601String(), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $review->created_at?->timezone('Europe/Sofia')->format('d.m.Y'), ENT_QUOTES, 'UTF-8') ?></time>
                                </header>
                                <p><?= nl2br(htmlspecialchars((string) $review->body, ENT_QUOTES, 'UTF-8')) ?></p>
                                <?php if ($isOwnedReview): ?>
                                    <div class="store-review-actions">
                                        <button type="button" class="store-review-edit" data-review-edit data-review-id="<?= (int) $review->id ?>" data-review-url="<?= htmlspecialchars('/products/' . $product->slug . '/reviews/' . $review->id, ENT_QUOTES, 'UTF-8') ?>" data-review-rating="<?= $rating ?>" data-review-body="<?= htmlspecialchars((string) $review->body, ENT_QUOTES, 'UTF-8') ?>">Редактирай отзива</button>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>

</article>

<?php if ($related !== []): ?>
    <section class="store-pdp-related">
        <h2>Още от тази категория</h2>
        <div class="store-pdp-related-grid">
            <?php foreach ($related as $item): ?>
                <?php
                $thumb = $item->frontImage;
                $url = '/products/' . $item->slug;
                $alt = $thumb !== null && trim((string) $thumb->alt) !== '' ? (string) $thumb->alt : $item->name;
                ?>
                <article class="store-pdp-card-wrap">
                <a class="store-pdp-card" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="store-pdp-card-media">
                        <?php if ($thumb !== null): ?>
                            <img src="<?= htmlspecialchars(ProductImageResource::toArray($thumb)['url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>" width="320" height="400">
                        <?php endif; ?>
                    </span>
                    <span class="store-pdp-card-name"><?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="store-pdp-card-price"><?= htmlspecialchars(ProductPage::money($item->price), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <button type="button" class="store-favorite-card-button" data-favorite-product="<?= (int) $item->id ?>" data-favorite="<?= in_array((int) $item->id, $favoriteIds, true) ? 'true' : 'false' ?>" aria-label="Любим продукт" aria-pressed="<?= in_array((int) $item->id, $favoriteIds, true) ? 'true' : 'false' ?>"><?= Html::iconSvg('heart') ?></button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
