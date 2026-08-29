<?php

declare(strict_types=1);

use App\Models\Product;
use App\Resources\ProductImageResource;
use Store\Core\Html;
use Store\Services\ProductPage;

/** @var Product $product */
/** @var list<array{label: string, href: string|null}> $crumbs */
/** @var array<string, mixed> $config */
/** @var list<Product> $related */
/** @var string|null $message */
/** @var bool $isError */
/** @var string $csrf */

$product = $product ?? null;
$crumbs = $crumbs ?? [];
$config = $config ?? [];
$related = $related ?? [];
$message = $message ?? null;
$isError = $isError ?? false;
$csrf = $csrf ?? '';
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
    'description' => $product->short_description ?: $product->description,
    'sku' => $product->sku,
    'image' => $front !== null ? ProductImageResource::toArray($front)['url'] : null,
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'BGN',
        'price' => $product->price,
        'availability' => 'https://schema.org/InStock',
        'url' => '/products/' . $product->slug,
    ],
];
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<article class="store-pdp" x-data='storeProduct(<?= $alpine ?>)' @keydown.escape.window="closeLightbox()" @keydown.arrow-left.window="lightbox && stepImage(-1)" @keydown.arrow-right.window="lightbox && stepImage(1)">
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
                    <button type="button" class="store-pdp-photo-btn" @click="openLightbox()" aria-label="Отвори снимката">
                        <img class="store-pdp-photo" :src="image.url" :alt="image.alt" width="900" height="1125">
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
                <p class="store-pdp-lead"><?= htmlspecialchars($product->short_description, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <p class="store-pdp-price">
                <span class="store-pdp-compare" x-cloak x-show="onSale" x-text="format(compare)"></span>
                <span class="store-pdp-amount" x-text="price ? format(price) : ''"><?= htmlspecialchars(ProductPage::money($product->price), ENT_QUOTES, 'UTF-8') ?></span>
            </p>
            <p class="store-pdp-meta">
                <span x-show="sku">Код <span x-text="sku"></span></span>
                <span class="store-pdp-stock" :class="!inStock && 'is-out'" x-text="status()"></span>
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
                    ?>
                    <div class="store-pdp-field">
                        <label for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!$required): ?>
                                <span class="text-muted"> (по желание)</span>
                            <?php endif; ?>
                        </label>
                        <?php if (trim((string) ($field['description'] ?? '')) !== ''): ?>
                            <p class="store-pdp-field-hint"><?= htmlspecialchars((string) $field['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if (($field['type'] ?? '') === 'textarea'): ?>
                            <textarea
                                class="store-input"
                                id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                                name="personalization[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>]"
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
                                <?= $max !== null ? 'maxlength="' . (int) $max . '"' : '' ?>
                                <?= $required ? 'required' : '' ?>
                                x-model="fields[<?= (int) $index ?>].value"
                            >
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

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

                <p class="store-pdp-error" x-cloak x-show="error" x-text="error"></p>

                <button type="submit" class="store-submit store-pdp-add" :disabled="!canBuy">
                    Добави в количката
                </button>
            </form>

            <p class="store-pdp-ship">Доставка с Econt до офис, автомат или адрес. Срокът се потвърждава при поръчка.</p>
        </div>
    </div>

    <?php if (trim((string) $product->description) !== ''): ?>
        <section class="store-pdp-section">
            <h2>Описание</h2>
            <div class="store-pdp-copy"><?= nl2br(htmlspecialchars($product->description, ENT_QUOTES, 'UTF-8')) ?></div>
        </section>
    <?php endif; ?>

    <?php if ($product->parameters->isNotEmpty()): ?>
        <section class="store-pdp-section">
            <h2>Параметри</h2>
            <dl class="store-pdp-specs">
                <?php foreach ($product->parameters as $parameter): ?>
                    <div>
                        <dt><?= htmlspecialchars($parameter->name, ENT_QUOTES, 'UTF-8') ?></dt>
                        <dd><?= htmlspecialchars($parameter->value, ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    <?php endif; ?>

    <template x-teleport="body">
        <div
            class="store-lightbox"
            x-cloak
            x-show="lightbox"
            x-transition.opacity.duration.180ms
            role="dialog"
            aria-modal="true"
            aria-label="Снимки на продукта"
            @click.self="closeLightbox()"
        >
            <button type="button" class="store-lightbox-close" x-ref="lightboxClose" @click="closeLightbox()" aria-label="Затвори">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
            <p class="store-lightbox-count" x-show="images.length > 1" x-text="(imageIndex + 1) + ' / ' + images.length"></p>
            <button type="button" class="store-lightbox-nav store-lightbox-nav--prev" x-show="images.length > 1" @click="stepImage(-1)" aria-label="Предишна снимка"><?= Html::iconSvg('chevron-right') ?></button>
            <figure class="store-lightbox-stage" @click.stop>
                <template x-if="image">
                    <img :src="image.url" :alt="image.alt" @click="images.length > 1 && stepImage(1)">
                </template>
            </figure>
            <button type="button" class="store-lightbox-nav store-lightbox-nav--next" x-show="images.length > 1" @click="stepImage(1)" aria-label="Следваща снимка"><?= Html::iconSvg('chevron-right') ?></button>
            <div class="store-lightbox-thumbs" x-show="images.length > 1" @click.stop>
                <template x-for="(item, index) in images" :key="'lb-' + item.id + '-' + index">
                    <button
                        type="button"
                        class="store-lightbox-thumb"
                        :class="imageIndex === index && 'is-active'"
                        @click="setImage(index)"
                    >
                        <img :src="item.url" :alt="item.alt" width="72" height="90">
                    </button>
                </template>
            </div>
        </div>
    </template>
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
                <a class="store-pdp-card" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="store-pdp-card-media">
                        <?php if ($thumb !== null): ?>
                            <img src="<?= htmlspecialchars(ProductImageResource::toArray($thumb)['url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>" width="320" height="400">
                        <?php endif; ?>
                    </span>
                    <span class="store-pdp-card-name"><?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="store-pdp-card-price"><?= htmlspecialchars(ProductPage::money($item->price), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
