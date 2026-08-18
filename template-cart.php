<?php
/**
 * Template Name: Корзина
 *
 * @package ROASTBERRY_THEME
 */

get_header();

$checkout_error = rb_pull_checkout_error();
$payment_notice = rb_pull_payment_notice();
$cart = rb_get_cart();
$cart_refresh = rb_refresh_cart_from_catalog($cart, true);
if (!is_wp_error($cart_refresh)) {
    $cart = $cart_refresh['cart'];
    rb_save_cart($cart);
    if (!$checkout_error && $cart_refresh['notices']) {
        $checkout_error = implode(' ', $cart_refresh['notices']);
    }
} elseif (!$checkout_error) {
    $checkout_error = $cart_refresh->get_error_message();
}
$cart_total = rb_cart_total();
$current_user = wp_get_current_user();
$user_phone = is_user_logged_in() ? rb_format_phone((string) get_user_meta($current_user->ID, 'rb_phone', true)) : '';
$cdek_configured = rb_cdek_is_configured();
$loyalty_data = rb_user_loyalty_data(get_current_user_id());
$cart_items_count = array_sum(array_map(static fn(array $item): int => max(1, (int) ($item['quantity'] ?? 1)), $cart));
$cart_items_modulo = $cart_items_count % 100;
$cart_items_word = ($cart_items_count % 10 === 1 && $cart_items_modulo !== 11)
    ? 'товар'
    : (($cart_items_count % 10 >= 2 && $cart_items_count % 10 <= 4 && ($cart_items_modulo < 12 || $cart_items_modulo > 14)) ? 'товара' : 'товаров');
?>

