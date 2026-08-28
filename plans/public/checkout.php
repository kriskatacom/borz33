<?php
require_once dirname(__DIR__) . '/views/site.php';
render_header('checkout', 'Как протича една поръчка', 'Пример как клиентът ще избира продукти и ще завършва поръчката си.');
?>
<section class="border-b border-forest/10 py-20 sm:py-24">
    <div class="page-shell grid gap-10 lg:grid-cols-[1fr_0.75fr] lg:items-end">
        <div><p class="eyebrow">Примерен клиентски път</p><h1 class="display-title">От продукта до <span class="italic text-coral">потвърждението.</span></h1></div>
        <p class="body-large">Този екран показва как ще протича една стандартна покупка. Точните полета, плащания и доставки ще се уточнят според Вашия бизнес.</p>
    </div>
</section>

<section class="bg-paper py-20">
    <div class="page-shell grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="soft-card p-6 sm:p-9">
            <div class="flex flex-wrap gap-2 text-xs font-extrabold">
                <span class="rounded-full bg-forest px-4 py-2 text-white">1. Данни</span><span class="rounded-full bg-sage px-4 py-2 text-forest">2. Доставка</span><span class="rounded-full bg-sage px-4 py-2 text-forest">3. Плащане</span>
            </div>
            <h2 class="mt-8 font-display text-3xl font-bold">Данни за поръчката</h2>
            <div class="mt-7 grid gap-4 sm:grid-cols-2">
                <?php foreach (['Име и фамилия', 'Телефон', 'E-mail', 'Населено място'] as $field): ?><div><p class="mb-2 text-xs font-bold text-ink/55"><?= $field ?></p><div class="h-12 rounded-2xl border border-forest/15 bg-cream"></div></div><?php endforeach; ?>
            </div>
            <div class="mt-6 rounded-2xl bg-sage/60 p-5"><p class="font-bold">Избор на доставка с Econt</p><div class="mt-4 grid gap-3 sm:grid-cols-3"><div class="rounded-xl border-2 border-coral bg-white p-4 text-sm font-bold">До офис</div><div class="rounded-xl border border-forest/10 bg-white p-4 text-sm font-bold">До Еконтомат</div><div class="rounded-xl border border-forest/10 bg-white p-4 text-sm font-bold">До адрес</div></div><p class="mt-4 text-xs leading-5 text-ink/55">Магазинът показва подходящите места и изчислява цената за доставка.</p></div>
            <button class="mt-7 w-full rounded-full bg-coral px-6 py-4 text-sm font-extrabold text-white" type="button">Продължи към плащане</button>
        </div>
        <aside class="soft-card h-fit p-6 sm:p-8">
            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-moss">Вашата поръчка</p>
            <div class="mt-6 flex gap-4 border-b border-forest/10 pb-6"><div class="size-20 rounded-2xl bg-sun"></div><div><h2 class="font-display text-xl font-bold">Примерен продукт</h2><p class="mt-1 text-sm text-ink/55">Вариант · 1 бр.</p><p class="mt-2 font-extrabold">89,00 лв.</p></div></div>
            <dl class="mt-6 space-y-3 text-sm"><div class="flex justify-between"><dt>Продукти</dt><dd>89,00 лв.</dd></div><div class="flex justify-between"><dt>Доставка</dt><dd>изчислява се</dd></div><div class="flex justify-between border-t border-forest/10 pt-4 text-lg font-extrabold"><dt>Общо</dt><dd>89,00 лв.</dd></div></dl>
            <p class="mt-6 rounded-2xl bg-cream p-4 text-xs leading-5 text-ink/55">Преди завършване клиентът вижда крайната сума, условията за доставка и избрания начин на плащане.</p>
        </aside>
    </div>
</section>

