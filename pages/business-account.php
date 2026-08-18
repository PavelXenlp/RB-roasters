<?php
$result = isset($_GET['business_result']) ? sanitize_key(wp_unslash($_GET['business_result'])) : '';
$messages = [
    'registered' => 'Заявка создана. Менеджер проверит реквизиты и откроет оптовые цены.',
    'saved' => 'Реквизиты сохранены.',
    'ordered' => 'Оптовая заявка отправлена менеджеру.',
    'company_added' => 'Компания добавлена в кабинет.',
    'email' => 'Пользователь с такой почтой уже существует.',
    'phone' => rb_phone_input_title(),
    'inn' => 'Проверьте ИНН: контрольное число не совпадает.',
    'empty' => 'Укажите количество хотя бы одного товара.',
    'approval' => 'Отправка заказа станет доступна после проверки компании.',
    'company' => 'Выберите компанию, от которой оформляется заказ.',
    'stock' => 'Один из выбранных товаров закончился или доступен в меньшем количестве.',
    'payment' => 'Не удалось создать онлайн-платеж. Заявка отменена, попробуйте снова или выберите счет от менеджера.',
    'payment_success' => 'Оплата получена. Оптовый заказ передан в обработку.',
    'payment_pending' => 'Заказ создан, статус оплаты уточняется автоматически.',
    'mail' => 'Не удалось отправить письмо с кодом. Попробуйте еще раз немного позже.',
    'code_rate' => 'Код уже отправлен. Используйте последнее письмо.',
    'code_invalid' => 'Код не подходит. Проверьте цифры и попробуйте снова.',
    'code_expired' => 'Срок действия кода истек. Запросите новый код.',
    'error' => 'Не удалось сохранить заявку. Попробуйте еще раз.',
    'consent' => 'Подтвердите обязательные согласия перед отправкой формы.',
];
$user = wp_get_current_user();
?>
<section class="account-page account-page--business">
    <aside>
        <h1>Кабинет для бизнеса</h1>
        <?php if (is_user_logged_in() && rb_is_business_customer()): ?>
            <a href="#b-company">Компания</a>
            <a href="#b-catalog">Оптовый заказ</a>
            <a href="#b-orders">Заказы</a>
            <a href="<?= esc_url(wp_logout_url(route_url('business-account'))) ?>">Выйти</a>
        <?php else: ?>
            <a href="#b-register">Регистрация</a>
            <a href="<?= esc_url(route_url('account') . '#login') ?>">Вход</a>
        <?php endif; ?>
    </aside>
    <div class="account-content">
        <?php if ($result && isset($messages[$result])): ?><p class="<?= in_array($result, ['registered', 'saved', 'ordered', 'company_added', 'payment_success', 'payment_pending', 'code_rate'], true) ? 'cart-notice' : 'checkout-error' ?>" role="status"><?= esc_html($messages[$result]) ?></p><?php endif; ?>

        <?php if (!is_user_logged_in()): ?>
            <?php if (rb_auth_pending_type() === 'business_register' && in_array($result, ['code', 'code_rate', 'code_invalid'], true)): ?>
            <section id="b-code" class="auth-code-section">
                <span class="eyebrow">Подтверждение рабочей почты</span>
                <h2>Введите код из письма</h2>
                <p>Код отправлен на <?= esc_html((string) (rb_auth_pending()['email'] ?? 'указанную почту')) ?> и действует 10 минут.</p>
                <form method="post" class="form-grid form-grid--code">
                    <input type="hidden" name="rb_action" value="verify_auth_code">
                    <?php wp_nonce_field('rb_verify_auth_code', 'rb_auth_code_nonce'); ?>
                    <input class="auth-code-input" name="auth_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" aria-label="Одноразовый код" required autofocus>
                    <button class="button" type="submit">Подтвердить и войти</button>
                </form>
                <a class="auth-code-section__back" href="<?= esc_url(route_url('business-account')) ?>">Изменить данные</a>
            </section>
            <?php else: ?>
            <section id="b-register">
                <span class="eyebrow">Для кафе, ресторанов и офисов</span>
                <h2>Регистрация компании</h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="business_register">
                    <?php wp_nonce_field('rb_business_register', 'rb_business_register_nonce'); ?>
                    <input name="full_name" placeholder="Контактное лицо" required>
                    <input type="email" name="email" placeholder="Рабочая почта" required>
                    <input name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" placeholder="+7 (___) ___-__-__" required>
                    <input name="company_name" placeholder="Название компании" required>
                    <input name="inn" inputmode="numeric" placeholder="ИНН" required>
                    <input name="kpp" inputmode="numeric" placeholder="КПП, если есть">
                    <input name="city" placeholder="Город" required>
                    <input name="legal_address" placeholder="Юридический адрес" required>
                    <?php rb_render_legal_consents(); ?>
                    <button class="button" type="submit">Получить код на почту</button>
                </form>
            </section>
            <?php endif; ?>
        <?php elseif (!rb_is_business_customer()): ?>
            <section><h2>Это кабинет для юридических лиц</h2><p>Для розничных заказов используйте обычный личный кабинет.</p><a class="button button--small" href="<?= esc_url(route_url('account')) ?>">Перейти в кабинет</a></section>
        <?php else: ?>
            <section id="b-company">
                <span class="eyebrow"><?= rb_business_is_approved() ? 'Компания подтверждена' : 'Реквизиты проверяются' ?></span>
                <h2><?= esc_html((string) (get_user_meta($user->ID, 'rb_company_name', true) ?: 'Моя компания')) ?></h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="business_profile">
                    <?php wp_nonce_field('rb_business_profile', 'rb_business_profile_nonce'); ?>
                    <input name="company_name" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_name', true)) ?>" placeholder="Название компании" required>
                    <input name="inn" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_inn', true)) ?>" placeholder="ИНН" required>
                    <input name="kpp" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_kpp', true)) ?>" placeholder="КПП">
                    <input name="city" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_city', true)) ?>" placeholder="Город" required>
                    <input name="legal_address" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_address', true)) ?>" placeholder="Юридический адрес" required>
                    <input name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_phone', true)) ?>" required>
                    <?php rb_render_legal_consents(); ?>
                    <button class="button button--small" type="submit">Сохранить реквизиты</button>
                </form>
                <?php $companies = rb_business_companies($user->ID); ?>
                <?php if ($companies): ?>
                    <div class="business-companies">
                        <?php foreach ($companies as $company): ?>
                            <article><strong><?= esc_html((string) $company['name']) ?></strong><span>ИНН <?= esc_html((string) $company['inn']) ?></span><?php if (!empty($company['city'])): ?><small><?= esc_html((string) $company['city']) ?></small><?php endif; ?></article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <details class="business-company-add">
                    <summary>Добавить еще одну компанию</summary>
                    <form method="post" class="form-grid">
                        <input type="hidden" name="rb_action" value="business_company_add">
                        <?php wp_nonce_field('rb_business_company_add', 'rb_business_company_nonce'); ?>
                        <input name="company_name" placeholder="Название компании" required>
                        <input name="inn" inputmode="numeric" placeholder="ИНН" required>
                        <input name="kpp" inputmode="numeric" placeholder="КПП">
                        <input name="city" placeholder="Город" required>
                        <input name="legal_address" placeholder="Юридический адрес" required>
                        <?php rb_render_legal_consents(); ?>
                        <button class="button button--small" type="submit">Добавить компанию</button>
                    </form>
                </details>
            </section>

            <section id="b-catalog">
                <span class="eyebrow">Заказ в килограммах</span>
                <h2>Оптовый каталог</h2>
                <?php if (rb_business_is_approved()): ?>
                    <?php $business_products = get_posts(['post_type' => 'rb_product', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']); ?>
                    <form method="post">
                        <input type="hidden" name="rb_action" value="business_order">
                        <?php wp_nonce_field('rb_business_order', 'rb_business_order_nonce'); ?>
                        <label class="business-company-select">Компания
                            <?php $company_options = []; foreach ($companies as $company) $company_options[(string) $company['id']] = (string) $company['name'] . ' · ИНН ' . (string) $company['inn']; rb_custom_select('company_id', $company_options, (string) array_key_first($company_options), 'Компания'); ?>
                        </label>
                        <label class="business-company-select">Оплата
                            <?php $payment_options = ['invoice' => 'Счет и условия согласует менеджер']; if (rb_yookassa_is_configured()) $payment_options['yookassa'] = 'Онлайн через YooKassa'; rb_custom_select('payment_method', $payment_options, 'invoice', 'Оплата'); ?>
                        </label>
                        <div class="business-order-grid">
                            <?php foreach ($business_products as $product): $base_price = rb_business_unit_price($product->ID, 1); if ($base_price <= 0) continue; ?>
                                <article class="business-order-item">
                                    <?= get_the_post_thumbnail($product, 'medium') ?>
                                    <div><strong><?= esc_html($product->post_title) ?></strong><small>от <?= esc_html(rb_format_price($base_price)) ?> / кг</small></div>
                                    <label>Количество, кг<input type="number" name="quantity[<?= esc_attr((string) $product->ID) ?>]" min="0" step="1" value="0"></label>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <p>От 10 кг одного лота — скидка 10%, от 25 кг — 20%. Доставка и форма оплаты согласуются менеджером.</p>
                        <?php rb_render_legal_consents(true); ?>
                        <button class="button" type="submit">Отправить заказ</button>
                    </form>
                <?php else: ?>
                    <p>Оптовые цены и форма заказа появятся после проверки реквизитов менеджером.</p>
                <?php endif; ?>
            </section>

            <section id="b-orders">
                <h2>Мои заказы</h2>
                <?php $business_orders = rb_get_user_orders($user->ID); ?>
                <?php if ($business_orders->have_posts()): while ($business_orders->have_posts()): $business_orders->the_post(); $status = (string) get_post_meta(get_the_ID(), 'rb_order_status', true); ?>
                    <p><strong><?= esc_html(get_the_title()) ?></strong><br><?= esc_html(rb_order_statuses()[$status] ?? $status) ?> · <?= esc_html((string) get_post_meta(get_the_ID(), 'rb_order_total', true)) ?></p>
                <?php endwhile; wp_reset_postdata(); else: ?><p>Заказов пока нет.</p><?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</section>
