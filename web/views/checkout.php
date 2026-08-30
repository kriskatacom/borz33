<?php

declare(strict_types=1);

use Store\Services\ProductPage;

/** @var list<array<string, mixed>> $lines */
/** @var array<string, string> $form */
/** @var array<string, string> $errors */
/** @var string $total */
/** @var string $csrf */

$lines = $lines ?? [];
$form = $form ?? [];
$errors = $errors ?? [];
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$value = static fn (string $key): string => $escape($form[$key] ?? '');
$error = static fn (string $key): ?string => isset($errors[$key]) ? (string) $errors[$key] : null;
$delivery = in_array(($form['delivery_method'] ?? ''), ['address', 'office'], true)
    ? (string) $form['delivery_method']
    : 'address';
$payment = in_array(($form['payment_method'] ?? ''), ['cash_on_delivery', 'bank_transfer'], true)
    ? (string) $form['payment_method']
    : 'cash_on_delivery';
$itemCount = array_sum(array_map(static fn (array $line): int => (int) ($line['qty'] ?? 0), $lines));
?>
<section class="store-checkout" data-checkout>
    <header class="store-checkout-head">
        <a href="/cart" class="store-checkout-back" aria-label="Обратно към количката">
            <span aria-hidden="true">←</span> Количка
        </a>
        <p>Сигурно завършване</p>
        <h1>Детайли за поръчката</h1>
        <p class="store-checkout-intro">Попълнете данните си и прегледайте поръчката преди изпращане.</p>
        <ol class="store-checkout-steps" aria-label="Стъпки на поръчката">
            <li class="is-done"><span aria-hidden="true">✓</span><strong>Количка</strong></li>
            <li class="is-current" aria-current="step"><span>2</span><strong>Детайли</strong></li>
            <li><span>3</span><strong>Готово</strong></li>
        </ol>
    </header>

    <?php if ($errors !== []): ?>
        <div class="store-checkout-alert" role="alert" tabindex="-1" data-checkout-alert>
            <span class="store-checkout-alert-icon" aria-hidden="true">!</span>
            <div>
                <strong>Някои данни се нуждаят от внимание</strong>
                <p>Проверете маркираните полета и опитайте отново.</p>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="/checkout" class="store-checkout-layout" data-checkout-form>
        <input type="hidden" name="_token" value="<?= $escape($csrf) ?>">

        <div class="store-checkout-form">
            <section class="store-checkout-card" aria-labelledby="checkout-contact-title">
                <div class="store-checkout-card-title">
                    <span aria-hidden="true">1</span>
                    <div>
                        <h2 id="checkout-contact-title">Контактни данни</h2>
                        <p>За потвърждение и връзка относно доставката.</p>
                    </div>
                </div>
                <div class="store-checkout-fields store-checkout-fields--two">
                    <label class="<?= $error('first_name') ? 'has-error' : '' ?>">
                        <span>Име <em>*</em></span>
                        <input type="text" name="first_name" autocomplete="given-name" value="<?= $value('first_name') ?>" maxlength="100" required<?= $error('first_name') ? ' aria-invalid="true" aria-describedby="first-name-error"' : '' ?>>
                        <?php if ($error('first_name')): ?><small id="first-name-error"><?= $escape($error('first_name')) ?></small><?php endif; ?>
                    </label>
                    <label class="<?= $error('last_name') ? 'has-error' : '' ?>">
                        <span>Фамилия <em>*</em></span>
                        <input type="text" name="last_name" autocomplete="family-name" value="<?= $value('last_name') ?>" maxlength="100" required<?= $error('last_name') ? ' aria-invalid="true" aria-describedby="last-name-error"' : '' ?>>
                        <?php if ($error('last_name')): ?><small id="last-name-error"><?= $escape($error('last_name')) ?></small><?php endif; ?>
                    </label>
                    <label class="<?= $error('email') ? 'has-error' : '' ?>">
                        <span>Имейл <em>*</em></span>
                        <input type="email" name="email" autocomplete="email" inputmode="email" value="<?= $value('email') ?>" maxlength="191" placeholder="name@example.com" required<?= $error('email') ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                        <?php if ($error('email')): ?><small id="email-error"><?= $escape($error('email')) ?></small><?php endif; ?>
                    </label>
                    <label class="<?= $error('phone') ? 'has-error' : '' ?>">
                        <span>Телефон <em>*</em></span>
                        <input type="tel" name="phone" autocomplete="tel" inputmode="tel" value="<?= $value('phone') ?>" maxlength="40" placeholder="+359 88 123 4567" required<?= $error('phone') ? ' aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
                        <?php if ($error('phone')): ?><small id="phone-error"><?= $escape($error('phone')) ?></small><?php endif; ?>
                    </label>
                </div>
            </section>

            <section class="store-checkout-card" aria-labelledby="checkout-delivery-title" data-delivery-section>
                <div class="store-checkout-card-title">
                    <span aria-hidden="true">2</span>
                    <div>
                        <h2 id="checkout-delivery-title">Доставка</h2>
                        <p>Изберете къде искате да получите поръчката.</p>
                    </div>
                </div>
                <fieldset class="store-checkout-choice-group<?= $error('delivery_method') ? ' has-error' : '' ?>">
                    <legend class="store-sr-only">Начин на доставка</legend>
                    <div class="store-checkout-choices">
                        <label>
                            <input type="radio" name="delivery_method" value="address" <?= $delivery === 'address' ? 'checked' : '' ?> required>
                            <span class="store-checkout-choice-icon" aria-hidden="true">⌂</span>
                            <span><strong>До личен адрес</strong><small>Куриер до посочения адрес</small></span>
                            <i aria-hidden="true"></i>
                        </label>
                        <label>
                            <input type="radio" name="delivery_method" value="office" <?= $delivery === 'office' ? 'checked' : '' ?> required>
                            <span class="store-checkout-choice-icon" aria-hidden="true">▣</span>
                            <span><strong>До офис на куриер</strong><small>Вземане от удобен офис</small></span>
                            <i aria-hidden="true"></i>
                        </label>
                    </div>
                    <?php if ($error('delivery_method')): ?><small class="store-checkout-group-error"><?= $escape($error('delivery_method')) ?></small><?php endif; ?>
                </fieldset>
                <div class="store-checkout-fields">
                    <label class="<?= $error('address_line') ? 'has-error' : '' ?>">
                        <span data-address-label><?= $delivery === 'office' ? 'Офис на куриер' : 'Улица и номер' ?> <em>*</em></span>
                        <input type="text" name="address_line" autocomplete="<?= $delivery === 'office' ? 'off' : 'street-address' ?>" value="<?= $value('address_line') ?>" maxlength="191" placeholder="<?= $delivery === 'office' ? 'Напр. Еконт Център, офис 1234' : 'Улица, номер, вход, етаж и апартамент' ?>" required data-address-input<?= $error('address_line') ? ' aria-invalid="true" aria-describedby="address-error"' : '' ?>>
                        <span class="store-checkout-field-hint" data-address-hint><?= $delivery === 'office' ? 'Посочете куриер, име или код на офиса.' : 'Добавете вход, етаж и апартамент, ако са приложими.' ?></span>
                        <?php if ($error('address_line')): ?><small id="address-error"><?= $escape($error('address_line')) ?></small><?php endif; ?>
                    </label>
                    <div class="store-checkout-fields store-checkout-fields--city">
                        <label class="<?= $error('city') ? 'has-error' : '' ?>">
                            <span>Населено място <em>*</em></span>
                            <input type="text" name="city" autocomplete="address-level2" value="<?= $value('city') ?>" maxlength="100" required<?= $error('city') ? ' aria-invalid="true" aria-describedby="city-error"' : '' ?>>
                            <?php if ($error('city')): ?><small id="city-error"><?= $escape($error('city')) ?></small><?php endif; ?>
                        </label>
                        <label class="<?= $error('postal_code') ? 'has-error' : '' ?>">
                            <span>Пощенски код</span>
                            <input type="text" name="postal_code" autocomplete="postal-code" inputmode="numeric" value="<?= $value('postal_code') ?>" maxlength="16"<?= $error('postal_code') ? ' aria-invalid="true" aria-describedby="postal-code-error"' : '' ?>>
                            <?php if ($error('postal_code')): ?><small id="postal-code-error"><?= $escape($error('postal_code')) ?></small><?php endif; ?>
                        </label>
                    </div>
                    <label class="<?= $error('country') ? 'has-error' : '' ?>">
                        <span>Държава <em>*</em></span>
                        <input type="text" name="country" autocomplete="country-name" value="<?= $value('country') ?>" maxlength="80" required<?= $error('country') ? ' aria-invalid="true" aria-describedby="country-error"' : '' ?>>
                        <?php if ($error('country')): ?><small id="country-error"><?= $escape($error('country')) ?></small><?php endif; ?>
                    </label>
                </div>
            </section>

            <section class="store-checkout-card" aria-labelledby="checkout-payment-title">
                <div class="store-checkout-card-title">
                    <span aria-hidden="true">3</span>
                    <div>
                        <h2 id="checkout-payment-title">Плащане</h2>
                        <p>Изберете предпочитания начин на плащане.</p>
                    </div>
                </div>
                <fieldset class="store-checkout-choice-group<?= $error('payment_method') ? ' has-error' : '' ?>">
                    <legend class="store-sr-only">Начин на плащане</legend>
                    <div class="store-checkout-choices">
                        <label>
                            <input type="radio" name="payment_method" value="cash_on_delivery" <?= $payment === 'cash_on_delivery' ? 'checked' : '' ?> required>
                            <span class="store-checkout-choice-icon" aria-hidden="true">€</span>
                            <span><strong>Наложен платеж</strong><small>Плащате на куриера при получаване</small></span>
                            <i aria-hidden="true"></i>
                        </label>
                        <label>
                            <input type="radio" name="payment_method" value="bank_transfer" <?= $payment === 'bank_transfer' ? 'checked' : '' ?> required>
                            <span class="store-checkout-choice-icon" aria-hidden="true">↗</span>
                            <span><strong>Банков превод</strong><small>Ще получите данни за превода</small></span>
                            <i aria-hidden="true"></i>
                        </label>
                    </div>
                    <?php if ($error('payment_method')): ?><small class="store-checkout-group-error"><?= $escape($error('payment_method')) ?></small><?php endif; ?>
                </fieldset>
                <label class="store-checkout-notes<?= $error('notes') ? ' has-error' : '' ?>">
                    <span>Бележка към поръчката <small>(по желание)</small></span>
                    <textarea name="notes" maxlength="1000" rows="4" placeholder="Напр. удобен час за доставка" data-notes<?= $error('notes') ? ' aria-invalid="true" aria-describedby="notes-error"' : '' ?>><?= $value('notes') ?></textarea>
                    <span class="store-checkout-notes-meta">
                        <?php if ($error('notes')): ?><small id="notes-error"><?= $escape($error('notes')) ?></small><?php else: ?><small>Не въвеждайте чувствителни лични данни.</small><?php endif; ?>
                        <small><span data-notes-count><?= mb_strlen((string) ($form['notes'] ?? '')) ?></span>/1000</small>
                    </span>
                </label>
            </section>
        </div>

        <aside class="store-checkout-summary" aria-labelledby="checkout-summary-title">
            <div class="store-checkout-summary-head">
                <div>
                    <p>Преглед</p>
                    <h2 id="checkout-summary-title">Вашата поръчка</h2>
                </div>
                <span><?= $itemCount ?> <?= $itemCount === 1 ? 'артикул' : 'артикула' ?></span>
            </div>
            <ul class="store-checkout-products">
                <?php foreach ($lines as $line): ?>
                    <li>
                        <a href="<?= $escape($line['href']) ?>" class="store-checkout-product-image">
                            <?php if (!empty($line['image'])): ?>
                                <img src="<?= $escape($line['image']) ?>" alt="<?= $escape($line['alt']) ?>" width="72" height="88">
                            <?php else: ?>
                                <span aria-hidden="true">B33</span>
                            <?php endif; ?>
                            <b aria-label="Количество: <?= (int) $line['qty'] ?>"><?= (int) $line['qty'] ?></b>
                        </a>
                        <div>
                            <a href="<?= $escape($line['href']) ?>"><?= $escape($line['name']) ?></a>
                            <?php if ((string) $line['options'] !== ''): ?><small><?= $escape($line['options']) ?></small><?php endif; ?>
                            <?php foreach (($line['notes'] ?? []) as $note): ?><small><?= $escape($note) ?></small><?php endforeach; ?>
                            <span><?= (int) $line['qty'] ?> × <?= $escape(ProductPage::money($line['price'])) ?></span>
                        </div>
                        <strong><?= $escape(ProductPage::money($line['total'])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="store-checkout-totals">
                <p><span>Продукти</span><strong><?= $escape($total) ?></strong></p>
                <p><span>Доставка</span><strong>Уточнява се при обработка</strong></p>
                <p><span>Общо за продуктите</span><strong><?= $escape($total) ?></strong></p>
            </div>
            <p class="store-checkout-delivery-note">
                <span aria-hidden="true">i</span>
                Цената за доставка не е включена и се потвърждава според избрания адрес или офис.
            </p>
            <button type="submit" data-checkout-submit>
                <span data-submit-label>Завърши поръчката</span>
                <span data-submit-loading hidden><i aria-hidden="true"></i> Изпращане…</span>
                <span aria-hidden="true">→</span>
            </button>
            <ul class="store-checkout-assurances" aria-label="Информация за поръчката">
                <li><span aria-hidden="true">✓</span> Данните се използват само за поръчката</li>
                <li><span aria-hidden="true">✓</span> Преглед преди окончателно изпращане</li>
            </ul>
            <a href="/cart" class="store-checkout-edit-cart">Промени продуктите в количката</a>
        </aside>
    </form>
</section>