<section class="bg-forest py-24 text-white">
    <div class="page-shell">
        <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-end"><div><p class="mb-5 text-xs font-extrabold uppercase tracking-[0.2em] text-sun">Econt интеграция</p><h2 class="font-display text-4xl leading-tight sm:text-6xl">От избора на доставка до получената пратка.</h2></div><p class="text-lg leading-8 text-white/65">Магазинът работи директно със системата на Econt и намалява ръчното въвеждане. Клиентът избира удобна доставка, а Вие подготвяте и проследявате пратката от поръчката в административния панел.</p></div>
        <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ([
                ['01','Клиентът избира','Офис, Еконтомат или личен адрес. Магазинът показва правилните места и изчислява цената.'],
                ['02','Уточнява услугите','При договорена възможност се избират наложен платеж, „Преглед“ и „Тест“.'],
                ['03','Създавате товарителница','От поръчката натискате „Създай товарителница“. Данните на клиента вече са попълнени.'],
                ['04','Принтирате и подготвяте','Товарителницата се принтира и залепва върху готовата пратка.'],
                ['05','Предавате на Econt','Носите пратката до офис или заявявате куриер да я вземе от магазина или склада.'],
                ['06','Следите доставката','Виждате дали пратката е в движение, в офис, доставена или върната.'],
            ] as [$n,$h,$t]): ?><article class="rounded-3xl border border-white/15 bg-white/5 p-6"><span class="font-display text-2xl font-bold text-sun"><?= $n ?></span><h3 class="mt-6 font-display text-2xl font-bold"><?= $h ?></h3><p class="mt-3 text-sm leading-6 text-white/60"><?= $t ?></p></article><?php endforeach; ?>
        </div>
        <div class="mt-8 rounded-3xl bg-sun p-6 text-ink sm:p-8"><p class="text-xs font-extrabold uppercase tracking-[0.16em] text-forest">Накратко</p><p class="mt-4 font-display text-2xl font-bold leading-relaxed sm:text-3xl">Клиент поръчва → избира доставка → магазинът създава товарителница → тя се принтира → Econt получава пратката → доставката се проследява.</p></div>
        <p class="mt-6 text-sm leading-6 text-white/50">Конкретните услуги, цени и възможността за заявка на куриер се потвърждават според договора на търговеца с Econt.</p>
    </div>
</section>

<section class="py-24">
    <div class="page-shell"><h2 class="section-title max-w-3xl">Какво се случва след поръчката</h2><div class="mt-12 grid gap-4 md:grid-cols-4">
        <?php foreach ([['01','Потвърждение','Клиентът получава номер и e-mail с всички избрани данни.'],['02','Проверка','Вие виждате новата поръчка и потвърждавате обработката.'],['03','Изпращане','Подготвяте пратката и добавяте информация за доставката.'],['04','Получаване','Клиентът следи статуса и получава финално известие.']] as [$n,$h,$t]): ?><article class="rounded-3xl border border-forest/10 bg-paper p-6"><span class="font-display text-2xl font-bold text-coral"><?= $n ?></span><h3 class="mt-6 font-display text-xl font-bold"><?= $h ?></h3><p class="mt-3 text-sm leading-6 text-ink/60"><?= $t ?></p></article><?php endforeach; ?>
    </div></div>
</section>

<section class="bg-sage py-20"><div class="page-shell"><div class="grid gap-8 lg:grid-cols-2"><div><p class="eyebrow">Възможни решения</p><h2 class="section-title">Плащане и доставка според Вашите правила.</h2></div><div class="grid gap-4 sm:grid-cols-2"><div class="rounded-3xl bg-paper p-6"><h3 class="font-display text-2xl font-bold">Плащане</h3><ul class="mt-4 space-y-2 text-sm leading-6 text-ink/65"><li>• Наложен платеж</li><li>• Банков превод</li><li>• Карта при избран оператор</li></ul></div><div class="rounded-3xl bg-paper p-6"><h3 class="font-display text-2xl font-bold">Доставка</h3><ul class="mt-4 space-y-2 text-sm leading-6 text-ink/65"><li>• Офис, Еконтомат или адрес</li><li>• Преглед и тест при възможност</li><li>• Безплатна доставка по правило</li></ul></div></div></div></div></section>
<?php render_footer(); ?>
