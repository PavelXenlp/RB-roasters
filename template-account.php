<?php
/**
 * Template Name: Личный кабинет
 *
 * @package ROASTBERRY_THEME
 */

get_header();
?>

<section class="account-page">
    <aside>
        <h1>Личный кабинет</h1>
        <?php if (is_user_logged_in()): ?>
            <a href="#orders">История заказов</a>
            <a href="#profile">Мои данные</a>
            <a href="#discount">Моя скидка</a>
            <a href="<?= esc_url(wp_logout_url(route_url('account'))) ?>">Выйти</a>
        <?php else: ?>
            <a href="#login">Вход</a>
            <a href="#register">Регистрация</a>
        <?php endif; ?>
    </aside>

    <div class="account-content">
        <?php if (is_user_logged_in()): ?>
            <?php
            $user = wp_get_current_user();
            $orders = rb_get_user_orders(get_current_user_id());
            $loyalty = rb_user_loyalty_data(get_current_user_id());
            $next_level = null;
            foreach ([10000 => 5, 20000 => 10, 50000 => 15] as $threshold => $percent) {
                if ($loyalty['spend'] < $threshold) {
                    $next_level = ['threshold' => $threshold, 'percent' => $percent];
                    break;
                }
            }
            ?>
            <section id="orders">
                <h2>История заказов</h2>
                <?php if ($orders->have_posts()): ?>
                    <?php while ($orders->have_posts()): $orders->the_post(); ?>
                        <p><strong><?= esc_html(get_the_title()) ?></strong><br>
                            <?php $order_status = (string) get_post_meta(get_the_ID(), 'rb_order_status', true); ?>
                            Статус: <?= esc_html(rb_order_statuses()[$order_status] ?? ($order_status ?: 'Новый')) ?>,
                            сумма: <?= esc_html(get_post_meta(get_the_ID(), 'rb_order_total', true) ?: 'не рассчитана') ?></p>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else: ?>
                    <p>Вы еще не совершили ни одного заказа.</p>
                    <a class="button button--small" href="<?= esc_url(route_url('catalog')) ?>">Выбрать кофе</a>
                <?php endif; ?>
            </section>

            <section id="profile">
                <h2>Мои данные</h2>
                <?php if (isset($_GET['profile']) && sanitize_key(wp_unslash($_GET['profile'])) === 'phone'): ?>
                    <p class="checkout-error" role="alert"><?= esc_html(rb_phone_input_title()) ?></p>
                <?php endif; ?>
                <?php if (isset($_GET['profile']) && sanitize_key(wp_unslash($_GET['profile'])) === 'consent'): ?><p class="checkout-error" role="alert">Подтвердите обязательные согласия.</p><?php endif; ?>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="profile">
                    <?php wp_nonce_field('rb_profile', 'rb_profile_nonce'); ?>
                    <input name="full_name" value="<?= esc_attr($user->display_name) ?>" placeholder="ФИО">
                    <input name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" value="<?= esc_attr(rb_format_phone((string) get_user_meta($user->ID, 'rb_phone', true))) ?>" placeholder="+7 (___) ___-__-__" required>
                    <input name="email" value="<?= esc_attr($user->user_email) ?>" placeholder="Почта">
                    <?php rb_render_legal_consents(); ?>
                    <button class="button button--small" type="submit">Сохранить</button>
                </form>
            </section>

            <section id="discount">
                <h2>Моя скидка</h2>
                <p><strong><?= esc_html((string) $loyalty['percent']) ?>%</strong> — ваша текущая скидка. Учтено покупок на <?= esc_html(rb_format_price((int) $loyalty['spend'])) ?>.</p>
                <div class="discount-line"><span>3%</span><span>5%</span><span>10%</span><span>15%</span></div>
                <?php if ($next_level): ?>
                    <p>До скидки <?= esc_html((string) $next_level['percent']) ?>% осталось покупок на <?= esc_html(rb_format_price(max(0, $next_level['threshold'] - $loyalty['spend']))) ?>.</p>
                <?php else: ?>
                    <p>У вас максимальный уровень программы лояльности.</p>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <?php
            $auth_status = isset($_GET['auth']) ? sanitize_key(wp_unslash($_GET['auth'])) : '';
            $auth_messages = [
                'failed' => 'Аккаунт с такой почтой не найден.',
                'exists' => 'Пользователь с такой почтой уже существует. Используйте вход.',
                'mail' => 'Не удалось отправить письмо. Попробуйте еще раз немного позже.',
                'code_rate' => 'Код уже отправлен. Используйте последнее письмо.',
                'code_invalid' => 'Код не подходит. Проверьте цифры и попробуйте снова.',
                'code_expired' => 'Срок действия кода истек. Запросите новый код.',
                'consent' => 'Подтвердите обязательные согласия перед отправкой формы.',
            ];
            $show_code_form = in_array(rb_auth_pending_type(), ['retail_login', 'retail_register'], true)
                && in_array($auth_status, ['code', 'code_rate', 'code_invalid'], true);
            ?>
            <?php if (isset($auth_messages[$auth_status])): ?>
                <p class="<?= $auth_status === 'code_rate' ? 'cart-notice' : 'checkout-error' ?>" role="alert"><?= esc_html($auth_messages[$auth_status]) ?></p>
            <?php endif; ?>
            <?php if ($show_code_form): ?>
            <section id="auth-code" class="auth-code-section">
                <span class="eyebrow">Проверьте почту</span>
                <h2>Введите код из письма</h2>
                <p>Мы отправили шестизначный код на <?= esc_html((string) (rb_auth_pending()['email'] ?? 'указанную почту')) ?>. Он действует 10 минут.</p>
                <form method="post" class="form-grid form-grid--code">
                    <input type="hidden" name="rb_action" value="verify_auth_code">
                    <?php wp_nonce_field('rb_verify_auth_code', 'rb_auth_code_nonce'); ?>
                    <input class="auth-code-input" name="auth_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" aria-label="Одноразовый код" required autofocus>
                    <button class="button button--small" type="submit">Подтвердить и войти</button>
                </form>
                <a class="auth-code-section__back" href="<?= esc_url(remove_query_arg('auth', route_url('account'))) ?>">Указать другую почту</a>
            </section>
            <?php else: ?>
            <section id="login">
                <h2>Вход</h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="login">
                    <?php wp_nonce_field('rb_login', 'rb_login_nonce'); ?>
                    <input type="email" name="email" placeholder="Почта" required>
                    <?php rb_render_legal_consents(); ?>
                    <button class="button button--small" type="submit">Получить код на почту</button>
                </form>
            </section>

            <section id="register">
                <h2>Регистрация</h2>
                <?php if (isset($_GET['auth']) && sanitize_key(wp_unslash($_GET['auth'])) === 'phone'): ?>
                    <p class="checkout-error" role="alert"><?= esc_html(rb_phone_input_title()) ?></p>
                <?php endif; ?>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="register">
                    <?php wp_nonce_field('rb_register', 'rb_register_nonce'); ?>
                    <input name="full_name" placeholder="ФИО" required>
                    <input name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" placeholder="+7 (___) ___-__-__" required>
                    <input type="email" name="email" placeholder="Почта" required>
                    <?php rb_render_legal_consents(); ?>
                    <button class="button button--small" type="submit">Получить код на почту</button>
                </form>
            </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
