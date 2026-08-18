<?php
/**
 * Shared commerce data model for catalog, orders, payments and imports.
 *
 * @package ROASTBERRY_THEME
 */

function rb_order_statuses(): array
{
    return [
        'new' => 'Новый',
        'awaiting_payment' => 'Ожидает оплаты',
        'processing' => 'В обработке',
        'ready' => 'Готов к выдаче',
        'shipped' => 'Передан в доставку',
        'completed' => 'Завершен',
        'canceled' => 'Отменен',
    ];
}

function rb_payment_statuses(): array
{
    return [
        'not_required' => 'Оплата не требуется',
        'pending' => 'Ожидает оплаты',
        'succeeded' => 'Оплачен',
        'canceled' => 'Отменен',
        'refunded' => 'Возвращен',
    ];
}

function rb_product_integration_fields(): array
{
    return [
        'rb_external_id' => 'Идентификатор товара в 1С',
        'rb_sku' => 'Артикул / SKU',
        'rb_unit' => 'Единица измерения',
        'rb_vat_code' => 'Код НДС для оплаты',
        'rb_stock_200' => 'Остаток упаковок 200 г',
        'rb_stock_1000' => 'Остаток упаковок 1 кг',
        'rb_wholesale_price' => 'Оптовая цена',
        'rb_1c_updated_at' => 'Последнее обновление из 1С',
    ];
}

function rb_product_stock_by_size(int $product_id, string $size): ?int
{
    $variant_key = $size === '1000' ? 'rb_stock_1000' : 'rb_stock_200';
    $variant_stock = get_post_meta($product_id, $variant_key, true);
    if ($variant_stock !== '') {
        return max(0, (int) $variant_stock);
    }

    $legacy_stock = get_post_meta($product_id, 'rb_stock', true);
    return $legacy_stock === '' || !is_numeric($legacy_stock) ? null : max(0, (int) $legacy_stock);
}

function rb_cart_item_size(array $item): string
{
    return ($item['size_code'] ?? '') === '1000' || ($item['size'] ?? '') === '1 кг' ? '1000' : '200';
}

function rb_refresh_cart_from_catalog(array $cart, bool $clamp_quantity = false)
{
    $refreshed = [];
    $notices = [];

    foreach ($cart as $key => $item) {
        $product_id = absint($item['product_id'] ?? 0);
        if (!$product_id || get_post_type($product_id) !== 'rb_product' || get_post_status($product_id) !== 'publish') {
            $notices[] = 'Один из товаров больше недоступен и удален из корзины.';
            continue;
        }

        $size = rb_cart_item_size($item);
        $quantity = max(1, absint($item['quantity'] ?? 1));
        $price = rb_product_price_by_size($product_id, $size);
        $stock = rb_product_stock_by_size($product_id, $size);
        $title = get_the_title($product_id) ?: 'Товар';

        if ($price <= 0) {
            return new WP_Error('rb_product_price_missing', sprintf('Для товара «%s» не указана актуальная цена.', $title));
        }
        if ($stock !== null && $quantity > $stock) {
            if (!$clamp_quantity) {
                return new WP_Error('rb_product_stock_insufficient', sprintf('Товара «%s» в выбранной фасовке доступно: %d шт.', $title, $stock));
            }
            if ($stock < 1) {
                $notices[] = sprintf('Товар «%s» закончился и удален из корзины.', $title);
                continue;
            }
            $quantity = $stock;
            $notices[] = sprintf('Количество товара «%s» уменьшено до доступного остатка: %d шт.', $title, $stock);
        }

        $item['title'] = $title;
        $item['url'] = get_permalink($product_id);
        $item['image'] = get_the_post_thumbnail_url($product_id, 'thumbnail') ?: rb_asset_url('img/1.webp');
        $item['size_code'] = $size;
        $item['size'] = $size === '1000' ? '1 кг' : '200 г';
        $item['quantity'] = $quantity;
        $item['price'] = $price;
        $refreshed[(string) $key] = $item;
    }

    return ['cart' => $refreshed, 'notices' => array_values(array_unique($notices))];
}

