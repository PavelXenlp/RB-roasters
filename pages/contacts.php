<section class="contacts-page">
        <div>
            <span class="eyebrow">Контакты</span>
            <h1>Roastberry Coffee Roasters</h1>
            <p><?= $contacts['address'] ?></p>
            <a href="tel:<?= esc_attr(rb_phone_href($contacts['phone'] ?? '')) ?>" class="big-phone"><?= esc_html(rb_format_phone($contacts['phone'] ?? '')) ?></a>
            <div class="contact-actions">
                <a class="button" href="<?= $contacts['tg'] ?>">Telegram</a>
                <a class="button button--outline" href="<?= $contacts['vk'] ?>">ВКонтакте</a>
            </div>
        </div>
        <a class="map-card map-card--large" href="<?= $contacts['map'] ?>" target="_blank" rel="noreferrer"><span class="map-pin"></span><strong>Открыть карту</strong><small><?= $contacts['address'] ?></small></a>
    </section>
