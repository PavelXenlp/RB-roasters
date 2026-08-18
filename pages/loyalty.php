<?php
$registration_url = route_url('account') . '#register';
$loyalty_levels = [
    ['level' => 'Уровень 1', 'percent' => '3%', 'condition' => 'После первого заказа', 'detail' => 'Стартовая скидка'],
    ['level' => 'Уровень 2', 'percent' => '5%', 'condition' => 'От 10 000 ₽', 'detail' => 'Общая сумма покупок'],
    ['level' => 'Уровень 3', 'percent' => '10%', 'condition' => 'От 20 000 ₽', 'detail' => 'Общая сумма покупок'],
    ['level' => 'Уровень 4', 'percent' => '15%', 'condition' => 'От 50 000 ₽', 'detail' => 'Максимальная скидка'],
];
?>

<section class="page-head page-head--green loyalty-hero">
    <div class="loyalty-hero__inner">
        <div class="loyalty-hero__copy">
            <span>Программа лояльности</span>
            <h1>Покупайте кофе и получайте постоянные привилегии</h1>
            <p>Скидка для розничных клиентов появляется после <a href="<?= esc_url($registration_url) ?>">регистрации</a> в личном кабинете и первого заказа на сайте и растет вместе с общей суммой покупок.</p>
        </div>
        <div class="loyalty-hero__maximum" aria-label="Максимальная скидка по программе — 15 процентов">
            <strong>15%</strong>
            <span>максимальная скидка</span>
        </div>
    </div>
</section>

<section class="section loyalty-program">
    <header class="loyalty-program__head">
        <div>
            <span class="eyebrow">Уровни программы</span>
            <h2>Скидка растет вместе с вашими покупками</h2>
        </div>
        <p>Мы учитываем общую сумму завершенных заказов. При достижении следующего уровня новая скидка применяется автоматически.</p>
    </header>

    <ol class="loyalty" aria-label="Уровни скидки">
        <?php foreach ($loyalty_levels as $item): ?>
            <li>
                <span class="loyalty__level"><?= esc_html($item['level']) ?></span>
                <strong><?= esc_html($item['percent']) ?></strong>
                <h3><?= esc_html($item['condition']) ?></h3>
                <p><?= esc_html($item['detail']) ?></p>
            </li>
        <?php endforeach; ?>
    </ol>

    <div class="loyalty-note">
        <span class="loyalty-note__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m20 6-11 11-5-5"/></svg>
        </span>
        <div>
            <strong>Дополнительные 5% при самовывозе</strong>
            <p>Скидка по программе лояльности суммируется со скидкой при самовывозе с производства.</p>
        </div>
    </div>

    <div class="loyalty-cta">
        <div>
            <span>Первый шаг</span>
            <h2>Создайте личный кабинет</h2>
            <p>Зарегистрируйтесь, оформите первый заказ и начните накапливать сумму покупок.</p>
        </div>
        <a class="button" href="<?= esc_url($registration_url) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 21a6 6 0 0 0-12 0"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
            Зарегистрироваться
        </a>
    </div>
</section>
