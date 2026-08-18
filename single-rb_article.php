<?php
/**
 * Single article template.
 *
 * @package ROASTBERRY_THEME
 */

get_header();

while (have_posts()): the_post();
    $article_id = get_the_ID();
    $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
    $related_articles = new WP_Query([
        'post_type' => 'rb_article',
        'post_status' => 'publish',
        'posts_per_page' => 4,
        'post__not_in' => [$article_id],
        'ignore_sticky_posts' => true,
    ]);
    ?>
    <article class="article-page">
        <section class="article-intro<?= $image ? '' : ' article-intro--no-image' ?>">
            <header class="article-intro__copy">
                <span><?= esc_html(get_the_date('j F Y')) ?></span>
                <h1><?= esc_html(get_the_title()) ?></h1>
                <?php if (has_excerpt()): ?><p><?= esc_html(get_the_excerpt()) ?></p><?php endif; ?>
            </header>

            <?php if ($image): ?>
                <figure class="article-intro__media">
                    <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>">
                </figure>
            <?php endif; ?>
        </section>

        <div class="section article-layout">
            <section class="article-content">
                <?php the_content(); ?>
            </section>

            <aside class="article-sidebar" aria-labelledby="related-articles-title">
                <span>Читайте также</span>
                <h2 id="related-articles-title">Другие новости</h2>

                <div class="article-sidebar__list">
                    <?php if ($related_articles->have_posts()): ?>
                        <?php while ($related_articles->have_posts()): $related_articles->the_post(); ?>
                            <?php $related_image = get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>
                            <a class="article-sidebar__item<?= $related_image ? '' : ' article-sidebar__item--text' ?>" href="<?= esc_url(get_permalink()) ?>">
                                <?php if ($related_image): ?>
                                    <img src="<?= esc_url($related_image) ?>" alt="" loading="lazy">
                                <?php endif; ?>
                                <span>
                                    <small><?= esc_html(get_the_date('j F')) ?></small>
                                    <strong><?= esc_html(get_the_title()) ?></strong>
                                </span>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>Других публикаций пока нет.</p>
                    <?php endif; ?>
                </div>

                <a class="article-sidebar__all" href="<?= esc_url(route_url('news')) ?>">Все новости</a>
            </aside>
        </div>
    </article>
    <?php wp_reset_postdata(); ?>
<?php endwhile; ?>

<?php get_footer(); ?>