function rb_cart_snapshot(?array $cart = null): array
{
    $cart = $cart ?? rb_get_cart();
    $snapshot = [];

    foreach ($cart as $key => $item) {
        $product_id = absint($item['product_id'] ?? 0);
        if (!$product_id || get_post_type($product_id) !== 'rb_product') {
            continue;
        }

        $size = rb_cart_item_size($item);
        $quantity = max(1, absint($item['quantity'] ?? 1));
        $unit_price = rb_product_price_by_size($product_id, $size);
        $title = get_the_title($product_id);

        $snapshot[] = [
            'key' => sanitize_text_field((string) $key),
            'product_id' => $product_id,
            'external_id' => sanitize_text_field((string) get_post_meta($product_id, 'rb_external_id', true)),
            'sku' => sanitize_text_field((string) (get_post_meta($product_id, 'rb_sku', true) ?: 'RB-' . $product_id)),
            'title' => sanitize_text_field($title ?: (string) ($item['title'] ?? 'Товар')),
            'size' => $size,
            'size_label' => $size === '1000' ? '1 кг' : '200 г',
            'grind' => sanitize_text_field((string) ($item['grind'] ?? 'В зернах')),
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'line_total' => $unit_price * $quantity,
            'vat_code' => sanitize_text_field((string) (get_post_meta($product_id, 'rb_vat_code', true) ?: '1')),
        ];
    }

    return $snapshot;
}

function rb_order_items_subtotal(array $items): int
{
    return array_sum(array_map(static fn(array $item): int => max(0, (int) ($item['line_total'] ?? 0)), $items));
}

function rb_order_items_text(array $items): string
{
    $lines = [];
    foreach ($items as $item) {
        $lines[] = sprintf(
            '%s, %s, %s, %d шт. — %s',
            $item['title'] ?? '',
            $item['size_label'] ?? '',
            $item['grind'] ?? '',
            (int) ($item['quantity'] ?? 1),
            rb_format_price((int) ($item['line_total'] ?? 0))
        );
    }

    return implode("\n", $lines);
}

function rb_get_order_items(int $order_id): array
{
    $items = get_post_meta($order_id, 'rb_order_items_data', true);
    return is_array($items) ? $items : [];
}

function rb_reserve_order_stock(int $order_id)
{
    if (get_post_meta($order_id, 'rb_stock_reserved', true) === '1') {
        return true;
    }

    if (!rb_acquire_stock_lock()) {
        return new WP_Error('rb_stock_lock_timeout', 'Остатки сейчас обновляются другим заказом. Повторите оформление через несколько секунд.');
    }

    try {
        $items = rb_get_order_items($order_id);
        foreach ($items as $item) {
            $product_id = absint($item['product_id'] ?? 0);
            $size = ($item['size'] ?? '') === '1000' ? '1000' : '200';
            $quantity = max(1, absint($item['quantity'] ?? 1));
            $stock = rb_product_stock_by_size($product_id, $size);
            if ($stock !== null && $quantity > $stock) {
                return new WP_Error('rb_stock_reservation_failed', sprintf(
                    'Не удалось зарезервировать «%s»: доступно %d шт.',
                    sanitize_text_field((string) ($item['title'] ?? 'товар')),
                    $stock
                ));
            }
        }

        foreach ($items as $item) {
            $product_id = absint($item['product_id'] ?? 0);
            $size = ($item['size'] ?? '') === '1000' ? '1000' : '200';
            $quantity = max(1, absint($item['quantity'] ?? 1));
            $stock = rb_product_stock_by_size($product_id, $size);
            if ($stock === null) continue;
            update_post_meta($product_id, $size === '1000' ? 'rb_stock_1000' : 'rb_stock_200', max(0, $stock - $quantity));
            rb_update_product_total_stock($product_id);
        }
        update_post_meta($order_id, 'rb_stock_reserved', '1');
        return true;
    } finally {
        rb_release_stock_lock();
    }
}

