<?php
require_once dirname(__DIR__) . '/views/site.php';
render_header('store', 'Магазинът', 'Какво ще получат клиентите на бъдещия онлайн магазин.');
?>
<section class="relative overflow-hidden border-b border-forest/10 py-20 sm:py-28">
    <div class="page-shell relative"><div class="max-w-4xl"><p class="eyebrow">Изживяването за клиента</p><h1 class="display-title">Пазаруването трябва да бъде <span class="italic text-coral">лесно.</span></h1><p class="body-large mt-8 max-w-2xl">Всеки екран ще води клиента уверено — от откриването на правилния продукт до успешното завършване на поръчката.</p></div></div>
</section>
<section class="bg-paper py-24">
    <div class="page-shell">
        <p class="eyebrow">Карта на бъдещия сайт</p>
        <h2 class="section-title max-w-4xl">Какво ще съдържа всяка част.</h2>
        <p class="body-large mt-6 max-w-3xl">Това не са готовите страници на магазина, а конкретен преглед какво ще вижда и прави посетителят във всеки основен екран.</p>
        <div class="mt-12 grid gap-5 lg:grid-cols-2">
            <?php foreach ([
                ['01','Начална страница','За всеки нов посетител',['Основно послание и актуален банер','Подбрани категории и продукти','Активни промоции и преимущества','Доверие, контакти и ясни следващи действия']],
                ['02','Каталог и категории','За клиента, който разглежда',['Подкатегории, филтри и сортиране','Продуктови снимки, цена и наличност','Промоционални означения','Удобно преминаване между резултатите']],
                ['03','Търсене','За клиента с конкретна нужда',['Бързи резултати по име и код','Подсказки и подходящи филтри','Полезни предложения при липса на резултат','Лесно връщане към каталога']],
                ['04','Продукт','За клиента преди решение',['Галерия, описание, цена и наличност','Варианти като размер или цвят','Персонализирано поле, когато е нужно','Доставка, свързани и допълващи продукти']],
                ['05','Количка','За преглед преди поръчка',['Продукти, варианти и персонализации','Промяна на количество или премахване','Код за отстъпка','Ясна междинна и крайна сума']],
                ['06','Завършване на поръчка','За изпращане и плащане',['Покупка без задължителен профил','Данни за доставка и фактура','Econt офис, автомат или адрес','Избран начин на плащане и потвърждение']],
                ['07','Регистрация и клиентски профил','За редовни клиенти, ако бъде включен',['Регистрация, вход и забравена парола','Запазени адреси и лични данни','История, подробности и повторна поръчка','Отделен избор за бъдещи предложения по e-mail']],
                ['08','Информационни страници','За доверие и обслужване',['За нас, контакти и форма за запитване','Доставка, плащане и връщане','Общи условия и поверителност','Допълнителни страници, създавани от Вас']],
            ] as [$n,$h,$audience,$items]): ?>
                <article class="soft-card p-7 sm:p-8"><div class="flex gap-5"><span class="font-display text-3xl font-bold text-coral"><?= $n ?></span><div><h3 class="font-display text-2xl font-bold"><?= $h ?></h3><p class="mt-1 text-xs font-extrabold uppercase tracking-[0.12em] text-moss"><?= $audience ?></p></div></div><ul class="mt-6 grid gap-3 text-sm leading-6 text-ink/65 sm:grid-cols-2"><?php foreach ($items as $item): ?><li class="flex gap-2"><span class="font-bold text-coral">✓</span><span><?= $item ?></span></li><?php endforeach; ?></ul></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="bg-sage py-24">
    <div class="page-shell grid gap-8 lg:grid-cols-2">
        <article class="rounded-[2rem] bg-paper p-8 sm:p-10"><p class="eyebrow">Клиентски профил</p><h2 class="font-display text-4xl font-bold">По-бързо за редовните клиенти.</h2><p class="mt-5 text-lg leading-8 text-ink/65">Регистрацията може да бъде по желание, така че първата покупка да остане лесна. Профилът дава удобство при следващи поръчки.</p><ul class="mt-7 space-y-3 leading-7 text-ink/65"><li>✓ Сигурна регистрация и вход</li><li>✓ Потвърждение на e-mail адреса</li><li>✓ Запазени адреси и история на поръчките</li><li>✓ Забравена парола и управление на личните данни</li></ul></article>
        <article class="rounded-[2rem] bg-forest p-8 text-white sm:p-10"><p class="mb-5 text-xs font-extrabold uppercase tracking-[0.18em] text-sun">Бъдещи предложения</p><h2 class="font-display text-4xl font-bold">Връзка с клиента след покупката.</h2><p class="mt-5 text-lg leading-8 text-white/65">Клиентът сам избира дали желае да получава новини, промоции и персонални предложения. Това съгласие е отделно от приемането на поръчката.</p><ul class="mt-7 space-y-3 leading-7 text-white/65"><li>✓ Ясна отметка без предварително избиране</li><li>✓ Предложения според интереси и покупки</li><li>✓ Видим начин за промяна на предпочитанията</li><li>✓ Лесно отписване във всяко съобщение</li></ul></article>
    </div>
