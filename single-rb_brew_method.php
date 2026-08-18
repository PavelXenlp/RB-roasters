<?php
/**
 * Single brewing method template.
 *
 * @package ROASTBERRY_THEME
 */

get_header();

while (have_posts()): the_post();
    $method_id = get_the_ID();
    $image = rb_get_brew_method_image($method_id);
    $recommended_ids = array_values(array_filter(array_map('intval', (array) get_post_meta($method_id, 'rb_brew_products', true))));
    ?>
    <div class="brew-method-page">
        <section class="brew-method-hero">
            <figure class="brew-method-hero__media">
                <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>">
            </figure>
            <div class="brew-method-hero__copy">
                <span class="eyebrow">Способ приготовления</span>
                <h1><?= esc_html(get_the_title()) ?></h1>
                <?php if (has_excerpt()): ?><p class="lead"><?= esc_html(get_the_excerpt()) ?></p><?php endif; ?>
                <?php if (trim((string) get_the_content()) !== ''): ?>
                    <div class="brew-method-content"><?php the_content(); ?></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section brew-recommendations">
            <?php section_title('Кофе для этого рецепта', 'Рекомендуем попробовать'); ?>
            <?php if ($recommended_ids): ?>
                <?php
                $products_query = new WP_Query([
                    'post_type' => 'rb_product',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'post__in' => $recommended_ids,
                    'orderby' => 'post__in',
                ]);
                ?>
                <?php if ($products_query->have_posts()): ?>
                    <div class="product-grid product-grid--recommended">
                        <?php while ($products_query->have_posts()): $products_query->the_post(); ?>
                            <?php rb_product_card_from_post(get_the_ID()); ?>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="brew-recommendations__empty">Подходящие лоты скоро появятся в этой подборке.</p>
            <?php endif; ?>
        </section>
    </div>
<?php endwhile; ?>

<?php get_footer(); ?>
