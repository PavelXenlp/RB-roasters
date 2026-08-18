<section class="contacts-page">
        <div>
            <span class="eyebrow">Контакты</span>
            <h1>Roastberry Coffee Roasters</h1>
            <p><?= esc_html((string) ($contacts['address'] ?? '')) ?></p>
            <a href="tel:<?= esc_attr(rb_phone_href($contacts['phone'] ?? '')) ?>" class="big-phone"><?= esc_html(rb_format_phone($contacts['phone'] ?? '')) ?></a>
            <div class="contact-actions">
                <a class="button" href="<?= esc_url((string) ($contacts['tg'] ?? '')) ?>" target="_blank" rel="noopener noreferrer">Telegram</a>
                <a class="button button--outline" href="<?= esc_url((string) ($contacts['vk'] ?? '')) ?>" target="_blank" rel="noopener noreferrer">ВКонтакте</a>
            </div>
        </div>
        <div class="contacts-map" aria-label="Карта Roastberry Coffee Roasters">
            <?= rb_get_contacts_map_iframe(get_queried_object_id()) ?>
        </div>
    </section>
