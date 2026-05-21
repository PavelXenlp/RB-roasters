<?php
/**
 * Template Name: Каталог
 *
 * @package ROASTBERRY_THEME
 */

get_header();

$terms = get_terms([
    'taxonomy' => 'rb_product_category',
    'hide_empty' => false,
]);

$products_query = new WP_Query([
    'post_type' => 'rb_product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'menu_order title',
    'order' => 'ASC',
]);
?>

<section class="page-head">
    <span>Розничный магазин</span>
    <h1>Каталог кофе для дома</h1>
    <p>Карточки товаров управляются из административной панели WordPress: Товары -> Добавить товар.</p>
</section>

<section class="section section--tight">
    <div class="category-row">
        <?php if (!is_wp_error($terms) && $terms): ?>
            <?php foreach ($terms as $term): ?>
                <a href="<?= esc_url(get_term_link($term)) ?>"><?= esc_html($term->name) ?></a>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($categories as $category): ?><a href="#products"><?= esc_html($category) ?></a><?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="product-grid" id="products">
        <?php if ($products_query->have_posts()): ?>
            <?php while ($products_query->have_posts()): $products_query->the_post(); ?>
                <?php rb_product_card_from_post(get_the_ID()); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else: ?>
            <?php foreach ($products as $product) product_card($product); ?>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
