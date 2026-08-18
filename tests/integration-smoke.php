<?php
/**
 * Pure integration helpers smoke test; does not require a WordPress bootstrap.
 */

function add_action(...$arguments): void {}
function add_filter(...$arguments): void {}
function sanitize_text_field($value): string { return trim((string) $value); }

require dirname(__DIR__) . '/includes/business.php';
require dirname(__DIR__) . '/includes/commerceml.php';
require dirname(__DIR__) . '/includes/yookassa.php';

$valid_inn = rb_validate_inn('590418682504');
$invalid_inn = rb_validate_inn('590418682505');
$offer = rb_1c_simplexml(
    '<Предложение><Ид>product#variant</Ид><Наименование>Кофе 1 кг</Наименование>'
    . '<Цены><Цена><ИдТипаЦены>retail</ИдТипаЦены><ЦенаЗаЕдиницу>450,50</ЦенаЗаЕдиницу></Цена></Цены>'
    . '<Склад КоличествоНаСкладе="3"/><Склад КоличествоНаСкладе="2"/></Предложение>'
);
$prices = rb_1c_offer_prices($offer);

$checks = [
    'valid INN accepted' => $valid_inn,
    'invalid INN rejected' => !$invalid_inn,
    'one kilogram offer detected' => rb_1c_offer_size($offer) === '1000',
    'warehouse stock summed' => rb_1c_offer_quantity($offer) === 5,
    'decimal CommerceML price parsed' => isset($prices['retail']) && abs($prices['retail'] - 450.5) < 0.001,
    'YooKassa money formatted' => rb_yookassa_money(450) === '450.00',
];

foreach ($checks as $label => $passed) {
    echo ($passed ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
}

exit(in_array(false, $checks, true) ? 1 : 0);

