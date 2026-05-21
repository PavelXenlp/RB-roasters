<section class="page-head">
        <span>Новости и акции</span>
        <h1>Жизнь обжарки</h1>
    </section>
    <section class="section"><div class="news-grid news-grid--full"><?php foreach ($news as $item): ?>
        <article class="news-card"><img src="<?= $item['image'] ?>" alt=""><div><span><?= $item['date'] ?></span><h3><?= $item['title'] ?></h3><p><?= $item['text'] ?></p></div></article>
    <?php endforeach; ?></div></section>
