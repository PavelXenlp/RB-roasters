<section class="business-hero">
    <div>
        <span>Оптовый раздел</span>
        <h1>Roastberry Coffee Roasters кофе для бизнеса</h1>
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
    <img src="<?= esc_url(rb_asset_url('img/__3.png')) ?>" alt="Фирменный стиль Roastberry Coffee Roasters">
</section>

<section class="beans-band beans-band--business">
    <div class="business-benefits-slider" data-business-benefits>
        <div class="benefits business-benefits" data-business-benefits-track>
            <article>
                <span class="business-benefit__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.5 13 17 22l-5-3-5 3 1.5-9"/></svg></span>
                <b>Входим в ТОП-3 обжарщиков страны</b>
                <p>По итогам национальной премии «Обжарщик года-2026»</p>
            </article>
            <article>
                <span class="business-benefit__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 2v2M10 2v2M14 2v2"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8Z"/><path d="M17 10h1a3 3 0 1 1 0 6h-1"/></svg></span>
                <b>Кофе на любой вкус</b>
                <p>Базовые и уникальные сорта для меню.</p>
            </article>
            <article>
                <span class="business-benefit__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                <b>Контроль качества</b>
                <p>Стабильные профили обжарки в Cropster.</p>
            </article>
            <article>
                <span class="business-benefit__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m2 10 10-5 10 5-10 5L2 10Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/><path d="M22 10v6"/></svg></span>
                <b>Обучение команды</b>
                <p>Помогаем бариста работать уверенно.</p>
            </article>
            <article>
                <span class="business-benefit__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5-5l2.1 2.1-2.4 2.4-2.1-2.1a4 4 0 0 0 5 5L21 17.4a2.1 2.1 0 0 1-3 3l-8.7-8.7"/></svg></span>
                <b>Сервис</b>
                <p>Диагностика и обслуживание оборудования.</p>
            </article>
        </div>
        <div class="business-benefits__controls" aria-label="Навигация по преимуществам">
            <button type="button" aria-label="Предыдущее преимущество" data-business-benefits-prev>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <span aria-live="polite" data-business-benefits-status>1 / 5</span>
            <button type="button" aria-label="Следующее преимущество" data-business-benefits-next>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
    </div>
</section>

