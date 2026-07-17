<?php
/**
 * Template Name: Корзина
 *
 * @package ROASTBERRY_THEME
 */

get_header();

$cart = rb_get_cart();
$cart_total = rb_cart_total();
$current_user = wp_get_current_user();
$user_phone = is_user_logged_in() ? rb_format_phone((string) get_user_meta($current_user->ID, 'rb_phone', true)) : '';
?>

<section class="cart-page">
    <div class="cart-items-panel">
        <h1>Корзина</h1>

        <?php if (!empty($_GET['added'])): ?>
            <p class="cart-notice">Товар добавлен в корзину.</p>
        <?php endif; ?>

        <?php if ($cart): ?>
            <form method="post" class="cart-list">
                <input type="hidden" name="rb_action" value="update_cart">
                <?php wp_nonce_field('rb_update_cart', 'rb_cart_update_nonce'); ?>

                <?php foreach ($cart as $key => $item): ?>
                    <article class="cart-item">
                        <a href="<?= esc_url($item['url']) ?>" class="cart-item__image">
                            <img src="<?= esc_url($item['image']) ?>" alt="">
                        </a>
                        <div>
                            <a class="cart-item__title" href="<?= esc_url($item['url']) ?>"><?= esc_html($item['title']) ?></a>
                            <p><?= esc_html($item['size']) ?>, <?= esc_html($item['grind']) ?></p>
                            <strong><?= esc_html(rb_format_price((int) $item['price'])) ?></strong>
                        </div>
                        <label class="cart-item__qty">
                            Кол-во
                            <input type="number" name="quantity[<?= esc_attr($key) ?>]" min="0" value="<?= esc_attr((int) $item['quantity']) ?>">
                        </label>
                        <button class="cart-remove" type="submit" name="remove_key" value="<?= esc_attr($key) ?>">Удалить</button>
                    </article>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <strong>Итого: <?= esc_html(rb_format_price($cart_total)) ?></strong>
                    <button class="button button--small" type="submit">Обновить корзину</button>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <h2>Корзина пуста :(</h2>
                <p>Выбирайте любимые лоты кофе в зернах или аксессуары для приготовления.</p>
                <a class="button" href="<?= esc_url(route_url('catalog')) ?>">В каталог</a>
            </div>
        <?php endif; ?>
    </div>

    <form class="checkout-panel" method="post">
        <h2>Оформление заказа</h2>
        <input type="hidden" name="rb_action" value="order">
        <?php wp_nonce_field('rb_order', 'rb_order_nonce'); ?>
        <label>Способ доставки
            <?php rb_custom_select('rb_delivery_method', [
                'СДЭК за счет получателя' => 'СДЭК за счет получателя',
                'Самовывоз из кофейни' => 'Самовывоз из кофейни',
                'Самовывоз с производства (-5%)' => 'Самовывоз с производства (-5%)',
            ], 'СДЭК за счет получателя', 'Способ доставки'); ?>
        </label>
        <label>Точка самовывоза
            <?php rb_custom_select('rb_pickup_point', [
                'Не выбрано' => 'Не выбрано',
                'Революции, 24' => 'Революции, 24',
                'Ленина, 68' => 'Ленина, 68',
                'Деревообделочная, 8к6' => 'Деревообделочная, 8к6',
            ], 'Не выбрано', 'Точка самовывоза'); ?>
        </label>
        <label>ФИО<input name="rb_customer_name" type="text" value="<?= esc_attr(is_user_logged_in() ? $current_user->display_name : '') ?>" placeholder="Иван Иванов" required></label>
        <label>Телефон<input name="rb_customer_phone" type="tel" inputmode="tel" value="<?= esc_attr($user_phone) ?>" placeholder="+7 (___) ___-__-__" required></label>
        <label>Почта<input name="rb_customer_email" type="email" value="<?= esc_attr(is_user_logged_in() ? $current_user->user_email : '') ?>" placeholder="mail@example.com"></label>
        <label>Промокод<input name="rb_promocode" type="text"></label>
        <label>Сумма<input name="rb_order_total" type="text" value="<?= esc_attr(rb_format_price($cart_total)) ?>" readonly></label>
        <button class="button" type="submit" <?= $cart ? '' : 'disabled' ?>>Оформить заказ</button>
    </form>
</section>

<?php get_footer(); ?>
