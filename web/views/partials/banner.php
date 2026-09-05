<?php

declare(strict_types=1);

use Store\Core\Banners;

/** @var \App\Models\Banner $banner */
/** @var list<\App\Models\BannerButton> $buttons */

$media = $banner->mediaFile;
$alt = trim((string) ($media->alt ?? '')) !== '' ? (string) $media->alt : (string) $banner->title;
$path = \App\Resources\StorageUrl::forPath((string) $media->path) ?? '';
$text = (string) $banner->text;
$layout = $banner->layoutKey();
$height = (int) ($banner->height ?? 0);
$widthMode = (string) ($banner->width_mode ?? 'container');
$widthMode = in_array($widthMode, ['container', 'full'], true) ? $widthMode : 'container';
$imagePosition = (string) ($banner->image_position ?? 'center');
$imagePosition = array_key_exists($imagePosition, \App\Models\Banner::IMAGE_POSITIONS) ? $imagePosition : 'center';
$imagePositionCss = \App\Models\Banner::IMAGE_POSITIONS[$imagePosition];
$contentPosition = (string) ($banner->content_position ?? 'center');
$contentPosition = array_key_exists($contentPosition, \App\Models\Banner::CONTENT_POSITIONS) ? $contentPosition : 'center';
$contentPositionCss = \App\Models\Banner::CONTENT_POSITIONS[$contentPosition];
$heightAttribute = $height > 0 ? ' data-height="' . $height . '" style="--store-banner-height: ' . $height . 'px"' : '';
$mediaStyle = 'background-image: url("' . $path . '"); background-position: ' . $imagePositionCss;
$copyStyle = 'align-items: ' . $contentPositionCss['horizontal'] . '; justify-content: ' . $contentPositionCss['vertical'] . '; text-align: ' . ($contentPositionCss['horizontal'] === 'center' ? 'center' : ($contentPositionCss['horizontal'] === 'flex-end' ? 'right' : 'left'));
?>
<section class="store-banner store-banner--<?= htmlspecialchars($layout, ENT_QUOTES, 'UTF-8') ?> store-banner--width-<?= $widthMode ?>"<?= $heightAttribute ?> aria-label="<?= htmlspecialchars((string) $banner->title, ENT_QUOTES, 'UTF-8') ?>">
    <div class="store-banner-media" role="img" aria-label="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>" style="<?= htmlspecialchars($mediaStyle, ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="store-banner-copy" style="<?= htmlspecialchars($copyStyle, ENT_QUOTES, 'UTF-8') ?>">
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
