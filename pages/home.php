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
            <h1>Кофе как точная работа со вкусом</h1>
            <p>Кофе - это не только тонизирующий эффект, а прежде всего философия и богатство вкуса. Откроем для вас многогранность мира кофе: горький, шоколадный, ореховый, сладкий.</p>
            <p>Мы знаем, как бережно раскрыть потенциал зерна. Наша миссия - донести до каждого любителя кофе вкус хорошего качества без переплаты за бренд и без стереотипов.</p>
        </div>
        <img class="about-photo" src="/assets/img/IMG_1159_1.jpg" alt="Обжарка кофе на производстве">
    </section>

    <section class="beans-band">
        <div class="section-title section-title--light">
            <span>RB Roasters</span>
            <h2>Преимущества обжарки</h2>
        </div>
        <div class="benefits">
            <article><b>Кофе на любой вкус</b><p>Для вас как базовый кофе, так и уникальные сорта.</p></article>
            <article><b>Качественное оборудование</b><p>Обжариваем на ростере Giesen W15A и Probat.</p></article>
            <article><b>Команда профессионалов</b><p>Сертифицированные SCA специалисты рядом на каждом этапе.</p></article>
            <article><b>Прямые поставщики</b><p>Сотрудничаем с надежными импортерами зеленого кофе.</p></article>
        </div>
    </section>

    <section class="section split-section">
        <div class="photo-collage">
            <img src="/assets/img/IMG_0972_1.jpg" alt="">
            <img src="/assets/img/IMG_1192_1.jpg" alt="">
            <img src="/assets/img/IMG_1352_1.jpg" alt="">
            <img src="/assets/img/IMG_1561_1.jpg" alt="">
        </div>
        <div class="copy-block">
            <span class="eyebrow">Производство</span>
            <h2>Стабильная обжарка и контроль качества</h2>
            <p>Цикл производства доведен до уровня точных обжарщиков с контролем качества. Жарим кофе на ростерах Giesen W15A и Probat, используем ПО Cropster для стабильности профилей.</p>
            <p>С заботой о вас отбираем сырье у импортеров, сотрудничаем только с надежными поставщиками и поддерживаем ассортимент для розницы и бизнеса.</p>
            <a class="button button--outline" href="<?= route_url('catalog') ?>">В каталог</a>
        </div>
    </section>

    <section class="section" id="news">
        <?php section_title('Новости и акции', 'Свежие события RB Roasters', 'На экране сразу несколько новостей, каждую можно раскрыть отдельной страницей при переносе в WordPress.'); ?>
        <div class="news-grid">
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
        </div>
    </section>

    <section class="section brew-section">
        <?php section_title('Способы приготовления', 'Выберите свой рецепт', 'Пока это заготовки страниц: позже добавим тексты, картинки и рекомендации из каталога.'); ?>
        <div class="brew-grid">
            <?php foreach ($brewMethods as $method): ?>
                <a href="<?= route_url('catalog') ?>" class="brew-item">
                    <span class="brew-item__icon"><?= brew_icon($method['icon']) ?></span>
                    <strong><?= htmlspecialchars($method['title']) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
