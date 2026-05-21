<section class="page-head">
        <span>Розничный магазин</span>
        <h1>Каталог кофе для дома</h1>
        <p>Лаконичная витрина с плотной сеткой карточек. В WordPress это место логично подключать к WooCommerce и 1С.</p>
    </section>
    <section class="section section--tight">
        <div class="category-row">
            <?php foreach ($categories as $category): ?><a href="#products"><?= htmlspecialchars($category) ?></a><?php endforeach; ?>
        </div>
        <div class="product-grid" id="products">
            <?php foreach ($products as $product) product_card($product); ?>
        </div>
    </section>
