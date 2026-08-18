<?php
/**
 * Single training template.
 *
 * @package ROASTBERRY_THEME
 */

get_header();

while (have_posts()): the_post();
    $duration = get_post_meta(get_the_ID(), 'rb_training_duration', true);
    $price = get_post_meta(get_the_ID(), 'rb_training_price', true);
    $link = get_post_meta(get_the_ID(), 'rb_training_link', true) ?: (rb_contacts()['trainer'] ?? '#');
    $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
    ?>
    <section class="product-page">
        <div class="product-gallery">
            <?php if ($image): ?><img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>"><?php endif; ?>
        </div>
        <div class="product-info">
            <span class="eyebrow">Тренинг-центр</span>
            <h1><?= esc_html(get_the_title()) ?></h1>
            <?php if ($duration || $price): ?><p class="lead"><?= esc_html(trim($duration . ' / ' . $price, ' /')) ?></p><?php endif; ?>
            <div class="copy-block"><?php the_content(); ?></div>
            <a class="button" href="<?= esc_url($link) ?>" target="_blank" rel="noopener noreferrer">Узнать подробности</a>
        </div>
    </section>
<?php endwhile; ?>

<?php get_footer(); ?>