</section>
<section class="bg-paper py-24">
    <div class="page-shell grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <?php $features = [
            ['⌕', 'Лесно откриване', 'Подредени категории, удобно търсене и полезни филтри помагат на клиента да стигне бързо до точния продукт.'],
            ['◇', 'Убедително представяне', 'Красиви снимки, ясни описания, варианти, наличност и свързани предложения на едно място.'],
            ['✎', 'Персонален избор', 'Клиентът може да добави текст, опция или друга персонализация към избрани продукти.'],
            ['＋', 'Удобна количка', 'Промяна на количества, преглед на избраните опции, отстъпки и ясна крайна сума.'],
            ['✓', 'Спокойна поръчка', 'Кратки стъпки, покупка без задължителна регистрация и ясно потвърждение на всяко действие.'],
            ['↗', 'Информация след покупка', 'Навременни съобщения за приемане, обработка, плащане и доставка на поръчката.'],
        ]; foreach ($features as [$icon, $heading, $text]): ?>
            <article class="soft-card p-7 sm:p-8"><?= feature_icon($icon) ?><h2 class="mt-7 font-display text-2xl font-bold"><?= $heading ?></h2><p class="mt-4 leading-7 text-ink/65"><?= $text ?></p></article>
        <?php endforeach; ?>
    </div>
</section>
<section class="py-24">
    <div class="page-shell grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <div><p class="eyebrow">Навсякъде с клиента</p><h2 class="section-title">Създаден първо за мобилния свят.</h2><p class="body-large mt-6">Магазинът ще се усеща естествено на телефон, таблет и компютър. Бутони, менюта, снимки и форми ще бъдат удобни за докосване и лесни за разбиране.</p></div>
        <div class="soft-card relative min-h-[460px] overflow-hidden bg-sage p-8">
            <div class="absolute left-1/2 top-10 w-64 -translate-x-1/2 rounded-[2.4rem] border-[10px] border-forest bg-paper p-4 shadow-2xl">
                <div class="mx-auto mb-4 h-1.5 w-16 rounded-full bg-forest/20"></div><div class="aspect-[4/3] rounded-2xl bg-sun"></div><div class="mt-4 h-3 w-2/3 rounded bg-forest/20"></div><div class="mt-2 h-2 w-full rounded bg-forest/10"></div><div class="mt-2 h-2 w-4/5 rounded bg-forest/10"></div><div class="mt-5 rounded-full bg-coral py-3 text-center text-xs font-extrabold text-white">Добави в количка</div>
            </div>
            <span class="absolute left-8 top-14 rounded-full bg-white px-4 py-2 text-xs font-extrabold text-forest shadow-lg">Ясно</span><span class="absolute bottom-16 right-7 rounded-full bg-forest px-4 py-2 text-xs font-extrabold text-white shadow-lg">Бързо</span>
        </div>
    </div>
</section>
<section class="bg-forest py-24 text-white">
    <div class="page-shell"><div class="max-w-3xl"><p class="mb-5 text-xs font-extrabold uppercase tracking-[0.2em] text-sun">Доверие във всяка стъпка</p><h2 class="font-display text-4xl leading-tight sm:text-6xl">Клиентът винаги знае какво следва.</h2></div><div class="mt-14 grid gap-4 md:grid-cols-4"><?php foreach ([['1','Избира продукт'],['2','Персонализира'],['3','Поръчва'],['4','Получава']] as [$n,$t]): ?><div class="rounded-3xl border border-white/15 p-6"><span class="text-sm font-extrabold text-sun"><?= $n ?></span><p class="mt-8 font-display text-2xl font-bold"><?= $t ?></p></div><?php endforeach; ?></div></div>
</section>
<section class="py-24"><div class="page-shell text-center"><h2 class="section-title">Вижте примерната поръчка.</h2><p class="body-large mx-auto mt-6 max-w-xl">Следващият екран показва как клиентът ще попълва данни, ще избира доставка и ще вижда крайната сума.</p><a class="btn-primary mt-8" href="/checkout.php">Преглед на поръчката <span class="ml-2">→</span></a></div></section>
<?php render_footer(); ?>