function rb_release_order_stock(int $order_id): void
{
    if (get_post_meta($order_id, 'rb_stock_reserved', true) !== '1' || get_post_meta($order_id, 'rb_stock_released', true) === '1') {
        return;
    }

    if (!rb_acquire_stock_lock()) {
        if (!wp_next_scheduled('rb_release_order_stock_event', [$order_id])) {
            wp_schedule_single_event(time() + 10, 'rb_release_order_stock_event', [$order_id]);
        }
        return;
    }
    try {
        foreach (rb_get_order_items($order_id) as $item) {
            $product_id = absint($item['product_id'] ?? 0);
            $size = ($item['size'] ?? '') === '1000' ? '1000' : '200';
            $quantity = max(1, absint($item['quantity'] ?? 1));
            $stock = rb_product_stock_by_size($product_id, $size);
            if ($product_id && $stock !== null) {
                update_post_meta($product_id, $size === '1000' ? 'rb_stock_1000' : 'rb_stock_200', $stock + $quantity);
                rb_update_product_total_stock($product_id);
            }
        }
        update_post_meta($order_id, 'rb_stock_released', '1');
    } finally {
        rb_release_stock_lock();
    }
}
add_action('rb_release_order_stock_event', 'rb_release_order_stock');

function rb_acquire_stock_lock(): bool
{
    $key = 'rb_stock_reservation_lock';
    for ($attempt = 0; $attempt < 20; $attempt++) {
        if (add_option($key, time(), '', false)) return true;
        $created_at = (int) get_option($key, 0);
        if ($created_at > 0 && $created_at < time() - 15) delete_option($key);
        usleep(100000);
    }
    return false;
}

function rb_release_stock_lock(): void
{
    delete_option('rb_stock_reservation_lock');
}

function rb_update_product_total_stock(int $product_id): void
{
    $stock_200 = rb_product_stock_by_size($product_id, '200');
    $stock_1000 = rb_product_stock_by_size($product_id, '1000');
    if ($stock_200 !== null || $stock_1000 !== null) {
        update_post_meta($product_id, 'rb_stock', max(0, (int) $stock_200) + max(0, (int) $stock_1000));
    }
}

function rb_store_schema_upgrade(): void
{
    $current_version = (int) get_option('rb_store_schema_version', 0);
    if ($current_version >= 3) {
        return;
    }

    $products = get_posts([
        'post_type' => 'rb_product',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);
    foreach ($products as $product_id) {
        if (get_post_meta($product_id, 'rb_sku', true) === '') {
            update_post_meta($product_id, 'rb_sku', 'RB-' . $product_id);
        }
        if (get_post_meta($product_id, 'rb_unit', true) === '') {
            update_post_meta($product_id, 'rb_unit', 'шт');
        }
        if (get_post_meta($product_id, 'rb_vat_code', true) === '') {
            update_post_meta($product_id, 'rb_vat_code', '1');
        }
    }

    add_role('rb_retail_customer', 'Розничный покупатель', ['read' => true]);
    add_role('rb_business_customer', 'Юрлицо', ['read' => true]);

    $legacy_statuses = [
        'Новый' => 'new',
        'Ожидает оплаты' => 'awaiting_payment',
        'В обработке' => 'processing',
        'Готов' => 'ready',
        'Готов к выдаче' => 'ready',
        'Передан в доставку' => 'shipped',
        'Завершен' => 'completed',
        'Отменен' => 'canceled',
    ];
    $orders = get_posts(['post_type' => 'rb_order', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids']);
    foreach ($orders as $order_id) {
        $status = (string) get_post_meta($order_id, 'rb_order_status', true);
        if (isset($legacy_statuses[$status])) update_post_meta($order_id, 'rb_order_status', $legacy_statuses[$status]);
        elseif ($status === '') update_post_meta($order_id, 'rb_order_status', 'new');
        if (get_post_meta($order_id, 'rb_payment_status', true) === '') update_post_meta($order_id, 'rb_payment_status', 'not_required');
        if (get_post_meta($order_id, 'rb_order_type', true) === '') update_post_meta($order_id, 'rb_order_type', 'retail');
        if (get_post_meta($order_id, 'rb_order_total_amount', true) === '') {
            update_post_meta($order_id, 'rb_order_total_amount', rb_parse_price((string) get_post_meta($order_id, 'rb_order_total', true)));
        }
    }

    update_option('rb_store_schema_version', 3, false);
    flush_rewrite_rules(false);
}
add_action('admin_init', 'rb_store_schema_upgrade');
