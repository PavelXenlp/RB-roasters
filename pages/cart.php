<section class="cart-page">
        <div class="empty-cart">
            <h1>Корзина пуста :(</h1>
            <p>Выбирайте любимые лоты кофе в зернах или аксессуары для приготовления.</p>
            <a class="button" href="<?= route_url('catalog') ?>">В каталог</a>
        </div>
        <form class="checkout-panel">
            <h2>Оформление заказа</h2>
            <label>Способ доставки<select><option>СДЭК за счет получателя</option><option>Самовывоз из кофейни</option><option>Самовывоз с производства (-5%)</option></select></label>
            <label>ФИО<input type="text" placeholder="Иван Иванов"></label>
            <label>Телефон<input type="tel" placeholder="+7"></label>
            <label>Почта<input type="email" placeholder="mail@example.com"></label>
            <label>Промокод<input type="text"></label>
        </form>
    </section>
