<?php
    $product = $products[0];
    ?>
    <section class="product-page">
        <div class="product-gallery">
            <img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['title']) ?>">
        </div>
        <div class="product-info">
            <span class="eyebrow">Кофе под фильтр</span>
            <h1><?= htmlspecialchars($product['title']) ?></h1>
            <p class="lead"><?= htmlspecialchars($product['description']) ?></p>
            <dl class="specs">
                <div><dt>Способ обработки</dt><dd><?= $product['process'] ?></dd></div>
                <div><dt>Степень обжарки</dt><dd><?= $product['roast'] ?></dd></div>
                <div><dt>Страна</dt><dd><?= $product['country'] ?></dd></div>
                <div><dt>Регион</dt><dd><?= $product['region'] ?></dd></div>
                <div><dt>Высота</dt><dd><?= $product['height'] ?></dd></div>
                <div><dt>Разновидность</dt><dd><?= $product['variety'] ?></dd></div>
            </dl>
            <form class="order-form" action="<?= route_url('cart') ?>">
                <label>Размер
                    <select name="size"><option>200 г - <?= $product['price_200'] ?></option><option>1 кг - <?= $product['price_1000'] ?></option></select>
                </label>
                <label>Помол
                    <select name="grind"><option>В зернах</option><option>Фильтр</option><option>Турка</option><option>Френч-пресс</option><option>Эспрессо</option><option>Гейзерная кофеварка</option><option>AeroPress</option><option>Воронка</option></select>
                </label>
                <label>Количество
                    <input type="number" min="1" value="1">
                </label>
                <button class="button" type="submit">Заказать</button>
            </form>
        </div>
    </section>
