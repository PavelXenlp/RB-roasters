<?php
/**
 * Product category archive.
 *
 * @package ROASTBERRY_THEME
 */

get_header();
$term = get_queried_object();
?>

<section class="page-head">
    <span>Категория</span>
    <h1><?= esc_html($term->name ?? 'Каталог') ?></h1>
    <?php if (!empty($term->description)): ?><p><?= esc_html($term->description) ?></p><?php endif; ?>
</section>

<section class="section section--tight">
    <div class="product-grid">
        <?php if (have_posts()): ?>
            <?php while (have_posts()): the_post(); ?>
                <?php rb_product_card_from_post(get_the_ID()); ?>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
