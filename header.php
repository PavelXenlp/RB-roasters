<?php
$contacts = function_exists('rb_contacts') ? rb_contacts() : ($contacts ?? []);
$menu = function_exists('rb_menu') ? rb_menu() : ($menu ?? []);
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
    <?php wp_head(); ?>
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= route_url('home') ?>" aria-label="Roastberry Coffee Roasters">
        <img src="<?= esc_url(rb_asset_url('img/logo.svg')) ?>" alt="" class="brand__logo">
        <span class="brand__name">Roastberry Coffee Roasters</span>
    </a>
    <div class="header-meta">
        <a class="social social--vk" href="<?= $contacts['vk'] ?>" aria-label="ВКонтакте">
            <img src="<?= esc_url(rb_asset_url('img/vk.svg')) ?>" alt="">
        </a>
        <a class="social social--tg" href="<?= $contacts['tg'] ?>" aria-label="Telegram">
            <img src="<?= esc_url(rb_asset_url('img/tg.svg')) ?>" alt="">
        </a>
        <a class="phone" href="tel:<?= $contacts['phone_href'] ?>"><?= $contacts['phone'] ?></a>
        <span class="address"><?= $contacts['address'] ?></span>
    </div>
    <nav class="header-actions" aria-label="Быстрые действия">
        <a class="icon-btn" href="<?= route_url('cart') ?>" aria-label="Корзина" title="Корзина">
            <img src="<?= esc_url(rb_asset_url('img/shopping-cart.svg')) ?>" alt="">
        </a>
        <a class="icon-btn" href="<?= route_url('account') ?>" aria-label="Личный кабинет" title="Личный кабинет">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 21a8 8 0 0 0-16 0"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </a>
        <button class="icon-btn menu-toggle" type="button" aria-label="Открыть меню" aria-expanded="false">
            <img src="<?= esc_url(rb_asset_url('img/dots.svg')) ?>" alt="">
        </button>
    </nav>
    <div class="menu-panel" aria-hidden="true">
        <?php foreach ($menu as [$label, $href]): ?>
            <a href="<?= $href ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </div>
</header>
<main>
