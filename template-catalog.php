<?php
/**
 * Template Name: Каталог
 *
 * @package ROASTBERRY_THEME
 */

get_header();

global $categories, $products;

$terms = rb_get_product_categories(false);
$search_query = isset($_GET['catalog_search']) ? sanitize_text_field(wp_unslash($_GET['catalog_search'])) : '';

$products_query_args = [
    'post_type' => 'rb_product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'menu_order title',
    'order' => 'ASC',
];
if ($search_query !== '') {
    $products_query_args['s'] = $search_query;
    $products_query_args['orderby'] = 'relevance';
}
$products_query = new WP_Query($products_query_args);
$products_count = $products_query->post_count;
if ($products_count === 0 && $search_query === '') {
    $products_count = count($products);
}
?>

<section class="page-head page-head--catalog">
    <?php rb_catalog_breadcrumbs(); ?>
    <span>Розничный магазин</span>
    <h1>Каталог кофе для дома</h1>
    <p>Выбирайте кофе для фильтра, эспрессо, капсул, дрип-пакеты и аксессуары для приготовления.</p>
</section>

<section class="section section--tight catalog-section">
    <div class="catalog-tools">
        <?php rb_catalog_search($search_query); ?>
        <?php if ($terms): ?>
            <?php rb_catalog_category_tabs($terms); ?>
        <?php else: ?>
            <nav class="category-row" aria-label="Категории каталога">
                <a class="is-active" href="#products" aria-current="page">Все товары</a>
                <?php foreach ($categories as $category): ?><a href="#products"><?= esc_html($category) ?></a><?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>

    <div class="catalog-results-head">
        <h2><?= $search_query !== '' ? 'Результаты поиска' : 'Все товары' ?></h2>
        <span>Товаров: <?= esc_html($products_count) ?></span>
    </div>
    <div class="product-grid" id="products">
        <?php if ($products_query->have_posts()): ?>
            <?php while ($products_query->have_posts()): $products_query->the_post(); ?>
                <?php rb_product_card_from_post(get_the_ID()); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php elseif ($search_query === ''): ?>
            <?php foreach ($products as $product) product_card($product); ?>
        <?php endif; ?>
    </div>
    <?php if (!$products_query->have_posts() && $search_query !== ''): ?>
        <div class="catalog-empty">
            <h2>Ничего не найдено</h2>
            <p>Попробуйте изменить запрос или выбрать подходящую категорию.</p>
            <a class="button button--outline" href="<?= esc_url(route_url('catalog')) ?>">Сбросить поиск</a>
        </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
