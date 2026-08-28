<?php
require_once dirname(__DIR__) . '/views/site.php';
render_header('home', 'Онлайн магазин, създаден за Вашия бизнес', 'Предложение за модерен онлайн магазин с лесно управление и възможност за развитие.');
?>
<section class="relative overflow-hidden">
    <div class="absolute -right-24 top-12 size-80 rounded-full bg-sun/30 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -left-32 bottom-0 size-96 rounded-full bg-sage/70 blur-3xl" aria-hidden="true"></div>
    <div class="page-shell relative grid min-h-[760px] items-center gap-14 py-20 lg:grid-cols-[1.05fr_0.95fr] lg:py-24">
        <div>
            <p class="eyebrow"><span class="size-2 rounded-full bg-coral"></span> Предложение за развитие</p>
            <h1 class="display-title max-w-4xl">Магазин, който работи <span class="italic text-coral">за Вас.</span></h1>
            <p class="body-large mt-8 max-w-2xl">Собствено онлайн пространство, създадено около Вашите продукти, клиенти и начин на работа — лесно за управление днес и готово за развитие утре.</p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a class="btn-primary" href="/store.php">Разгледайте възможностите <span class="ml-2" aria-hidden="true">→</span></a>
                <a class="btn-secondary" href="/roadmap.php">Как ще го реализираме</a>
            </div>
        </div>
        <div class="relative mx-auto w-full max-w-xl">
            <div class="gentle-drift soft-card relative z-10 overflow-hidden p-5 sm:p-7">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-moss">Вашият магазин</p>
                        <p class="mt-1 font-display text-2xl font-bold">Всичко на едно място</p>
                    </div>
                    <span class="rounded-full bg-sage px-3 py-1.5 text-xs font-extrabold text-forest">Лесно управление</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-3xl bg-forest p-5 text-white sm:p-6">
                        <p class="text-3xl font-extrabold">24/7</p><p class="mt-2 text-sm text-white/65">отворен за поръчки</p>
                    </div>
                    <div class="rounded-3xl bg-sun p-5 sm:p-6">
                        <p class="text-3xl font-extrabold">100%</p><p class="mt-2 text-sm text-ink/65">контрол върху съдържанието</p>
                    </div>
                    <div class="col-span-2 rounded-3xl border border-forest/10 bg-cream p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3"><span class="font-bold">Поръчки и клиенти</span><span class="text-coral">● ● ●</span></div>
                        <div class="mt-5 h-2 overflow-hidden rounded-full bg-forest/10"><div class="h-full w-[76%] rounded-full bg-coral"></div></div>
                        <div class="mt-4 flex justify-between text-xs font-bold text-ink/50"><span>Ясен процес</span><span>Сигурно развитие</span></div>
                    </div>
                </div>
            </div>
            <div class="absolute -right-6 -top-7 hidden rotate-6 rounded-3xl bg-coral px-6 py-5 text-white shadow-xl sm:block"><p class="font-display text-xl font-bold">Създаден по мярка</p><p class="text-xs text-white/70">без излишни ограничения</p></div>
        </div>
    </div>
</section>

<section class="bg-paper py-24">
    <div class="page-shell">
        <h2 class="section-title">Какво ще получите</h2>
        <p class="body-large mt-5 max-w-2xl">Магазинът има две свързани части: удобна публична страна за купувачите и защитена зона за Вашия екип.</p>
        <div class="mt-12 grid gap-6 md:grid-cols-2">
            <article class="soft-card p-8"><p class="text-xs font-extrabold uppercase tracking-[0.16em] text-coral">За клиентите</p><h3 class="mt-3 font-display text-3xl font-bold">Лесно откриване и поръчване</h3><ul class="mt-6 space-y-3 leading-7 text-ink/65"><li>✓ Каталог, категории, търсене и подробни продукти</li><li>✓ Варианти и персонализация на избрани артикули</li><li>✓ Количка, отстъпки, Econt и удобни плащания</li><li>✓ Профил по избор, история и ясни e-mail известия</li></ul></article>
            <article class="soft-card p-8"><p class="text-xs font-extrabold uppercase tracking-[0.16em] text-coral">За Вас</p><h3 class="mt-3 font-display text-3xl font-bold">Управление без промяна по сайта</h3><ul class="mt-6 space-y-3 leading-7 text-ink/65"><li>✓ Продукти, цени, наличности и категории</li><li>✓ Поръчки, клиенти, доставки и плащания</li><li>✓ Страници, менюта, банери и e-mail съобщения</li><li>✓ SEO, GDPR, роли и договорен счетоводен export</li></ul></article>
        </div>
    </div>
