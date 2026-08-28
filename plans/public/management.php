<?php
require_once dirname(__DIR__) . '/views/site.php';
render_header('management', 'Управление', 'Лесен контрол върху продуктите, поръчките и съдържанието на магазина.');
?>
<section class="relative overflow-hidden py-20 sm:py-28">
    <div class="absolute right-0 top-0 h-full w-1/3 bg-sage/45" aria-hidden="true"></div><div class="page-shell relative grid gap-12 lg:grid-cols-[1fr_0.8fr] lg:items-end"><div><p class="eyebrow">Вашият контролен център</p><h1 class="display-title">Управлявате повече. Зависите от <span class="italic text-coral">по-малко.</span></h1></div><p class="body-large">Информацията в магазина няма да бъде заключена в кода. Ще можете сами да обновявате най-важното, когато бизнесът го изисква.</p></div>
</section>
<section class="bg-paper py-24">
    <div class="page-shell"><div class="mb-14 grid gap-8 lg:grid-cols-2 lg:items-end"><h2 class="section-title">Един панел.<br>Целият магазин.</h2><p class="body-large">Ясен интерфейс, пригоден за телефон, таблет и компютър, с достъп според ролята на всеки член от екипа.</p></div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ([
                ['Продукти', 'Цени, снимки, варианти, персонализации и наличности.'],
                ['Поръчки', 'Нови заявки, статуси, плащания, доставки и история.'],
                ['Съдържание', 'Страници, менюта, банери и начални секции.'],
                ['Кампании', 'Промоции, купони, подбрани предложения и периоди.'],
                ['Клиенти', 'Регистрации, контакти, адреси, поръчки и съгласия на едно място.'],
                ['Съобщения', 'Редактируеми писма, автоматични известия и бъдещи предложения.'],
                ['Видимост', 'Заглавия, описания и настройки за търсачките.'],
                ['Отчети', 'Полезен преглед и подготвени данни за счетоводство.'],
            ] as $index => [$heading, $text]): ?>
                <article class="rounded-[1.75rem] border border-forest/10 bg-cream p-6"><span class="text-xs font-extrabold text-coral"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><h3 class="mt-8 font-display text-2xl font-bold"><?= $heading ?></h3><p class="mt-3 text-sm leading-6 text-ink/60"><?= $text ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="py-24">
    <div class="page-shell grid gap-12 lg:grid-cols-[1fr_1.1fr] lg:items-center">
        <div class="soft-card order-2 overflow-hidden bg-forest p-5 text-white lg:order-1"><div class="rounded-3xl bg-white/8 p-6"><div class="flex items-center justify-between"><div><p class="text-xs text-white/45">Добре дошли</p><p class="font-display text-2xl font-bold">Преглед на магазина</p></div><span class="rounded-full bg-sun px-3 py-1 text-xs font-extrabold text-ink">Днес</span></div><div class="mt-7 grid grid-cols-2 gap-3"><div class="rounded-2xl bg-white/10 p-4"><p class="text-xs text-white/50">Нови поръчки</p><p class="mt-2 text-3xl font-extrabold">12</p></div><div class="rounded-2xl bg-coral p-4"><p class="text-xs text-white/70">За обработка</p><p class="mt-2 text-3xl font-extrabold">5</p></div></div><div class="mt-3 rounded-2xl bg-white p-4 text-ink"><div class="flex justify-between text-xs font-bold"><span>Последни поръчки</span><span class="text-moss">Виж всички</span></div><?php foreach (['#1048 · Нова', '#1047 · Изпратена', '#1046 · Платена'] as $row): ?><div class="mt-3 flex items-center justify-between border-t border-forest/10 pt-3 text-xs"><span><?= $row ?></span><span class="size-2 rounded-full bg-coral"></span></div><?php endforeach; ?></div></div></div>
        <div class="order-1 lg:order-2"><p class="eyebrow">Яснота без усложнения</p><h2 class="section-title">Важното е видимо от първия поглед.</h2><p class="body-large mt-6">Началният екран ще показва какво изисква внимание: нови поръчки, ниски наличности и текущи задачи. Така екипът действа навреме, без да търси информация на различни места.</p></div>
    </div>
