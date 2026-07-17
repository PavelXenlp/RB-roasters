<?php
$contacts = function_exists('rb_contacts') ? rb_contacts() : ($contacts ?? []);
$rb_header_menu_items = function_exists('rb_menu') ? rb_menu() : [];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title(); ?></title>
    <meta name="description" content="Roastberry Coffee Roasters: кофе для дома, бизнеса, обучение бариста и сервис кофейного оборудования.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('rb_preloader_seen') === '1') {
                    document.documentElement.classList.add('has-seen-preloader');
                }
            } catch (error) {
                document.documentElement.classList.add('has-seen-preloader');
            }
        }());
    </script>
    <?php wp_head(); ?>
</head>
<body>
<div class="page-preloader" data-preloader aria-hidden="true">
    <div class="page-preloader__inner">
        <img src="<?= esc_url(rb_asset_url('img/logo.svg')) ?>" alt="" class="page-preloader__logo">
        <div class="page-preloader__brand">Roastberry Coffee Roasters</div>
        <div class="page-preloader__bar" aria-hidden="true">
            <span data-preloader-bar></span>
        </div>
        <div class="page-preloader__percent" data-preloader-percent>0%</div>
    </div>
</div>
<header class="site-header">
    <a class="brand" href="<?= esc_url(route_url('home')) ?>" aria-label="Roastberry Coffee Roasters">
        <img src="<?= esc_url(rb_asset_url('img/logo.svg')) ?>" alt="" class="brand__logo">
        <span class="brand__name">Roastberry Coffee Roasters</span>
    </a>
    <div class="header-meta">
        <a class="social social--vk" href="<?= esc_url($contacts['vk'] ?? '#') ?>" aria-label="ВКонтакте">
            <img src="<?= esc_url(rb_asset_url('img/vk.svg')) ?>" alt="">
        </a>
        <a class="social social--tg" href="<?= esc_url($contacts['tg'] ?? '#') ?>" aria-label="Telegram">
            <img src="<?= esc_url(rb_asset_url('img/tg.svg')) ?>" alt="">
        </a>
        <a class="phone" href="tel:<?= esc_attr(rb_phone_href($contacts['phone'] ?? '')) ?>"><?= esc_html(rb_format_phone($contacts['phone'] ?? '')) ?></a>
        <span class="address"><?= esc_html($contacts['address'] ?? '') ?></span>
    </div>
    <nav class="header-actions" aria-label="Быстрые действия">
        <a class="icon-btn" href="<?= esc_url(route_url('cart')) ?>" aria-label="Корзина" title="Корзина">
            <img src="<?= esc_url(rb_asset_url('img/shopping-cart.svg')) ?>" alt="">
            <?php if (function_exists('rb_cart_count') && rb_cart_count() > 0): ?>
                <span class="cart-count"><?= esc_html((string) rb_cart_count()) ?></span>
            <?php endif; ?>
        </a>
        <?php if (is_user_logged_in()): ?>
            <a class="icon-btn" href="<?= esc_url(route_url('account')) ?>" aria-label="Личный кабинет" title="Личный кабинет">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </a>
        <?php else: ?>
            <button class="icon-btn account-modal-toggle" type="button" aria-label="Выбрать личный кабинет" title="Личный кабинет" aria-expanded="false" data-auth-modal-open>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </button>
        <?php endif; ?>
        <button class="icon-btn menu-toggle" type="button" aria-label="Открыть меню" aria-expanded="false">
            <img src="<?= esc_url(rb_asset_url('img/dots.svg')) ?>" alt="">
        </button>
    </nav>
    <div class="menu-panel" aria-hidden="true">
        <?php foreach ($rb_header_menu_items as [$label, $href]): ?>
            <a href="<?= esc_url($href) ?>"><?= esc_html($label) ?></a>
        <?php endforeach; ?>
    </div>
</header>
<?php if (!is_user_logged_in()): ?>
    <div class="auth-modal" data-auth-modal aria-hidden="true">
        <div class="auth-modal__overlay" data-auth-modal-close></div>
        <section class="auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
            <button class="auth-modal__close" type="button" aria-label="Закрыть" data-auth-modal-close>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>
            <span class="eyebrow">RB Roasters</span>
            <h2 id="auth-modal-title">Выберите кабинет</h2>
            <p>Зарегистрируйтесь как розничный покупатель или юридическое лицо.</p>
            <div class="auth-modal__choices">
                <a class="auth-choice" href="<?= esc_url(route_url('account') . '#register') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 21a8 8 0 0 0-16 0"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Розничный покупатель</span>
                    <small>Личный кабинет для заказов с сайта</small>
                </a>
                <a class="auth-choice" href="<?= esc_url(route_url('business-account')) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 21h18"/>
                        <path d="M5 21V7l8-4v18"/>
                        <path d="M19 21V11l-6-3"/>
                        <path d="M9 9h1"/>
                        <path d="M9 13h1"/>
                        <path d="M9 17h1"/>
                    </svg>
                    <span>Юридическое лицо</span>
                    <small>Кабинет для кофеен, офисов и партнеров</small>
                </a>
            </div>
            <a class="auth-modal__login" href="<?= esc_url(route_url('account') . '#login') ?>">У меня уже есть аккаунт</a>
        </section>
    </div>
<?php endif; ?>
<main>
