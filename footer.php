<?php $contacts = function_exists('rb_contacts') ? rb_contacts() : ($contacts ?? []); ?>
</main>
<footer class="site-footer" id="contacts">
    <div class="footer-inner">
        <div>
            <img src="<?= esc_url(rb_asset_url('img/footer_logo.svg')) ?>" alt="Roastberry Coffee Roasters" class="footer-logo">
            <nav class="footer-links" aria-label="Подвал">
                <a href="<?= route_url('delivery') ?>">Доставка и оплата</a>
                <a href="<?= route_url('business') ?>">Кофе для бизнеса</a>
                <a href="<?= route_url('contacts') ?>">Контакты</a>
            </nav>
            <nav class="footer-legal-links" aria-label="Юридическая информация">
                <a href="<?= esc_url(route_url('returns')) ?>">Возврат</a>
                <a href="<?= esc_url(route_url('public-offer')) ?>">Публичная оферта</a>
                <a href="<?= esc_url(route_url('user-agreement')) ?>">Пользовательское соглашение</a>
                <a href="<?= esc_url(route_url('privacy')) ?>">Политика конфиденциальности</a>
                <a href="<?= esc_url(route_url('personal-data-consent')) ?>">Согласие на обработку ПД</a>
                <a href="<?= esc_url(route_url('requisites')) ?>">Реквизиты</a>
            </nav>
            <a class="footer-phone" href="tel:<?= esc_attr(rb_phone_href($contacts['phone'] ?? '')) ?>"><?= esc_html(rb_format_phone($contacts['phone'] ?? '')) ?></a>
            <div class="footer-socials">
                <a href="<?= esc_url($contacts['vk']) ?>" target="_blank" rel="noopener noreferrer" aria-label="ВКонтакте"><img src="<?= esc_url(rb_asset_url('img/vk_footer.svg')) ?>" alt=""></a>
                <a href="<?= esc_url($contacts['tg']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram"><img src="<?= esc_url(rb_asset_url('img/tg_footer.svg')) ?>" alt=""></a>
            </div>
            <?php $legal_settings = rb_legal_settings(); ?>
            <p class="legal">© <?= esc_html($legal_settings['legal_name']) ?><br>ИНН <?= esc_html($legal_settings['inn']) ?> · ОГРНИП <?= esc_html($legal_settings['ogrnip']) ?></p>
        </div>
        <div class="footer-map">
            <a href="https://yandex.ru/maps/org/rb_roasters/232344819285/?utm_medium=mapframe&utm_source=maps" target="_blank" rel="noopener noreferrer">Roastberry Coffee Roasters</a>
            <a href="https://yandex.ru/maps/50/perm/category/coffee_store/144176536401/?utm_medium=mapframe&utm_source=maps" target="_blank" rel="noopener noreferrer">Магазин кофе в Перми</a>
            <a href="https://yandex.ru/maps/50/perm/category/coffee_machine_repair/18797871051/?utm_medium=mapframe&utm_source=maps" target="_blank" rel="noopener noreferrer">Ремонт кофемашин в Перми</a>
            <?= rb_cookie_deferred_embed('<iframe src="https://yandex.ru/map-widget/v1/org/rb_roasters/232344819285/?from=mapframe&amp;ll=56.147100%2C58.005299&amp;z=19.28" width="560" height="400" frameborder="1" allowfullscreen="true" title="Карта Roastberry Coffee Roasters"></iframe>', 'Карта Roastberry Coffee Roasters') ?>
        </div>
    </div>
</footer>
<?php $cookie_policy_version = (string) (rb_legal_settings()['policy_version'] ?? '1'); ?>
<section class="cookie-consent" data-cookie-consent data-cookie-version="<?= esc_attr($cookie_policy_version) ?>" role="dialog" aria-modal="false" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-description" hidden>
    <div class="cookie-consent__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5Z"/><circle cx="8.5" cy="10.5" r="1"/><circle cx="12.5" cy="15.5" r="1"/><circle cx="7.5" cy="16.5" r="1"/></svg>
    </div>
    <div class="cookie-consent__copy">
        <strong id="cookie-consent-title">Настройки cookie</strong>
        <p id="cookie-consent-description">Мы используем необходимые cookie для корзины, входа и стабильной работы сайта. Дополнительные cookie включаются только с вашего согласия. Подробнее в <a href="<?= esc_url(route_url('privacy') . '#cookies') ?>" target="_blank" rel="noopener">Политике конфиденциальности</a>.</p>
    </div>
    <div class="cookie-consent__actions">
        <button class="button button--outline" type="button" data-cookie-choice="necessary">Только необходимые</button>
        <button class="button" type="button" data-cookie-choice="all">Принять все</button>
    </div>
</section>
<a class="manager-link" href="<?= esc_url($contacts['manager']) ?>" target="_blank" rel="noopener noreferrer">Связь с менеджером</a>
<?php wp_footer(); ?>
</body>
</html>
