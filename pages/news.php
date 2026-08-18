<section class="page-head">
    <span>Новости и акции</span>
    <h1>ЖИЗНЬ ROASTBERRY COFFEE ROASTERS</h1>
</section>
<section class="section">
    <div class="news-grid news-grid--full">
        <?php
        $articles_query = new WP_Query([
            'post_type' => 'rb_article',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);
        ?>
        <?php if ($articles_query->have_posts()): ?>
            <?php while ($articles_query->have_posts()): $articles_query->the_post(); ?>
                <?php rb_article_card_from_post(get_the_ID()); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else: ?>
            <?php foreach ($news as $item): ?>
                <article class="news-card">
                    <img src="<?= $item['image'] ?>" alt="">
                    <div>
                        <span><?= htmlspecialchars($item['date']) ?></span>
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['text']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
