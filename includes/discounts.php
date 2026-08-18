<?php
/**
 * Retail loyalty and promotional discounts.
 *
 * @package ROASTBERRY_THEME
 */

add_action('init', 'rb_register_promocode_type', 11);
function rb_register_promocode_type(): void
{
    register_post_type('rb_promocode', [
        'labels' => [
            'name' => 'Промокоды',
            'singular_name' => 'Промокод',
            'add_new_item' => 'Добавить промокод',
            'edit_item' => 'Редактировать промокод',
            'menu_name' => 'Промокоды',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'rb-roasters',
        'supports' => ['title'],
        'map_meta_cap' => true,
    ]);
}

add_action('add_meta_boxes_rb_promocode', 'rb_add_promocode_meta_box');
function rb_add_promocode_meta_box(): void
{
    add_meta_box('rb-promocode-settings', 'Условия промокода', 'rb_render_promocode_meta_box', 'rb_promocode', 'normal', 'high');
}

function rb_render_promocode_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_promocode', 'rb_promocode_nonce');
    $type = (string) (get_post_meta($post->ID, 'rb_promo_type', true) ?: 'percent');
    ?>
    <p class="description">Название записи используется как код. Рекомендуемый формат: <code>WELCOME10</code>.</p>
    <table class="form-table" role="presentation">
        <tr><th><label for="rb-promo-type">Тип скидки</label></th><td><select id="rb-promo-type" name="rb_promo_type"><option value="percent" <?= selected($type, 'percent', false) ?>>Процент</option><option value="fixed" <?= selected($type, 'fixed', false) ?>>Фиксированная сумма</option></select></td></tr>
        <tr><th><label for="rb-promo-value">Значение</label></th><td><input id="rb-promo-value" name="rb_promo_value" type="number" min="1" step="1" value="<?= esc_attr((string) get_post_meta($post->ID, 'rb_promo_value', true)) ?>" required></td></tr>
        <tr><th><label for="rb-promo-minimum">Минимальная сумма товаров, ₽</label></th><td><input id="rb-promo-minimum" name="rb_promo_minimum" type="number" min="0" step="1" value="<?= esc_attr((string) get_post_meta($post->ID, 'rb_promo_minimum', true)) ?>"></td></tr>
        <tr><th><label for="rb-promo-expires">Действует до</label></th><td><input id="rb-promo-expires" name="rb_promo_expires" type="date" value="<?= esc_attr((string) get_post_meta($post->ID, 'rb_promo_expires', true)) ?>"><p class="description">Пустое поле означает бессрочный промокод.</p></td></tr>
        <tr><th><label for="rb-promo-limit">Общий лимит применений</label></th><td><input id="rb-promo-limit" name="rb_promo_limit" type="number" min="0" step="1" value="<?= esc_attr((string) get_post_meta($post->ID, 'rb_promo_limit', true)) ?>"><p class="description">0 или пустое поле означает без ограничений.</p></td></tr>
    </table>
    <?php
}

add_action('save_post_rb_promocode', 'rb_save_promocode_meta');
function rb_save_promocode_meta(int $post_id): void
{
    if (!isset($_POST['rb_promocode_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_promocode_nonce'])), 'rb_save_promocode')) {
        return;
    }
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $type = isset($_POST['rb_promo_type']) && $_POST['rb_promo_type'] === 'fixed' ? 'fixed' : 'percent';
    update_post_meta($post_id, 'rb_promo_code', rb_normalize_promocode(get_the_title($post_id)));
    update_post_meta($post_id, 'rb_promo_type', $type);
    update_post_meta($post_id, 'rb_promo_value', max(1, absint($_POST['rb_promo_value'] ?? 1)));
    update_post_meta($post_id, 'rb_promo_minimum', max(0, absint($_POST['rb_promo_minimum'] ?? 0)));
    update_post_meta($post_id, 'rb_promo_limit', max(0, absint($_POST['rb_promo_limit'] ?? 0)));
    update_post_meta($post_id, 'rb_promo_expires', sanitize_text_field((string) ($_POST['rb_promo_expires'] ?? '')));
}

function rb_normalize_promocode(string $code): string
{
    $code = trim(sanitize_text_field($code));
    return function_exists('mb_strtoupper') ? mb_strtoupper($code) : strtoupper($code);
}

function rb_find_promocode(string $code): int
{
    $code = rb_normalize_promocode($code);
    if ($code === '') {
        return 0;
    }
    $ids = get_posts([
        'post_type' => 'rb_promocode',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => 'rb_promo_code',
        'meta_value' => $code,
    ]);
    return $ids ? (int) $ids[0] : 0;
}

function rb_promocode_usage_count(int $promocode_id): int
{
    $orders = get_posts([
        'post_type' => 'rb_order',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'AND',
            ['key' => 'rb_promocode_id', 'value' => $promocode_id, 'compare' => '='],
            ['key' => 'rb_order_status', 'value' => 'canceled', 'compare' => '!='],
        ],
    ]);
    return count($orders);
}

