<?php

declare(strict_types=1);

use Store\Services\ProductPage;

/** @var list<array<string, mixed>> $lines */
/** @var array<string, string> $form */
/** @var array<string, string> $errors */
/** @var string $total */
/** @var string $totalWeight */
/** @var string $csrf */
/** @var bool $acceptedTerms */
/** @var bool $wantsInvoice */
/** @var float $subtotalAmount */
/** @var float $freeShippingThreshold */

$lines = $lines ?? [];
$form = $form ?? [];
$errors = $errors ?? [];
$acceptedTerms = (bool) ($acceptedTerms ?? false);
$wantsInvoice = (bool) ($wantsInvoice ?? false);
$totalWeight = $totalWeight ?? 'Не е изчислено';
$company = require dirname(__DIR__, 2) . '/config/company.php';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$value = static fn (string $key): string => $escape($form[$key] ?? '');
$error = static fn (string $key): ?string => isset($errors[$key]) ? (string) $errors[$key] : null;
$delivery = in_array(($form['delivery_method'] ?? ''), ['address', 'office'], true)
    ? (string) $form['delivery_method']
    : 'address';
$payment = 'cash_on_delivery';
$itemCount = array_sum(array_map(static fn (array $line): int => (int) ($line['qty'] ?? 0), $lines));
$econt = (new \App\Services\Shipping\EcontConfigurationService())->publicConfiguration();
$shippingPayer = in_array(($form['shipping_payer'] ?? ''), ['receiver', 'sender'], true) ? (string) $form['shipping_payer'] : 'receiver';
$subtotalAmount = (float) ($subtotalAmount ?? 0);
$freeShippingThreshold = (float) ($freeShippingThreshold ?? 0);
$freeShippingEligible = $subtotalAmount > $freeShippingThreshold;
if (!$freeShippingEligible) $shippingPayer = 'receiver';
?>
<section class="store-checkout" data-checkout data-econt-locator-url="<?= $escape($econt['office_locator_url']) ?>" data-econt-environment="<?= $escape($econt['environment']) ?>">
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
                <input type="hidden" name="econt_office_code" value="<?= $value('econt_office_code') ?>" data-econt-office-code>
                <div class="store-checkout-card-title">
                    <span aria-hidden="true">2</span>
                    <div>
                        <h2 id="checkout-delivery-title">Доставка</h2>
                        <p>Изберете къде искате да получите поръчката.</p>
                    </div>
                </div>
                <fieldset class="store-checkout-choice-group<?= $error('delivery_method') ? ' has-error' : '' ?>">
                    <legend class="store-sr-only">Начин на доставка</legend>
                    <div class="store-checkout-choices store-checkout-choices--delivery">
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
                        <input type="text" name="address_line" autocomplete="<?= $delivery === 'address' ? 'street-address' : 'off' ?>" value="<?= $value('address_line') ?>" maxlength="191" placeholder="<?= $delivery === 'office' ? 'Изберете офис от картата' : 'Улица, номер, вход, етаж и апартамент' ?>" required data-address-input<?= $delivery !== 'address' ? ' readonly' : '' ?><?= $error('address_line') ? ' aria-invalid="true" aria-describedby="address-error"' : '' ?>>
                        <button type="button" class="store-checkout-office-button" data-econt-office-open<?= $delivery === 'address' ? ' hidden' : '' ?>>Избери офис на Еконт</button>
                        <span class="store-checkout-field-hint" data-address-hint><?= $delivery === 'office' ? 'Избраният офис определя точната цена на доставката.' : 'Добавете вход, етаж и апартамент, ако са приложими.' ?></span>
                        <?php if ($error('address_line')): ?><small id="address-error"><?= $escape($error('address_line')) ?></small><?php endif; ?>
                    </label>
                    <div class="store-checkout-fields store-checkout-fields--city">
                        <label class="<?= $error('city') ? 'has-error' : '' ?>">
                            <span>Населено място <em>*</em></span>
                            <input type="text" name="city" autocomplete="address-level2" value="<?= $value('city') ?>" maxlength="100" required<?= $error('city') ? ' aria-invalid="true" aria-describedby="city-error"' : '' ?>>
                            <?php if ($error('city')): ?><small id="city-error"><?= $escape($error('city')) ?></small><?php endif; ?>
                        </label>
                        <label class="<?= $error('postal_code') ? 'has-error' : '' ?>">
                            <span>Пощенски код <em>*</em></span>
                            <input type="text" name="postal_code" autocomplete="postal-code" inputmode="numeric" value="<?= $value('postal_code') ?>" maxlength="16" required<?= $error('postal_code') ? ' aria-invalid="true" aria-describedby="postal-code-error"' : '' ?>>
                            <?php if ($error('postal_code')): ?><small id="postal-code-error"><?= $escape($error('postal_code')) ?></small><?php endif; ?>
                        </label>
                    </div>
                    <label class="<?= $error('country') ? 'has-error' : '' ?>">
                        <span>Държава <em>*</em></span>
                        <input type="text" name="country" autocomplete="country-name" value="България" maxlength="80" readonly required<?= $error('country') ? ' aria-invalid="true" aria-describedby="country-error"' : '' ?>>
                        <?php if ($error('country')): ?><small id="country-error"><?= $escape($error('country')) ?></small><?php endif; ?>
                    </label>
                </div>
                <fieldset class="store-checkout-choice-group<?= $error('shipping_payer') ? ' has-error' : '' ?>">
                    <legend>Кой плаща доставката?</legend>
                    <div class="store-checkout-choices">
                        <label><input type="radio" name="shipping_payer" value="receiver" <?= $shippingPayer === 'receiver' ? 'checked' : '' ?> required><span class="store-checkout-choice-icon" aria-hidden="true">€</span><span><strong>Клиентът</strong><small>Цената се добавя към поръчката</small></span><i aria-hidden="true"></i></label>
                        <?php if ($freeShippingEligible): ?>
                            <label><input type="radio" name="shipping_payer" value="sender" <?= $shippingPayer === 'sender' ? 'checked' : '' ?> required><span class="store-checkout-choice-icon" aria-hidden="true">✓</span><span><strong>Магазинът</strong><small>Безплатна доставка за поръчка над <?= $escape(ProductPage::money($freeShippingThreshold)) ?></small></span><i aria-hidden="true"></i></label>
                        <?php endif; ?>
                    </div>
                    <?php if (!$freeShippingEligible): ?><p class="store-checkout-field-hint">Безплатната доставка се активира при стойност на продуктите над <?= $escape(ProductPage::money($freeShippingThreshold)) ?>.</p><?php endif; ?>
                    <?php if ($error('shipping_payer')): ?><small class="store-checkout-group-error"><?= $escape($error('shipping_payer')) ?></small><?php endif; ?>
                </fieldset>
            </section>

            <section class="store-checkout-card" aria-labelledby="checkout-invoice-title" data-invoice-section>
                <div class="store-checkout-card-title">
                    <span aria-hidden="true">3</span>
                    <div><h2 id="checkout-invoice-title">Фактура</h2><p>Фирмените данни се запазват към издадения документ.</p></div>
                </div>
                <label class="store-checkout-invoice-toggle">
                    <input type="checkbox" name="invoice_requested" value="1" <?= $wantsInvoice ? 'checked' : '' ?> data-invoice-toggle>
                    <span><strong>Желая фактура</strong><small>PDF фактурата ще бъде генерирана автоматично към поръчката.</small></span>
                </label>
                <div class="store-checkout-fields store-checkout-invoice-fields" data-invoice-fields<?= $wantsInvoice ? '' : ' hidden' ?>>
                    <label class="<?= $error('invoice_company') ? 'has-error' : '' ?>"><span>Фирма <em>*</em></span><input type="text" name="invoice_company" autocomplete="organization" maxlength="191" value="<?= $value('invoice_company') ?>"><?php if ($error('invoice_company')): ?><small><?= $escape($error('invoice_company')) ?></small><?php endif; ?></label>
                    <div class="store-checkout-fields store-checkout-fields--two">
                        <label class="<?= $error('invoice_eik') ? 'has-error' : '' ?>"><span>ЕИК <em>*</em></span><input type="text" name="invoice_eik" inputmode="numeric" maxlength="16" value="<?= $value('invoice_eik') ?>"><?php if ($error('invoice_eik')): ?><small><?= $escape($error('invoice_eik')) ?></small><?php endif; ?></label>
                        <label class="<?= $error('invoice_vat_number') ? 'has-error' : '' ?>"><span>ДДС №</span><input type="text" name="invoice_vat_number" maxlength="20" placeholder="BG123456789" value="<?= $value('invoice_vat_number') ?>"><?php if ($error('invoice_vat_number')): ?><small><?= $escape($error('invoice_vat_number')) ?></small><?php endif; ?></label>
                    </div>
                    <label class="<?= $error('invoice_address') ? 'has-error' : '' ?>"><span>Адрес <em>*</em></span><input type="text" name="invoice_address" maxlength="255" value="<?= $value('invoice_address') ?>"><?php if ($error('invoice_address')): ?><small><?= $escape($error('invoice_address')) ?></small><?php endif; ?></label>
                    <label class="<?= $error('invoice_mol') ? 'has-error' : '' ?>"><span>МОЛ <em>*</em></span><input type="text" name="invoice_mol" maxlength="191" value="<?= $value('invoice_mol') ?>"><?php if ($error('invoice_mol')): ?><small><?= $escape($error('invoice_mol')) ?></small><?php endif; ?></label>
                </div>
            </section>

            <section class="store-checkout-card" aria-labelledby="checkout-payment-title">
                <div class="store-checkout-card-title">
                    <span aria-hidden="true">4</span>
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
                            <small class="store-weight-detail">Тегло: <strong><?= $escape($line['weight']) ?></strong> · общо: <strong><?= $escape($line['total_weight']) ?></strong></small>
                            <span><?= (int) $line['qty'] ?> × <?= $escape(ProductPage::money($line['price'])) ?></span>
                        </div>
                        <strong><?= $escape(ProductPage::money($line['total'])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="store-checkout-totals">
                <p><span>Продукти</span><strong><?= $escape($total) ?></strong></p>
                <p><span>Общо тегло</span><strong><?= $escape($totalWeight) ?></strong></p>
                <p><span>Доставка с Еконт</span><strong data-shipping-price>Изчислява се…</strong></p>
                <p><span>Общо</span><strong data-checkout-grand-total data-products-total="<?= $escape($total) ?>"><?= $escape($total) ?></strong></p>
            </div>
            <p class="store-checkout-delivery-note<?= $error('shipping') ? ' has-error' : '' ?>" data-shipping-status>
                <span class="store-checkout-delivery-note-icon" aria-hidden="true">i</span>
                <span data-shipping-message><?= $error('shipping') ? $escape($error('shipping')) : 'Цената се изчислява в избраната Econt среда според адреса, теглото, стойността и плащането.' ?></span>
            </p>
            <button type="button" class="store-checkout-quote-button" data-shipping-quote>Изчисли доставката</button>
            <div class="store-checkout-legal<?= $error('accept_terms') ? ' has-error' : '' ?>">
                <label>
                    <input type="checkbox" name="accept_terms" value="1" <?= $acceptedTerms ? 'checked' : '' ?> required<?= $error('accept_terms') ? ' aria-invalid="true" aria-describedby="accept-terms-error"' : '' ?>>
                    <span>
                        Приемам
                        <a href="<?= $escape($company['terms_url'] ?? '/terms') ?>" target="_blank" rel="noopener">Общите условия</a>
                        и
                        <a href="<?= $escape($company['privacy_url'] ?? '/privacy') ?>" target="_blank" rel="noopener">Политиката за поверителност</a>.
                    </span>
                </label>
                <?php if ($error('accept_terms')): ?><small id="accept-terms-error"><?= $escape($error('accept_terms')) ?></small><?php endif; ?>
                <p>
                    За доставката се прилагат
                    <a href="https://www.econt.com/econt-express/common-terms" target="_blank" rel="noopener noreferrer">условията на Еконт</a>
                    и
                    <a href="https://www.econt.com/services/courier-services" target="_blank" rel="noopener noreferrer">актуалните цени на Еконт</a>.
                </p>
            </div>
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

    <dialog class="store-checkout-office-dialog" data-econt-office-dialog aria-labelledby="econt-office-title">
        <div class="store-checkout-office-dialog-head">
            <div>
                <p>Доставка с Еконт · demo режим</p>
                <h2 id="econt-office-title">Изберете офис на Еконт</h2>
            </div>
            <button type="button" aria-label="Затвори картата" data-econt-office-close>×</button>
        </div>
        <iframe title="Карта с офиси на Еконт" allow="geolocation" data-econt-office-frame></iframe>
    </dialog>
</section>
