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
            <a class="footer-phone" href="tel:<?= $contacts['phone_href'] ?>"><?= $contacts['phone'] ?></a>
            <div class="footer-socials">
                <a href="<?= esc_url($contacts['vk']) ?>" aria-label="ВКонтакте"><img src="<?= esc_url(rb_asset_url('img/vk_footer.svg')) ?>" alt=""></a>
                <a href="<?= esc_url($contacts['tg']) ?>" aria-label="Telegram"><img src="<?= esc_url(rb_asset_url('img/tg_footer.svg')) ?>" alt=""></a>
            </div>
            <p class="legal">© ИП Тюхтин Дмитрий Владимирович<br>ИНН 590418682504</p>
        </div>
        <div class="footer-map">
            <a href="https://yandex.ru/maps/org/rb_roasters/232344819285/?utm_medium=mapframe&utm_source=maps">Rb Roasters</a>
            <a href="https://yandex.ru/maps/50/perm/category/coffee_store/144176536401/?utm_medium=mapframe&utm_source=maps">Магазин кофе в Перми</a>
            <a href="https://yandex.ru/maps/50/perm/category/coffee_machine_repair/18797871051/?utm_medium=mapframe&utm_source=maps">Ремонт кофемашин в Перми</a>
            <iframe src="https://yandex.ru/map-widget/v1/org/rb_roasters/232344819285/?from=mapframe&ll=56.147100%2C58.005299&z=19.28" width="560" height="400" frameborder="1" allowfullscreen="true" title="Карта RB Roasters"></iframe>
        </div>
    </div>
</footer>
<a class="manager-link" href="<?= esc_url($contacts['manager']) ?>" target="_blank" rel="noreferrer">Связь с менеджером</a>
<?php wp_footer(); ?>
</body>
</html>
