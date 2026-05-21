<?php
/**
 * Default fallback template.
 *
 * @package ROASTBERRY_THEME
 */

get_header();
?>

<section class="page-head">
    <span><?= esc_html(get_bloginfo('name')) ?></span>
    <h1><?= esc_html(is_singular() ? get_the_title() : wp_get_document_title()) ?></h1>
</section>

<section class="section">
    <?php if (have_posts()): ?>
        <?php while (have_posts()): the_post(); ?>
            <article <?php post_class('copy-block'); ?>>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
