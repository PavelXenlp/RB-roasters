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
            ?>
            <section id="orders">
                <h2>История заказов</h2>
                <?php if ($orders->have_posts()): ?>
                    <?php while ($orders->have_posts()): $orders->the_post(); ?>
                        <p><strong><?= esc_html(get_the_title()) ?></strong><br>
                            Статус: <?= esc_html(get_post_meta(get_the_ID(), 'rb_order_status', true) ?: 'Новый') ?>,
                            сумма: <?= esc_html(get_post_meta(get_the_ID(), 'rb_order_total', true) ?: 'не рассчитана') ?></p>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else: ?>
                    <p>Вы еще не совершили ни одного заказа.</p>
                    <a class="button button--small" href="<?= esc_url(route_url('catalog')) ?>">Выбрать кофе</a>
                <?php endif; ?>
            </section>

            <section id="profile">
                <h2>Мои данные</h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="profile">
                    <?php wp_nonce_field('rb_profile', 'rb_profile_nonce'); ?>
                    <input name="full_name" value="<?= esc_attr($user->display_name) ?>" placeholder="ФИО">
                    <input name="phone" value="<?= esc_attr(get_user_meta($user->ID, 'rb_phone', true)) ?>" placeholder="Телефон">
                    <input name="email" value="<?= esc_attr($user->user_email) ?>" placeholder="Почта">
                    <button class="button button--small" type="submit">Сохранить</button>
                </form>
            </section>

            <section id="discount">
                <h2>Моя скидка</h2>
                <div class="discount-line"><span>0%</span><span>5%</span><span>10%</span><span>15%</span></div>
                <p>Накопления и уровень скидки будут считаться по завершенным заказам.</p>
            </section>
        <?php else: ?>
            <section id="login">
                <h2>Вход</h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="login">
                    <?php wp_nonce_field('rb_login', 'rb_login_nonce'); ?>
                    <input type="email" name="email" placeholder="Почта" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button class="button button--small" type="submit">Войти</button>
                </form>
            </section>

            <section id="register">
                <h2>Регистрация</h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="rb_action" value="register">
                    <?php wp_nonce_field('rb_register', 'rb_register_nonce'); ?>
                    <input name="full_name" placeholder="ФИО" required>
                    <input name="phone" placeholder="Телефон" required>
                    <input type="email" name="email" placeholder="Почта" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button class="button button--small" type="submit">Зарегистрироваться</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
