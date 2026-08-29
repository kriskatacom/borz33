<?php

declare(strict_types=1);

use Store\Core\Html;

/** @var string $currentPath */
/** @var \App\Models\User|null $currentUser */
/** @var string $csrf */
/** @var \Illuminate\Support\Collection<int, \App\Models\Category> $navCategories */
/** @var int $cartCount */

$currentPath = $currentPath ?? '/';
$currentUser = $currentUser ?? null;
$csrf = $csrf ?? '';
$navCategories = $navCategories ?? collect();
$cartCount = (int) ($cartCount ?? 0);
$catalogActive = store_nav_active('/catalog', $currentPath);
$accountLabel = $currentUser !== null ? 'Акаунт' : 'Вход';
$accountActive = $currentPath === '/login' || store_nav_active('/account', $currentPath);
?>
<header class="sticky top-0 z-50 overflow-visible border-b border-line bg-canvas">
    <div class="mx-auto grid w-[min(1120px,calc(100%-2rem))] grid-cols-[1fr_auto] items-center gap-x-3 gap-y-3 py-3 md:grid-cols-[minmax(0,1fr)_minmax(12rem,36rem)_minmax(0,1fr)]">
        <a class="store-logo justify-self-start" href="/" aria-label="Borz33, начало">Borz33</a>

        <div class="flex items-center justify-self-end gap-0.5 md:col-start-3">
            <?php if ($currentUser === null): ?>
                <?php Html::iconLink('/login', 'user', $accountLabel, ['active' => $accountActive]); ?>
            <?php else: ?>
                <div class="relative" @click.outside="accountOpen = false">
                    <?php Html::tooltipStart($accountLabel); ?>
                    <button
                        type="button"
                        class="store-icon-btn <?= $accountActive ? 'is-active' : '' ?>"
                        aria-label="<?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?>"
                        @click="toggleAccount()"
                        :aria-expanded="accountOpen"
                        aria-controls="store-account-menu"
                        aria-haspopup="true"
                    >
                        <?= Html::iconSvg('user') ?>
                    </button>
                    <?php Html::tooltipEnd(); ?>
                    <div
                        id="store-account-menu"
                        class="absolute top-full right-0 z-50 min-w-[12rem] pt-2"
                        x-cloak
                        x-show="accountOpen"
                        x-transition.opacity.duration.120ms
                    >
                        <div class="border border-line bg-canvas py-1 shadow-lg">
                            <p class="px-3 py-2 text-xs text-muted"><?= htmlspecialchars($currentUser->fullName(), ENT_QUOTES, 'UTF-8') ?></p>
                            <a class="block px-3 py-2.5 text-sm font-semibold no-underline hover:bg-ink hover:text-on-accent" href="/account">Табло</a>
                            <a class="block px-3 py-2.5 text-sm font-semibold no-underline hover:bg-ink hover:text-on-accent" href="/account/details">Данни на акаунта</a>
                            <a class="block px-3 py-2.5 text-sm font-semibold no-underline hover:bg-ink hover:text-on-accent" href="/account/password">Парола</a>
                            <form method="post" action="/logout">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="block w-full border-0 bg-transparent px-3 py-2.5 text-left text-sm font-semibold text-ink hover:bg-ink hover:text-on-accent">Изход</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php Html::iconLink('/favorites', 'heart', 'Любими', ['active' => store_nav_active('/favorites', $currentPath), 'badge' => '0']); ?>
            <?php Html::iconLink('/cart', 'cart', 'Количка', ['active' => store_nav_active('/cart', $currentPath), 'badge' => $cartCount > 0 ? (string) $cartCount : null]); ?>
        </div>

        <form
            class="store-search col-span-2 flex h-12 min-w-0 w-full items-center border border-line bg-canvas pr-1 pl-3 md:col-span-1 md:col-start-2 md:row-start-1"
            action="/catalog"
            method="get"
            role="search"
            @click.outside="closeSearch()"
            @focusin="openSearch()"
        >
            <label class="sr-only" for="q">Търсене в каталога</label>
            <input
                id="q"
                name="q"
                type="search"
                class="min-w-0 flex-1 border-0 bg-transparent py-2 text-[0.95rem] text-ink outline-none"
                placeholder="Търсене"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="store-search-results"
                :aria-expanded="searchOpen"
                x-ref="searchInput"
                x-model="searchQuery"
                @focus="openSearch()"
                @click="openSearch()"
                @input.debounce.200ms="onSearchInput()"
            >
            <?php Html::iconButton('search', 'Търси', ['type' => 'submit', 'class' => 'store-icon-btn--sm']); ?>
            <div
                id="store-search-results"
                class="store-search-panel"
                x-cloak
                x-show="searchOpen"
                x-transition.opacity.duration.120ms
                role="listbox"
                aria-label="Продукти"
            >
                <p class="store-search-label" x-show="searchFeatured && searchItems.length > 0">Избрани продукти</p>
                <p class="store-search-label" x-show="!searchFeatured && searchItems.length > 0">Резултати</p>
                <p class="store-search-status" x-show="searchLoading && searchItems.length === 0">Търсене…</p>
                <p class="store-search-empty" x-show="!searchLoading && searchItems.length === 0">Няма намерени продукти</p>
                <template x-for="item in searchItems" :key="item.id">
                    <a class="store-search-item" :href="item.url" role="option">
                        <div class="store-search-thumb">
                            <img x-show="item.image" :src="item.image" :alt="item.image_alt" width="56" height="56">
                        </div>
                        <div class="min-w-0">
                            <p class="store-search-name" x-text="item.name"></p>
                            <p class="store-search-meta" x-show="item.sku" x-text="item.sku"></p>
                            <p class="store-search-save" x-show="item.on_sale" x-text="'Спестявате ' + formatPrice(item.savings)"></p>
                        </div>
                        <div class="store-search-prices">
                            <span class="store-search-compare" x-show="item.on_sale" x-text="formatPrice(item.compare_at_price)"></span>
                            <span class="store-search-price" x-text="formatPrice(item.price)"></span>
                            <span class="store-search-badge" x-show="item.on_sale">Промоция</span>
                        </div>
                    </a>
                </template>
            </div>
        </form>
    </div>

    <nav class="overflow-visible border-t border-line" aria-label="Категории">
        <div class="mx-auto flex w-[min(1120px,calc(100%-2rem))] flex-wrap items-center justify-center gap-x-1 gap-y-1 py-1.5">
            <a
                href="/catalog"
                class="px-3 py-2 text-sm font-semibold no-underline <?= $catalogActive ? 'underline underline-offset-4' : '' ?>"
                <?= $catalogActive ? 'aria-current="page"' : '' ?>
            >Каталог</a>
            <?php foreach ($navCategories as $category): ?>
                <?php
                $href = '/catalog/' . $category->slug;
                $active = store_nav_active($href, $currentPath);
                $hasChildren = $category->children->isNotEmpty();
                $categoryId = (int) $category->id;
                ?>
                <?php if (!$hasChildren): ?>
                    <a
                        href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                        class="px-3 py-2 text-sm font-semibold no-underline <?= $active ? 'underline underline-offset-4' : '' ?>"
                        <?= $active ? 'aria-current="page"' : '' ?>
                    ><?= htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <div
                        class="relative"
                        @mouseenter="openCategory(<?= $categoryId ?>)"
                        @mouseleave="delayCloseCategory()"
                        @click.outside="openCat === <?= $categoryId ?> && (openCat = 0)"
                    >
                        <div class="flex items-center">
                            <a
                                href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                                class="px-2 py-2 text-sm font-semibold no-underline <?= $active ? 'underline underline-offset-4' : '' ?>"
                                <?= $active ? 'aria-current="page"' : '' ?>
                            ><?= htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php Html::iconButton('chevron-down', 'Подкатегории: ' . $category->name, [
                                'class' => 'store-icon-btn--sm',
                                'attrs' => '@click.stop="toggleCategory(' . $categoryId . ')" :aria-expanded="openCat === ' . $categoryId . '" aria-haspopup="true" :class="openCat === ' . $categoryId . ' && \'is-active\'"',
                            ]); ?>
                        </div>
                        <div
                            class="absolute top-full left-1/2 z-50 min-w-[14rem] -translate-x-1/2 pt-2"
                            x-cloak
                            x-show="openCat === <?= $categoryId ?>"
                            x-transition.opacity.duration.120ms
                            @keydown.escape.stop="openCat = 0"
                        >
                            <div class="border border-line bg-canvas py-1 text-left shadow-lg">
                                <?php foreach ($category->children as $child): ?>
                                    <?php $childHref = '/catalog/' . $child->slug; ?>
                                    <a
                                        href="<?= htmlspecialchars($childHref, ENT_QUOTES, 'UTF-8') ?>"
                                        class="block px-3 py-2.5 text-sm font-semibold no-underline hover:bg-ink hover:text-on-accent <?= store_nav_active($childHref, $currentPath) ? 'bg-ink text-on-accent' : '' ?>"
                                    ><?= htmlspecialchars($child->name, ENT_QUOTES, 'UTF-8') ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
