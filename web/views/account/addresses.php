<?php

declare(strict_types=1);

use Store\Core\Html;
?>
<article class="store-card">
    <div class="flex items-start gap-3">
        <span class="store-shortcut-icon"><?= Html::iconSvg('map-pin') ?></span>
        <div>
            <h2>Няма записани адреси</h2>
            <p class="m-0 text-sm text-muted">Адрес за доставка и фактурация ще добавите при първата поръчка, след което ще ги управлявате оттук.</p>
        </div>
    </div>
</article>
