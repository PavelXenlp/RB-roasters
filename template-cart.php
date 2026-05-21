<?php
/**
 * Template Name: Корзина
 *
 * @package ROASTBERRY_THEME
 */

get_header();
?>

<section class="cart-page">
    <div class="empty-cart">
        <h1>Корзина</h1>
        <p>На этом этапе корзина подготовлена как кастомная форма заказа. После добавления JS-корзины сюда будут подставляться выбранные товары.</p>
        <a class="button" href="<?= esc_url(route_url('catalog')) ?>">В каталог</a>
    </div>

    <form class="checkout-panel" method="post">
        <h2>Оформление заказа</h2>
        <input type="hidden" name="rb_action" value="order">
        <?php wp_nonce_field('rb_order', 'rb_order_nonce'); ?>
        <label>Позиции заказа
            <textarea name="rb_order_items" rows="4" placeholder="Mexico Chiapas, 200 г, зерно, 1 шт."></textarea>
        </label>
        <label>Способ доставки
            <select name="rb_delivery_method">
                <option>СДЭК за счет получателя</option>
                <option>Самовывоз из кофейни</option>
                <option>Самовывоз с производства (-5%)</option>
            </select>
        </label>
        <label>Точка самовывоза
            <select name="rb_pickup_point">
                <option>Не выбрано</option>
                <option>Революции, 24</option>
                <option>Ленина, 68</option>
                <option>Деревообделочная, 8к6</option>
            </select>
        </label>
        <label>ФИО<input name="rb_customer_name" type="text" placeholder="Иван Иванов" required></label>
        <label>Телефон<input name="rb_customer_phone" type="tel" placeholder="+7" required></label>
        <label>Почта<input name="rb_customer_email" type="email" placeholder="mail@example.com"></label>
        <label>Промокод<input name="rb_promocode" type="text"></label>
        <label>Сумма<input name="rb_order_total" type="text" placeholder="Будет рассчитана после подключения корзины"></label>
        <button class="button" type="submit">Оформить заказ</button>
    </form>
</section>

<?php get_footer(); ?>