function rb_user_loyalty_data(int $user_id): array
{
    if (!$user_id) {
        return ['spend' => 0, 'orders' => 0, 'percent' => 0];
    }
    $user = get_userdata($user_id);
    if (!$user || in_array('rb_business_customer', (array) $user->roles, true)) {
        return ['spend' => 0, 'orders' => 0, 'percent' => 0];
    }

    $orders = get_posts([
        'post_type' => 'rb_order',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => 'rb_order_status',
            'value' => ['processing', 'ready', 'shipped', 'completed'],
            'compare' => 'IN',
        ]],
    ]);
    $spend = 0;
    foreach ($orders as $order_id) {
        $spend += max(0, (int) get_post_meta($order_id, 'rb_order_total_amount', true) - (int) get_post_meta($order_id, 'rb_delivery_cost_amount', true));
    }

    $percent = 0;
    if ($orders) $percent = 3;
    if ($spend >= 10000) $percent = 5;
    if ($spend >= 20000) $percent = 10;
    if ($spend >= 50000) $percent = 15;
    return ['spend' => $spend, 'orders' => count($orders), 'percent' => $percent];
}

function rb_calculate_order_discounts(int $subtotal, string $delivery_method, string $promocode, int $user_id)
{
    $subtotal = max(0, $subtotal);
    $loyalty = rb_user_loyalty_data($user_id);
    $loyalty_percent = (int) $loyalty['percent'];
    $pickup_percent = $delivery_method === 'pickup_production' ? 5 : 0;
    $base_discount = (int) round($subtotal * ($loyalty_percent + $pickup_percent) / 100);
    $promo_discount = 0;
    $promocode_id = 0;
    $normalized_code = rb_normalize_promocode($promocode);

    if ($normalized_code !== '') {
        $promocode_id = rb_find_promocode($normalized_code);
        if (!$promocode_id) {
            return new WP_Error('rb_promocode_invalid', 'Промокод не найден или отключен.');
        }
        $expires = (string) get_post_meta($promocode_id, 'rb_promo_expires', true);
        if ($expires !== '' && $expires < current_time('Y-m-d')) {
            return new WP_Error('rb_promocode_expired', 'Срок действия промокода истек.');
        }
        $minimum = max(0, (int) get_post_meta($promocode_id, 'rb_promo_minimum', true));
        if ($subtotal < $minimum) {
            return new WP_Error('rb_promocode_minimum', sprintf('Промокод действует при сумме товаров от %s.', rb_format_price($minimum)));
        }
        $limit = max(0, (int) get_post_meta($promocode_id, 'rb_promo_limit', true));
        if ($limit > 0 && rb_promocode_usage_count($promocode_id) >= $limit) {
            return new WP_Error('rb_promocode_limit', 'Лимит применений промокода исчерпан.');
        }
        $value = max(1, (int) get_post_meta($promocode_id, 'rb_promo_value', true));
        $promo_discount = get_post_meta($promocode_id, 'rb_promo_type', true) === 'fixed'
            ? $value
            : (int) round(($subtotal - $base_discount) * min(100, $value) / 100);
    }

    $total_discount = min($subtotal, $base_discount + $promo_discount);
    return [
        'total' => $total_discount,
        'loyalty_percent' => $loyalty_percent,
        'pickup_percent' => $pickup_percent,
        'promo_discount' => min($promo_discount, max(0, $subtotal - $base_discount)),
        'promocode_id' => $promocode_id,
        'promocode' => $normalized_code,
    ];
}

add_action('rest_api_init', 'rb_register_discount_quote_route');
function rb_register_discount_quote_route(): void
{
    register_rest_route('rb/v1', '/discount-quote', [
        'methods' => 'POST',
        'callback' => 'rb_discount_quote',
        'permission_callback' => '__return_true',
    ]);
}

function rb_discount_quote(WP_REST_Request $request)
{
    $nonce = (string) $request->get_header('X-RB-Nonce');
    if (!$nonce || !wp_verify_nonce($nonce, 'rb_cart_discount')) {
        return new WP_Error('rb_discount_nonce', 'Обновите страницу и попробуйте снова.', ['status' => 403]);
    }
    $subtotal = rb_order_items_subtotal(rb_cart_snapshot());
    $method = sanitize_key((string) $request->get_param('delivery_method'));
    if (!in_array($method, ['pickup_cafe', 'pickup_production', 'cdek'], true)) $method = 'pickup_cafe';
    $result = rb_calculate_order_discounts($subtotal, $method, sanitize_text_field((string) $request->get_param('promocode')), get_current_user_id());
    if (is_wp_error($result)) {
        return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => 422]);
    }
    return rest_ensure_response([
        'discount_total' => (int) $result['total'],
        'promocode' => (string) $result['promocode'],
        'message' => $result['promocode'] !== '' ? 'Промокод применен' : '',
    ]);
}