<section class="section split-section business-production" id="production">
    <div class="copy-block">
        <span class="eyebrow">Производство</span>
        <h2>Стабильная обжарка и контроль качества</h2>
        <p>Цикл производства доведен до уровня лучших обжарщиков страны. Жарим кофе на ростерах Giesen W15A и Trobrat, используем ПО Cropster для контроля качества и стабильности профилей.</p>
        <p>С заботой о клиентах отбираем сырье у импортеров, сотрудничаем только с надежными поставщиками и поддерживаем ассортимент для бизнеса. Подбираем лоты под задачи заведения и можем обжаривать под собственной торговой маркой.</p>
    </div>
    <div class="about-mosaic business-production-mosaic" aria-label="Производство Roastberry Coffee Roasters">
        <figure class="about-mosaic__item about-mosaic__item--team">
            <img src="<?= esc_url(rb_asset_url('img/business/DL9A3763 1.webp')) ?>" alt="Обжарщик Roastberry Coffee Roasters управляет ростером" loading="lazy">
        </figure>
        <figure class="about-mosaic__item about-mosaic__item--award">
            <img src="<?= esc_url(rb_asset_url('img/business/DL9A3790 1.webp')) ?>" alt="Контроль процесса обжарки кофе" loading="lazy">
        </figure>
        <figure class="about-mosaic__item about-mosaic__item--process">
            <img src="<?= esc_url(rb_asset_url('img/business/DL9A4158 1.webp')) ?>" alt="Фасовка кофе на производстве" loading="lazy">
        </figure>
        <figure class="about-mosaic__item about-mosaic__item--roaster">
            <img src="<?= esc_url(rb_asset_url('img/business/DL9A4179 1.webp')) ?>" alt="Зеленое кофейное зерно перед обжаркой" loading="lazy">
        </figure>
    </div>
    <?php $business_logo_ids = rb_get_business_logos(get_queried_object_id()); ?>
    <div class="business-trust">
        <span class="eyebrow">Нам доверяют</span>
        <?php if ($business_logo_ids): ?>
            <div class="business-trust__logos" aria-label="Компании, которые работают с Roastberry Coffee Roasters">
                <?php foreach ($business_logo_ids as $logo_id): ?>
                    <div class="business-trust__logo">
                        <?= wp_get_attachment_image($logo_id, 'medium', false, ['loading' => 'lazy']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section business-training" id="training">
    <?php section_title('Тренинг-центр', 'Курсы для бариста и команд', ''); ?>
    <div class="course-grid">
        <?php
        $training_query = new WP_Query([
            'post_type' => 'rb_training',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        ]);
        ?>
        <?php if ($training_query->have_posts()): ?>
            <?php while ($training_query->have_posts()): $training_query->the_post(); ?>
                <?php rb_training_card_from_post(get_the_ID()); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <article class="course-card">
                    <img src="<?= esc_url($course['image']) ?>" alt="">
                    <div>
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                        <p><b><?= htmlspecialchars($course['duration']) ?></b><br><?= htmlspecialchars($course['price']) ?></p>
                        <ul><?php foreach ($course['items'] as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?></ul>
                        <a class="button button--small" href="<?= esc_url($contacts['trainer']) ?>" target="_blank" rel="noopener noreferrer">Узнать подробности</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="section service-section" id="service">
    <?php section_title('Сервисный центр', 'Обслуживание кофейного оборудования'); ?>
    <div class="service-gallery">
        <img src="<?= esc_url(rb_asset_url('img/4.webp')) ?>" alt="">
        <img src="<?= esc_url(rb_asset_url('img/DL9A4358.webp')) ?>" alt="">
    </div>
    <div class="service-directions-slider" data-service-directions>
        <div class="service-directions" data-service-directions-track>
            <article>
                <span class="service-directions__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5-5l2.1 2.1-2.4 2.4-2.1-2.1a4 4 0 0 0 5 5L21 17.4a2.1 2.1 0 0 1-3 3l-8.7-8.7"/></svg></span>
                <h3>Ремонт и обслуживание профессионального и домашнего кофейного оборудования</h3>
            </article>
            <article>
                <span class="service-directions__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m7.5 4.3 9 5.1"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/><path d="m21 8-9-5-9 5v10l9 5 9-5Z"/></svg></span>
                <h3>Запчасти, расходники, средства для очистки</h3>
            </article>
            <article>
                <span class="service-directions__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
                <h3>Продажа нового и б/у оборудования</h3>
            </article>
            <article>
                <span class="service-directions__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15 7 3 3"/><path d="m18 4 3 3"/></svg></span>
                <h3>Аренда для бизнеса и офиса</h3>
            </article>
            <article>
                <span class="service-directions__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect width="14" height="18" x="5" y="3" rx="2"/><path d="M9 3V1h6v2"/><path d="m9 13 2 2 4-4"/></svg></span>
                <h3>Консалтинг в подборе оборудования</h3>
            </article>
        </div>
        <div class="service-directions__controls" aria-label="Навигация по направлениям сервиса">
            <button type="button" aria-label="Предыдущее направление" data-service-directions-prev>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <span aria-live="polite" data-service-directions-status>1 / 5</span>
            <button type="button" aria-label="Следующее направление" data-service-directions-next>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
    </div>
    <div class="service-actions">
        <a class="button" href="https://t.me/RBR_Zakaz" target="_blank" rel="noopener noreferrer">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            Связаться с менеджером
        </a>
    </div>
</section>

<section class="section price-form" id="price">
    <form method="post">
        <h2>Запросить прайс</h2>
        <?php if (isset($_GET['price']) && sanitize_key(wp_unslash($_GET['price'])) === 'sent'): ?><p class="cart-notice">Запрос отправлен. Менеджер свяжется с вами.</p><?php endif; ?>
        <?php if (isset($_GET['price']) && sanitize_key(wp_unslash($_GET['price'])) === 'error'): ?><p class="checkout-error">Проверьте ФИО, почту и номер телефона.</p><?php endif; ?>
        <?php if (isset($_GET['price']) && sanitize_key(wp_unslash($_GET['price'])) === 'consent'): ?><p class="checkout-error">Подтвердите обязательные согласия.</p><?php endif; ?>
        <input type="hidden" name="rb_action" value="business_price_request">
        <?php wp_nonce_field('rb_business_price_request', 'rb_business_price_nonce'); ?>
        <input name="full_name" placeholder="ФИО" required>
        <input name="phone" type="tel" placeholder="+7 (___) ___-__-__" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" required>
        <input name="email" type="email" placeholder="Почта" required>
        <?php rb_render_legal_consents(); ?>
        <button class="button" type="submit">Получить прайс</button>
    </form>
</section>
