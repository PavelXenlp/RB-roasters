<section class="cart-page">
    <div class="empty-cart">
        <h1>Корзина пуста :(</h1>
        <p>Выбирайте любимые лоты кофе в зернах или аксессуары для приготовления.</p>
        <a class="button" href="<?= route_url('catalog') ?>">В каталог</a>
    </div>
    <form class="checkout-panel">
        <h2>Оформление заказа</h2>
        <label>Способ доставки
            <?php rb_custom_select('delivery', [
                'СДЭК за счет получателя' => 'СДЭК за счет получателя',
                'Самовывоз из кофейни' => 'Самовывоз из кофейни',
                'Самовывоз с производства (-5%)' => 'Самовывоз с производства (-5%)',
            ], 'СДЭК за счет получателя', 'Способ доставки'); ?>
        </label>
        <label>ФИО<input type="text" placeholder="Иван Иванов"></label>
        <label>Телефон<input type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" placeholder="+7 (___) ___-__-__" required></label>
        <label>Почта<input type="email" placeholder="mail@example.com"></label>
        <label>Промокод<input type="text"></label>
        <?php rb_render_legal_consents(true); ?>
    </form>
</section>
