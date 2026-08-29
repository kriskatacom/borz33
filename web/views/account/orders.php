<?php

declare(strict_types=1);

use Store\Core\Html;
?>
<article class="store-card">
    <div class="flex items-start gap-3">
        <span class="store-shortcut-icon"><?= Html::iconSvg('package') ?></span>
        <div>
            <h2>Все още няма поръчки</h2>
            <p class="m-0 text-sm text-muted">Когато поръчате, историята и статусите ще се показват тук.</p>
            <p class="mb-0 mt-4"><a class="font-semibold" href="/catalog">Към каталога</a></p>
        </div>
    </div>
</article>