</section>
<section class="py-24">
    <div class="page-shell grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
        <div><p class="eyebrow">Клиенти и комуникация</p><h2 class="section-title">Съгласие, предпочитания и предложения.</h2><p class="body-large mt-6">От панела виждате кои клиенти са избрали да получават бъдещи предложения, без да смесвате рекламните съобщения със задължителните писма за поръчката.</p></div>
        <div class="grid gap-4 sm:grid-cols-2"><article class="soft-card p-7"><h3 class="font-display text-2xl font-bold">Регистрирани клиенти</h3><ul class="mt-5 space-y-3 text-sm leading-6 text-ink/65"><li>• Профил и потвърден e-mail</li><li>• История на поръчките</li><li>• Адреси и предпочитания</li><li>• Заявки за данните и изтриване</li></ul></article><article class="soft-card p-7"><h3 class="font-display text-2xl font-bold">Модул за предложения</h3><ul class="mt-5 space-y-3 text-sm leading-6 text-ink/65"><li>• Списъци според съгласието</li><li>• Групи по интерес или покупка</li><li>• Подготовка и преглед на съобщение</li><li>• Отписване и история на изпращането</li></ul></article></div>
    </div>
</section>
<section class="bg-forest py-24 text-white">
    <div class="page-shell grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
        <div><p class="mb-5 text-xs font-extrabold uppercase tracking-[0.2em] text-sun">Econt от административния панел</p><h2 class="font-display text-4xl leading-tight sm:text-5xl">Подготвяте пратката без повторно въвеждане.</h2><p class="mt-6 text-lg leading-8 text-white/60">Името, телефонът, адресът или избраният офис идват от поръчката. Остава да проверите данните и да създадете товарителницата.</p></div>
        <div class="rounded-[2rem] bg-white p-6 text-ink sm:p-8"><div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-xs font-bold text-ink/45">Поръчка #1048</p><h3 class="mt-1 font-display text-2xl font-bold">Доставка с Econt</h3></div><span class="rounded-full bg-sage px-4 py-2 text-xs font-extrabold text-forest">Готова за подготовка</span></div><div class="mt-6 grid gap-3 sm:grid-cols-2"><div class="rounded-2xl bg-cream p-4"><p class="text-xs text-ink/45">Получател</p><p class="mt-1 font-bold">Данните са попълнени</p></div><div class="rounded-2xl bg-cream p-4"><p class="text-xs text-ink/45">Доставка</p><p class="mt-1 font-bold">Избран офис / адрес</p></div></div><div class="mt-4 grid gap-3 sm:grid-cols-2"><button type="button" class="rounded-full bg-coral px-5 py-3 text-sm font-extrabold text-white">Създай товарителница</button><button type="button" class="rounded-full border border-forest/15 px-5 py-3 text-sm font-extrabold text-forest">Заяви куриер</button></div><div class="mt-5 flex flex-wrap gap-2 text-xs font-bold"><span class="rounded-full bg-sage px-3 py-1.5">В движение</span><span class="rounded-full bg-cream px-3 py-1.5">В офис</span><span class="rounded-full bg-cream px-3 py-1.5">Доставена</span><span class="rounded-full bg-cream px-3 py-1.5">Върната</span></div></div>
    </div>
</section>
<section class="bg-sage py-24"><div class="page-shell"><div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr]"><div><p class="eyebrow">Възможности за свързване</p><h2 class="section-title">Готов за реалния Ви работен ден.</h2></div><div class="grid gap-4 sm:grid-cols-2"><?php foreach ([['Картови плащания','Удобно и сигурно плащане чрез избран банков или платежен партньор.'],['Econt','Избор на доставка, подготовка на пратки и проследяване на статуса.'],['Счетоводни данни','Подреден export на договорената информация за избран период.'],['Бъдещи услуги','Възможност за добавяне на складова, търговска или маркетинг система.']] as [$h,$t]): ?><article class="rounded-3xl bg-paper p-7"><h3 class="font-display text-2xl font-bold"><?= $h ?></h3><p class="mt-3 leading-7 text-ink/65"><?= $t ?></p></article><?php endforeach; ?></div></div></div></section>
<section class="py-24"><div class="page-shell text-center"><h2 class="section-title">Планът има ясни стъпки.</h2><p class="body-large mx-auto mt-6 max-w-2xl">Преди реалната изработка уточняваме приоритетите, за да получите точно това, което бизнесът Ви използва.</p><a class="btn-primary mt-8" href="/roadmap.php">Вижте реализацията <span class="ml-2">→</span></a></div></section>
<?php render_footer(); ?>
