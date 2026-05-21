<section class="business-hero">
        <div>
            <span>Оптовый раздел</span>
            <h1>RB Roasters кофе для бизнеса</h1>
            <ul>
                <li>Обслуживание кофеен, кафе и ресторанов</li>
                <li>Обжарка кофе под собственной торговой маркой</li>
                <li>Консалтинг и обучение</li>
                <li>Сервис оборудования</li>
            </ul>
            <div class="hero-actions">
                <a class="button" href="<?= route_url('business-account') ?>">Зарегистрироваться</a>
                <a class="button button--outline" href="#price">Запросить прайс</a>
            </div>
        </div>
        <img src="/assets/img/__3.png" alt="Фирменный стиль RB Roasters">
    </section>
    <section class="beans-band beans-band--business">
        <div class="benefits">
            <article><b>Кофе на любой вкус</b><p>Базовые и уникальные сорта для меню.</p></article>
            <article><b>Контроль качества</b><p>Стабильные профили обжарки в Cropster.</p></article>
            <article><b>Обучение команды</b><p>Помогаем бариста работать уверенно.</p></article>
            <article><b>Сервис</b><p>Диагностика и обслуживание оборудования.</p></article>
        </div>
    </section>
    <section class="section split-section" id="production">
        <div class="copy-block"><span class="eyebrow">Производство</span><h2>Обжарка для кофеен и ресторанов</h2><p>Работаем с зеленым кофе, контролируем стабильность партий, подбираем лоты под задачи заведения и можем обжаривать под собственной торговой маркой.</p></div>
        <img class="about-photo" src="/assets/img/IMG_1192_1.jpg" alt="">
    </section>
    <section class="section" id="training">
        <?php section_title('Тренинг-центр', 'Курсы для бариста и команд', 'У каждого курса кнопка ведет к тренеру в Telegram.'); ?>
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <article class="course-card">
                    <img src="<?= $course['image'] ?>" alt="">
                    <div>
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                        <p><b><?= htmlspecialchars($course['duration']) ?></b><br><?= htmlspecialchars($course['price']) ?></p>
                        <ul><?php foreach ($course['items'] as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?></ul>
                        <a class="button button--small" href="<?= $contacts['trainer'] ?>">Узнать подробности</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="section service-section" id="service">
        <?php section_title('Сервисный центр', 'Обслуживание кофейного оборудования'); ?>
        <div class="service-gallery">
            <img src="/assets/img/4.webp" alt="">
            <img src="/assets/img/IMG_1463_1.jpg" alt="">
            <img src="/assets/img/IMG_1561_1.jpg" alt="">
        </div>
    </section>
    <section class="section price-form" id="price">
        <form>
            <h2>Запросить прайс</h2>
            <input placeholder="ФИО">
            <input placeholder="Телефон">
            <input placeholder="Почта">
            <button class="button" type="button">Получить прайс</button>
        </form>
    </section>
