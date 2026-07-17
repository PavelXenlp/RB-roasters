<?php
/**
 * Single article template.
 *
 * @package ROASTBERRY_THEME
 */

get_header();

while (have_posts()): the_post();
    $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
    ?>
    <article class="article-page">
        <header class="page-head">
            <span><?= esc_html(get_the_date('j F Y')) ?></span>
            <h1><?= esc_html(get_the_title()) ?></h1>
            <?php if (has_excerpt()): ?><p><?= esc_html(get_the_excerpt()) ?></p><?php endif; ?>
        </header>

        <?php if ($image): ?>
            <div class="article-hero">
                <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>">
            </div>
        <?php endif; ?>

        <section class="section article-content">
            <?php the_content(); ?>
        </section>
    </article>
<?php endwhile; ?>

<?php get_footer(); ?>