<section class="cart-page<?= $cart ? '' : ' cart-page--empty' ?>">
    <div class="cart-items-panel">
        <div class="cart-panel__head">
            <div>
                <span class="eyebrow">Ваш заказ</span>
                <h1>Корзина</h1>
            </div>
            <?php if ($cart): ?>
                <span class="cart-count" data-cart-items-count><?= esc_html($cart_items_count) ?> <?= esc_html($cart_items_word) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($checkout_error): ?>
            <p class="checkout-error" role="alert"><?= esc_html($checkout_error) ?></p>
        <?php endif; ?>

        <?php if (!empty($_GET['added'])): ?>
            <p class="cart-notice">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                Товар добавлен в корзину
            </p>
        <?php endif; ?>

        <?php if ($cart): ?>
            <form method="post" class="cart-list" data-cart-form>
                <input type="hidden" name="rb_action" value="update_cart">
                <?php wp_nonce_field('rb_update_cart', 'rb_cart_update_nonce'); ?>

                <?php foreach ($cart as $key => $item): ?>
                    <?php $item_stock = rb_product_stock_by_size((int) $item['product_id'], rb_cart_item_size($item)); ?>
                    <article class="cart-item" data-cart-item data-cart-key="<?= esc_attr($key) ?>" data-unit-price="<?= esc_attr((int) $item['price']) ?>">
                        <a href="<?= esc_url($item['url']) ?>" class="cart-item__image">
                            <img src="<?= esc_url($item['image']) ?>" alt="">
                        </a>
                        <div class="cart-item__body">
                            <a class="cart-item__title" href="<?= esc_url($item['url']) ?>"><?= esc_html($item['title']) ?></a>
                            <p><?= esc_html($item['size']) ?>, <?= esc_html($item['grind']) ?></p>
                            <strong class="cart-item__price" data-cart-line-total><?= esc_html(rb_format_price((int) $item['price'] * (int) $item['quantity'])) ?></strong>
                        </div>
                        <div class="cart-item__actions">
                            <div class="quantity-control">
                                <button type="button" data-quantity-change="-1" aria-label="Уменьшить количество">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/></svg>
                                </button>
                                <input type="number" name="quantity[<?= esc_attr($key) ?>]" min="1"<?= $item_stock !== null ? ' max="' . esc_attr((string) max(1, $item_stock)) . '"' : '' ?> value="<?= esc_attr((int) $item['quantity']) ?>" aria-label="Количество товара">
                                <button type="button" data-quantity-change="1" aria-label="Увеличить количество">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                            </div>
                            <button class="cart-remove" type="submit" name="remove_key" value="<?= esc_attr($key) ?>" aria-label="Удалить <?= esc_attr($item['title']) ?>" title="Удалить товар">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 10v6M14 10v6"/></svg>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <div>
                        <span>Сумма товаров</span>
                        <strong data-cart-subtotal><?= esc_html(rb_format_price($cart_total)) ?></strong>
                        <small class="cart-save-status" data-cart-save-status aria-live="polite"></small>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <?php if ($payment_notice): ?>
                    <span class="eyebrow"><?= $payment_notice['status'] === 'success' ? 'Оплата принята' : 'Статус уточняется' ?></span>
                    <h2>Заказ №<?= esc_html((string) $payment_notice['order_id']) ?> оформлен</h2>
                    <p><?= $payment_notice['status'] === 'success' ? 'Спасибо! Заказ передан в обработку.' : 'Мы проверяем результат оплаты. Статус заказа обновится автоматически.' ?></p>
                    <a class="button" href="<?= esc_url(is_user_logged_in() ? route_url('account') : route_url('catalog')) ?>"><?= is_user_logged_in() ? 'Мои заказы' : 'Вернуться в каталог' ?></a>
                <?php else: ?>
                    <h2>Корзина пуста :(</h2>
                    <p>Выбирайте любимые лоты кофе в зернах или аксессуары для приготовления.</p>
                    <a class="button" href="<?= esc_url(route_url('catalog')) ?>">В каталог</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($cart): ?>
    <form class="checkout-panel" method="post">
        <div class="checkout-panel__head">
            <span class="eyebrow">Последний шаг</span>
            <h2>Оформление заказа</h2>
            <p>Выберите доставку и укажите данные получателя.</p>
        </div>
        <input type="hidden" name="rb_action" value="order">
        <?php wp_nonce_field('rb_order', 'rb_order_nonce'); ?>

        <fieldset class="delivery-choice" data-delivery-choice data-cart-total="<?= esc_attr($cart_total) ?>" data-loyalty-percent="<?= esc_attr((string) $loyalty_data['percent']) ?>" data-discount-path="<?= esc_url(rest_url('rb/v1/discount-quote')) ?>" data-discount-nonce="<?= esc_attr(wp_create_nonce('rb_cart_discount')) ?>">
            <legend><span>1</span> Способ доставки</legend>
            <div class="delivery-choice__options">
                <label class="delivery-option">
                    <input type="radio" name="rb_delivery_method" value="pickup_cafe" checked>
                    <span class="delivery-option__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 9h18"/><path d="M5 9v11h14V9"/><path d="m4 4 1 5h14l1-5Z"/><path d="M9 13h6"/></svg>
                    </span>
                    <span><strong>Из кофейни</strong><small>Самовывоз</small></span>
                </label>
                <label class="delivery-option">
                    <input type="radio" name="rb_delivery_method" value="pickup_production">
                    <span class="delivery-option__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M5 21V9l5 3V9l5 3V4h4v17"/><path d="M8 17h1"/><path d="M12 17h1"/><path d="M16 17h1"/></svg>
                    </span>
                    <span><strong>С производства</strong><small>Самовывоз</small></span>
                </label>
                <label class="delivery-option delivery-option--cdek<?= $cdek_configured ? '' : ' is-disabled' ?>">
                    <input type="radio" name="rb_delivery_method" value="cdek" <?= $cdek_configured ? '' : 'disabled' ?>>
                    <span class="delivery-option__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M10 17h4V5H2v12h3"/><path d="M14 9h4l4 4v4h-3"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
                    </span>
                    <span><strong>СДЭК</strong><small><?= $cdek_configured ? 'До пункта выдачи' : 'Нужна настройка' ?></small></span>
                </label>
            </div>

            <div class="delivery-panel is-active" data-delivery-panel="pickup_cafe">
                <label>Точка самовывоза
                    <?php rb_custom_select('rb_cafe_pickup_point', [
                        'Революции, 24' => 'Революции, 24',
                        'Ленина, 68' => 'Ленина, 68',
                    ], 'Революции, 24', 'Точка самовывоза из кофейни'); ?>
                </label>
            </div>

            <div class="delivery-panel" data-delivery-panel="pickup_production">
                <div class="pickup-address">
                    <span>Адрес производства</span>
                    <strong>Пермь, ул. Деревообделочная, 8к6</strong>
                </div>
            </div>

            <div class="delivery-panel" data-delivery-panel="cdek">
                <?php if ($cdek_configured): ?>
                    <div
                        class="cdek-picker"
                        data-cdek-checkout
                        data-service-path="<?= esc_url(rest_url('rb/v1/cdek')) ?>"
                        data-cart-total="<?= esc_attr($cart_total) ?>"
                    >
                        <p class="cdek-server-note">
                            <strong>Доставка до пункта выдачи</strong>
                            Выберите город и удобный ПВЗ.
                        </p>
                        <div class="cdek-selection" data-cdek-selection hidden>
                            <span>Выбран пункт</span>
                            <strong data-cdek-office></strong>
                            <small data-cdek-details></small>
                            <button type="button" data-cdek-change>Изменить</button>
                        </div>
                        <div class="cdek-fallback" data-cdek-fallback>
                            <div class="cdek-fallback__head">
                                <strong>Выбор пункта выдачи</strong>
                            </div>
                            <label class="cdek-city-search">
                                Город доставки
                                <input type="search" autocomplete="off" placeholder="Начните вводить город" data-cdek-city-input>
                            </label>
                            <div class="cdek-city-results" data-cdek-city-results hidden></div>
                            <div class="cdek-office-select" data-cdek-office-select>
                                <button class="cdek-office-select__button" type="button" aria-expanded="false" data-cdek-office-button disabled>
                                    <span>Сначала выберите город</span>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div class="cdek-office-select__dropdown" data-cdek-office-list hidden></div>
                            </div>
                            <p class="cdek-fallback__status" data-cdek-fallback-status aria-live="polite"></p>
                        </div>
                        <input type="hidden" name="rb_cdek_office_code" data-cdek-field="office_code">
                        <input type="hidden" name="rb_cdek_city_code" data-cdek-field="city_code">
                        <input type="hidden" name="rb_cdek_tariff_code" data-cdek-field="tariff_code">
                        <input type="hidden" name="rb_cdek_tariff_name" data-cdek-field="tariff_name">
                        <input type="hidden" name="rb_delivery_cost" value="0" data-cdek-field="cost">
                    </div>
                <?php else: ?>
                    <p class="cdek-picker__hint">Доставка СДЭК временно недоступна. Настройки подключения еще не заполнены.</p>
                <?php endif; ?>
            </div>
        </fieldset>

        <fieldset class="checkout-recipient">
            <legend><span>2</span> Получатель</legend>
            <div class="checkout-fields">
                <label>ФИО<input name="rb_customer_name" type="text" value="<?= esc_attr(is_user_logged_in() ? $current_user->display_name : '') ?>" placeholder="Иван Иванов" required></label>
                <label>Телефон<input name="rb_customer_phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" value="<?= esc_attr($user_phone) ?>" placeholder="+7 (___) ___-__-__" required></label>
                <label><span class="field-label">Почта</span><input name="rb_customer_email" type="email" autocomplete="email" value="<?= esc_attr(is_user_logged_in() ? $current_user->user_email : '') ?>" placeholder="mail@example.com" required></label>
                <label><span class="field-label">Промокод</span><input name="rb_promocode" type="text" autocomplete="off" placeholder="Введите промокод"><small class="visually-hidden" data-promocode-status aria-live="polite"></small></label>
            </div>
        </fieldset>
        <fieldset class="payment-choice">
            <legend><span>3</span> Оплата</legend>
            <div class="payment-choice__options">
                <?php if (rb_yookassa_is_configured()): ?>
                <label class="payment-choice__card">
                    <input type="radio" name="rb_payment_method" value="yookassa" checked>
                    <span class="payment-choice__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h2"/></svg>
                    </span>
                    <span><strong>Онлайн на сайте</strong><small>Банковской картой через YooKassa</small></span>
                </label>
                <?php endif; ?>
                <label class="payment-choice__card">
                    <input type="radio" name="rb_payment_method" value="manager" <?= checked(!rb_yookassa_is_configured(), true, false) ?>>
                    <span class="payment-choice__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                    </span>
                    <span><strong>После оформления заказа</strong><small>Менеджер свяжется для подтверждения заказа</small></span>
                </label>
            </div>
        </fieldset>
        <?php rb_render_legal_consents(true); ?>
        <div class="checkout-total" aria-live="polite">
            <p><span>Товары</span><strong data-checkout-subtotal><?= esc_html(rb_format_price($cart_total)) ?></strong></p>
            <p data-discount-row<?= (int) $loyalty_data['percent'] > 0 ? '' : ' hidden' ?>><span>Скидка</span><strong data-discount-price><?= (int) $loyalty_data['percent'] > 0 ? '−' . esc_html(rb_format_price((int) round($cart_total * (int) $loyalty_data['percent'] / 100))) : '0 ₽' ?></strong></p>
            <p><span>Доставка</span><strong data-delivery-price>Бесплатно</strong></p>
            <p class="checkout-total__grand"><span>Итого</span><strong data-order-total><?= esc_html(rb_format_price($cart_total)) ?></strong></p>
        </div>
        <button class="button checkout-submit" type="submit" <?= $cart ? '' : 'disabled' ?>>
            Оформить заказ
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
