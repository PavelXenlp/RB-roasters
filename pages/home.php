<section class="hero-grid" aria-label="Основные разделы">
        <?php foreach ($heroCards as $card): ?>
            <a class="hero-card" href="<?= $card['href'] ?>">
                <img src="<?= $card['image'] ?>" alt="">
                <span><?= htmlspecialchars($card['title']) ?></span>
                <b>Подробнее</b>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="section about-section" id="about">
        <div class="copy-block">
            <span class="eyebrow">О нас</span>
            <h1>Roastberry Coffee Roasters — локальный обжарщик из Перми, за которым стоит современный подход к обжарке кофе и люди, которые любят своё дело</h1>
            <p>Кофе - это не только тонизирующий эффект, а прежде всего философия и богатство вкуса. Откроем для вас многогранность мира кофе: горький, шоколадный, ореховый, сладкий.</p>
            <p>Мы знаем, как бережно раскрыть потенциал зерна. Наша миссия - донести до каждого любителя кофе вкус хорошего качества без переплаты за бренд и без стереотипов.</p>
        </div>
        <div class="about-mosaic" aria-label="Фотографии команды и производства Roastberry Coffee Roasters">
            <figure class="about-mosaic__item about-mosaic__item--team">
                <img src="<?= esc_url(rb_asset_url('img/about_us/1.webp')) ?>" alt="Команда Roastberry Coffee Roasters на производстве" loading="lazy">
            </figure>
            <figure class="about-mosaic__item about-mosaic__item--process">
                <img src="<?= esc_url(rb_asset_url('img/about_us/3.webp')) ?>" alt="Свежая обжарка кофе в ростере" loading="lazy">
            </figure>
            <figure class="about-mosaic__item about-mosaic__item--roaster">
                <img src="<?= esc_url(rb_asset_url('img/about_us/4.webp')) ?>" alt="Обжарщик Roastberry Coffee Roasters у ростера" loading="lazy">
            </figure>
            <figure class="about-mosaic__item about-mosaic__item--award">
                <img src="<?= esc_url(rb_asset_url('img/about_us/2.webp')) ?>" alt="Дипломы и награды Roastberry Coffee Roasters" loading="lazy">
            </figure>
        </div>
    </section>

    <section class="beans-band">
        <div class="section-title section-title--light">
            <span>
                <img src="<?= esc_url(rb_asset_url('img/brand_name.svg')) ?>" alt="">
            </span>
            <h2>Преимущества обжарки</h2>
        </div>
        <div class="benefits">
            <article><b>Входим в ТОП-3 обжарщиков страны</b><p>По итогам национальной премии «Обжарщик года-2026»</p></article>
            <article><b>Кофе на любой вкус</b><p>Для вас как базовый кофе, так и уникальные сорта.</p></article>
            <article><b>Качественное оборудование</b><p>Обжариваем на ростере Giesen W15A и Trobrat.</p></article>
            <article><b>Команда профессионалов</b><p>Сертифицированные SCA специалисты рядом на каждом этапе.</p></article>
            <article><b>Прямые поставщики</b><p>Сотрудничаем с надежными импортерами зеленого кофе.</p></article>
        </div>
    </section>

    <section class="section split-section home-production">
        <div class="photo-collage">
            <img src="/wp-content/themes/theme/assets/img/IMG_0972_1.jpg" alt="">
            <img src="/wp-content/themes/theme/assets/img/IMG_1192_1.jpg" alt="">
            <img src="/wp-content/themes/theme/assets/img/IMG_1352_1.jpg" alt="">
            <img src="/wp-content/themes/theme/assets/img/IMG_1561_1.jpg" alt="">
        </div>
        <div class="copy-block">
            <span class="eyebrow">Производство</span>
            <h2>Стабильная обжарка и контроль качества</h2>
            <p>Цикл производства доведен до уровня точных обжарщиков с контролем качества. Жарим кофе на ростерах Giesen W15A и Trobrat, используем ПО Cropster для стабильности профилей.</p>
            <p>С заботой о вас отбираем сырье у импортеров, сотрудничаем только с надежными поставщиками и поддерживаем ассортимент для розницы и бизнеса.</p>
            <a class="button button--outline" href="<?= route_url('catalog') ?>">В каталог</a>
        </div>
    </section>

    <section class="section home-news" id="news">
        <?php section_title('Новости и акции', 'Свежие события Roastberry Coffee Roasters'); ?>
        <div class="news-grid news-grid--featured">
            <?php
            $articles_query = new WP_Query([
                'post_type' => 'rb_article',
                'post_status' => 'publish',
                'posts_per_page' => 5,
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

    <section class="section brew-section">
        <?php section_title('Способы приготовления', 'Выберите свой рецепт', 'Подберите удобный способ заваривания и кофе под него.'); ?>
        <div class="brew-grid">
            <?php
            $brew_query = new WP_Query([
                'post_type' => 'rb_brew_method',
                'post_status' => 'publish',
                'posts_per_page' => 5,
                'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
            ]);
            ?>
            <?php if ($brew_query->have_posts()): ?>
                <?php while ($brew_query->have_posts()): $brew_query->the_post(); ?>
                    <?php $brew_icon_name = (string) get_post_meta(get_the_ID(), 'rb_brew_icon', true); ?>
                    <a href="<?= esc_url(get_permalink()) ?>" class="brew-item">
                        <span class="brew-item__icon"><?= brew_icon($brew_icon_name) ?></span>
                        <strong><?= esc_html(get_the_title()) ?></strong>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else: ?>
                <?php foreach ($brewMethods as $method): ?>
                    <a href="<?= esc_url(route_url('catalog')) ?>" class="brew-item">
                        <span class="brew-item__icon"><?= brew_icon($method['icon']) ?></span>
                        <strong><?= esc_html($method['title']) ?></strong>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
