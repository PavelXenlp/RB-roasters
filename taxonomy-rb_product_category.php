<?php
/**
 * Product category archive.
 *
 * @package ROASTBERRY_THEME
 */

get_header();
$term = get_queried_object();
$terms = rb_get_product_categories(false);
$has_products = have_posts();
$products_count = (int) ($GLOBALS['wp_query']->found_posts ?? 0);
?>

<section class="page-head page-head--catalog">
    <?php if ($term instanceof WP_Term): ?><?php rb_catalog_breadcrumbs($term); ?><?php endif; ?>
    <span>Категория</span>
    <h1><?= esc_html($term->name ?? 'Каталог') ?></h1>
    <?php if (!empty($term->description)): ?><p><?= esc_html($term->description) ?></p><?php endif; ?>
</section>

<section class="section section--tight catalog-section">
    <div class="catalog-tools">
        <?php rb_catalog_search(); ?>
        <?php rb_catalog_category_tabs($terms, $term instanceof WP_Term ? $term->term_id : 0); ?>
    </div>
    <div class="catalog-results-head">
        <h2><?= esc_html($term->name ?? 'Товары') ?></h2>
        <span>Товаров: <?= esc_html($products_count) ?></span>
    </div>
    <div class="product-grid">
        <?php if ($has_products): ?>
            <?php while (have_posts()): the_post(); ?>
                <?php rb_product_card_from_post(get_the_ID()); ?>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
    <?php if (!$has_products): ?>
        <div class="catalog-empty">
            <h2>В категории пока нет товаров</h2>
            <p>Посмотрите другие категории или вернитесь ко всему каталогу.</p>
            <a class="button button--outline" href="<?= esc_url(route_url('catalog')) ?>">Все товары</a>
        </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
