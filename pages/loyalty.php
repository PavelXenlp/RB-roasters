<section class="page-head page-head--green">
        <span>Программа лояльности</span>
        <h1>Покупайте кофе и получайте постоянные привилегии</h1>
        <p>Скидка появляется после первого заказа и растет вместе с общей суммой покупок.</p>
    </section>
    <section class="section loyalty">
        <?php foreach ([['3%', 'после первого заказа'], ['5%', 'от 10000 ₽'], ['10%', 'от 20000 ₽'], ['15%', 'от 50000 ₽']] as [$percent, $condition]): ?>
            <article><strong><?= $percent ?></strong><span><?= $condition ?></span></article>
        <?php endforeach; ?>
        <p>Скидка по программе лояльности суммируется со скидкой 5% при самовывозе с производства.</p>
    </section>