</section>

<section class="bg-sage py-24">
    <div class="page-shell"><p class="eyebrow">Пътят на клиента</p><h2 class="section-title max-w-3xl">Четири ясни стъпки до покупката.</h2><div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><?php foreach ([['01','Открива','Търси, филтрира и сравнява подходящи продукти.'],['02','Избира','Вижда подробности, вариант и възможна персонализация.'],['03','Поръчва','Попълва данни, избира Econt и начин на плащане.'],['04','Получава','Следи e-mail известията и статуса на доставката.']] as [$n,$h,$t]): ?><article class="rounded-3xl bg-paper p-6"><span class="font-display text-3xl font-bold text-coral"><?= $n ?></span><h3 class="mt-6 font-display text-2xl font-bold"><?= $h ?></h3><p class="mt-3 text-sm leading-6 text-ink/60"><?= $t ?></p></article><?php endforeach; ?></div></div>
</section>

<section class="bg-paper py-24 sm:py-28">
    <div class="page-shell">
        <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
            <div><p class="eyebrow">Основната идея</p><h2 class="section-title">Вашият бизнес задава правилата.</h2></div>
            <p class="body-large max-w-2xl lg:justify-self-end">Магазинът няма да Ви принуждава да променяте начина си на работа. Той ще бъде организиран около реалните Ви нужди — от представянето на продуктите до последната стъпка на поръчката.</p>
        </div>
        <div class="mt-14 grid gap-5 md:grid-cols-3">
            <?php foreach ([
                ['01', 'Разпознаваем облик', 'Дизайн, който изгражда доверие и представя марката Ви последователно на всяко устройство.'],
                ['02', 'Лесна покупка', 'Кратък и ясен път от продукта до поръчката, без ненужни стъпки и разсейване.'],
                ['03', 'Свобода за развитие', 'Стабилна основа, към която могат да се добавят нови услуги и възможности с растежа на бизнеса.'],
            ] as [$number, $heading, $text]): ?>
                <article class="soft-card p-7 sm:p-8"><span class="font-display text-4xl font-bold text-coral"><?= $number ?></span><h3 class="mt-8 font-display text-2xl font-bold"><?= $heading ?></h3><p class="mt-4 leading-7 text-ink/65"><?= $text ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-24 sm:py-28">
    <div class="page-shell">
        <div class="soft-card overflow-hidden bg-forest text-white">
            <div class="grid lg:grid-cols-2">
                <div class="p-8 sm:p-12 lg:p-16"><p class="mb-5 text-xs font-extrabold uppercase tracking-[0.2em] text-sun">Истинско предимство</p><h2 class="font-display text-4xl leading-tight sm:text-5xl">Не просто още един готов магазин.</h2><p class="mt-6 text-lg leading-8 text-white/65">Решението ще бъде създадено конкретно за Вашия бизнес. Получавате контрол, по-ясно преживяване за клиентите и възможност магазинът да се развива без зависимост от множество несвързани добавки.</p></div>
                <div class="grid gap-px bg-white/10 sm:grid-cols-2">
                    <?php foreach (['По-малко ограничения', 'Ясно управление', 'Подреден процес', 'Дългосрочна стойност'] as $item): ?><div class="flex min-h-40 items-end bg-forest/95 p-7"><p class="font-display text-2xl font-bold"><span class="mr-2 text-sun">✓</span><?= $item ?></p></div><?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-24 sm:pb-28">
    <div class="page-shell"><div class="text-center"><p class="eyebrow">Разгледайте целия план</p><h2 class="section-title mx-auto max-w-4xl">Реална представа преди началото на изработката.</h2></div><div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ([['/store.php','Страници','Какво съдържа всеки основен екран.'],['/checkout.php','Поръчка','Как клиентът купува и избира доставка.'],['/improvements.php','Предложения','Идеи за повече продажби и по-добро обслужване.'],['/management.php','Управление','Какво контролира Вашият екип.'],['/roadmap.php','Етапи','Какво правим, какво давате Вие и кога е готово.'],['/preparation.php','Подготовка','Материали и решения преди старта.']] as [$url,$h,$t]): ?><a href="<?= $url ?>" class="soft-card p-6 transition hover:-translate-y-1 hover:shadow-xl"><h3 class="font-display text-2xl font-bold"><?= $h ?></h3><p class="mt-3 text-sm leading-6 text-ink/60"><?= $t ?></p><span class="mt-6 inline-block font-extrabold text-coral">Разгледайте →</span></a><?php endforeach; ?></div></div>
</section>
<?php render_footer(); ?>
