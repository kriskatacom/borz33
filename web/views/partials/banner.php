<?php

declare(strict_types=1);

use Store\Core\Banners;

/** @var \App\Models\Banner $banner */
/** @var list<\App\Models\BannerButton> $buttons */

$media = $banner->mediaFile;
$alt = trim((string) ($media->alt ?? '')) !== '' ? (string) $media->alt : (string) $banner->title;
$path = '/' . ltrim((string) $media->path, '/');
$text = (string) $banner->text;
$layout = $banner->layoutKey();
?>
<section class="store-banner store-banner--<?= htmlspecialchars($layout, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars((string) $banner->title, ENT_QUOTES, 'UTF-8') ?>">
    <div class="store-banner-media">
        <img src="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="store-banner-copy">
        <h2 class="store-banner-title"><?= htmlspecialchars((string) $banner->title, ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="store-banner-text"><?= Banners::textHtml($text) ?></div>
        <div class="store-banner-actions">
            <?php foreach ($buttons as $index => $button): ?>
                <?php $url = Banners::safeUrl((string) $button->url); ?>
                <?php if ($url === null): continue; endif; ?>
                <a
                    class="<?= $index === 0 ? 'store-banner-btn' : 'store-banner-btn store-banner-btn--ghost' ?>"
                    href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                    <?php if ($button->opensInNewTab()): ?>
                        target="_blank" rel="noopener noreferrer"
                    <?php endif; ?>
                ><?= htmlspecialchars((string) $button->label, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
