<?php
/**
 * YooKassa payments without an ecommerce plugin.
 *
 * @package ROASTBERRY_THEME
 */

function rb_yookassa_default_settings(): array
{
    return [
        'enabled' => '0',
        'shop_id' => '',
        'secret_key' => '',
        'vat_code' => '1',
        'tax_system_code' => '',
    ];
}

function rb_yookassa_settings(): array
{
    return wp_parse_args((array) get_option('rb_yookassa_settings', []), rb_yookassa_default_settings());
}

function rb_yookassa_is_configured(): bool
{
    $settings = rb_yookassa_settings();
    return $settings['enabled'] === '1' && $settings['shop_id'] !== '' && $settings['secret_key'] !== '';
}

add_action('admin_init', 'rb_yookassa_register_settings');
function rb_yookassa_register_settings(): void
{
    register_setting('rb_yookassa_settings_group', 'rb_yookassa_settings', [
        'type' => 'array',
        'sanitize_callback' => 'rb_yookassa_sanitize_settings',
        'default' => rb_yookassa_default_settings(),
    ]);
}

function rb_yookassa_sanitize_settings(array $input): array
{
    $current = rb_yookassa_settings();
    $secret_key = trim((string) ($input['secret_key'] ?? ''));

    return [
        'enabled' => empty($input['enabled']) ? '0' : '1',
        'shop_id' => sanitize_text_field((string) ($input['shop_id'] ?? '')),
        'secret_key' => $secret_key === '' ? $current['secret_key'] : sanitize_text_field($secret_key),
        'vat_code' => in_array((string) ($input['vat_code'] ?? ''), ['1', '2', '3', '4', '5', '6'], true) ? (string) $input['vat_code'] : '1',
        'tax_system_code' => in_array((string) ($input['tax_system_code'] ?? ''), ['', '1', '2', '3', '4', '5', '6'], true) ? (string) $input['tax_system_code'] : '',
    ];
}

add_action('admin_menu', 'rb_yookassa_admin_menu', 21);
function rb_yookassa_admin_menu(): void
{
    add_submenu_page('rb-roasters', 'Онлайн-оплата', 'Онлайн-оплата', 'manage_options', 'rb-yookassa', 'rb_yookassa_render_settings_page');
}

function rb_yookassa_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = rb_yookassa_settings();
    ?>
    <div class="wrap">
        <h1>Онлайн-оплата YooKassa</h1>
        <p>После сохранения ключей платежи создаются на сервере. Секретный ключ не передается в браузер.</p>
        <form method="post" action="options.php">
            <?php settings_fields('rb_yookassa_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr><th>Прием платежей</th><td><label><input type="checkbox" name="rb_yookassa_settings[enabled]" value="1" <?= checked($settings['enabled'], '1', false) ?>> Включить YooKassa после сохранения ключей</label></td></tr>
                <tr><th><label for="rb-yookassa-shop-id">shopId</label></th><td><input class="regular-text" id="rb-yookassa-shop-id" name="rb_yookassa_settings[shop_id]" value="<?= esc_attr($settings['shop_id']) ?>" autocomplete="off"></td></tr>
                <tr><th><label for="rb-yookassa-secret">Секретный ключ</label></th><td><input class="regular-text" type="password" id="rb-yookassa-secret" name="rb_yookassa_settings[secret_key]" value="" placeholder="<?= $settings['secret_key'] !== '' ? esc_attr('Ключ сохранен, оставьте поле пустым') : '' ?>" autocomplete="new-password"></td></tr>
                <tr><th><label for="rb-yookassa-vat">НДС по умолчанию</label></th><td><select id="rb-yookassa-vat" name="rb_yookassa_settings[vat_code]"><?php foreach (rb_yookassa_vat_codes() as $code => $label): ?><option value="<?= esc_attr($code) ?>" <?= selected($settings['vat_code'], $code, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select></td></tr>
                <tr><th><label for="rb-yookassa-tax-system">Система налогообложения</label></th><td><select id="rb-yookassa-tax-system" name="rb_yookassa_settings[tax_system_code]"><?php foreach (rb_yookassa_tax_systems() as $code => $label): ?><option value="<?= esc_attr($code) ?>" <?= selected($settings['tax_system_code'], $code, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select><p class="description">Оставьте «по настройкам YooKassa», если в кабинете используется одна система.</p></td></tr>
            </table>
            <?php submit_button('Сохранить настройки'); ?>
        </form>
        <h2>Webhook</h2>
        <p>Укажите этот адрес в кабинете YooKassa для событий <code>payment.succeeded</code> и <code>payment.canceled</code>:</p>
        <p><code><?= esc_html(rest_url('rb/v1/yookassa/webhook')) ?></code></p>
    </div>
    <?php
}

function rb_yookassa_vat_codes(): array
{
    return ['1' => 'Без НДС', '2' => 'НДС 0%', '3' => 'НДС 10%', '4' => 'НДС 20%', '5' => 'НДС 10/110', '6' => 'НДС 20/120'];
}

function rb_yookassa_tax_systems(): array
{
    return ['' => 'По настройкам YooKassa', '1' => 'ОСН', '2' => 'УСН доходы', '3' => 'УСН доходы минус расходы', '4' => 'ЕСХН', '5' => 'Патент', '6' => 'НПД'];
}

function rb_yookassa_money(int $amount): string
{
    return number_format(max(0, $amount), 2, '.', '');
}

function rb_yookassa_api_request(string $method, string $path, ?array $body = null, string $idempotence_key = '')
{
    if (!rb_yookassa_is_configured()) {
        return new WP_Error('rb_yookassa_not_configured', 'Онлайн-оплата пока не настроена.');
    }

    $settings = rb_yookassa_settings();
    $headers = [
        'Authorization' => 'Basic ' . base64_encode($settings['shop_id'] . ':' . $settings['secret_key']),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];
    if ($idempotence_key !== '') {
        $headers['Idempotence-Key'] = $idempotence_key;
    }

    $arguments = ['method' => $method, 'headers' => $headers, 'timeout' => 30];
    if ($body !== null) {
        $arguments['body'] = wp_json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $response = wp_remote_request('https://api.yookassa.ru/v3/' . ltrim($path, '/'), $arguments);
    if (is_wp_error($response)) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($status < 200 || $status >= 300 || !is_array($data)) {
        $description = is_array($data) ? (string) ($data['description'] ?? $data['code'] ?? '') : '';
        return new WP_Error('rb_yookassa_api_error', $description ?: 'YooKassa вернула ошибку при обработке запроса.', ['status' => $status]);
    }

    return $data;
}

function rb_yookassa_receipt_items(int $order_id): array
{
    $settings = rb_yookassa_settings();
    $receipt_items = [];
    $discount_left = max(0, (int) get_post_meta($order_id, 'rb_discount_total_amount', true));
    foreach (rb_get_order_items($order_id) as $item) {
        $vat_code = (string) ($item['vat_code'] ?? $settings['vat_code']);
        if (!array_key_exists($vat_code, rb_yookassa_vat_codes())) {
            $vat_code = $settings['vat_code'];
        }
        $description = (string) ($item['title'] ?? 'Товар') . ', ' . (string) ($item['size_label'] ?? '');
        $description = function_exists('mb_substr') ? mb_substr($description, 0, 128) : substr($description, 0, 128);
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $unit_price = max(0, (int) ($item['unit_price'] ?? 0));
        for ($unit = 0; $unit < $quantity; $unit++) {
            $unit_discount = min($discount_left, $unit_price);
            $discount_left -= $unit_discount;
            $discounted_price = $unit_price - $unit_discount;
            if ($discounted_price <= 0) {
                continue;
            }
            $receipt_items[] = [
                'description' => $description,
                'quantity' => '1.000',
                'amount' => ['value' => rb_yookassa_money($discounted_price), 'currency' => 'RUB'],
                'vat_code' => (int) $vat_code,
                'payment_mode' => 'full_payment',
                'payment_subject' => 'commodity',
            ];
        }
    }

    $delivery_cost = (int) get_post_meta($order_id, 'rb_delivery_cost_amount', true);
    if ($delivery_cost > 0) {
        $receipt_items[] = [
            'description' => 'Доставка заказа',
            'quantity' => '1.000',
            'amount' => ['value' => rb_yookassa_money($delivery_cost), 'currency' => 'RUB'],
            'vat_code' => (int) $settings['vat_code'],
            'payment_mode' => 'full_payment',
            'payment_subject' => 'service',
        ];
    }

    return $receipt_items;
}

function rb_yookassa_create_payment(int $order_id)
{
    $total = (int) get_post_meta($order_id, 'rb_order_total_amount', true);
    if ($total <= 0) {
        return new WP_Error('rb_yookassa_invalid_total', 'Сумма заказа должна быть больше нуля.');
    }

    $return_token = (string) get_post_meta($order_id, 'rb_payment_return_token', true);
    if ($return_token === '') {
        $return_token = wp_generate_password(32, false, false);
        update_post_meta($order_id, 'rb_payment_return_token', $return_token);
    }
    $idempotence_key = (string) get_post_meta($order_id, 'rb_payment_idempotence_key', true);
    if ($idempotence_key === '') {
        $idempotence_key = wp_generate_uuid4();
        update_post_meta($order_id, 'rb_payment_idempotence_key', $idempotence_key);
    }

    $body = [
        'amount' => ['value' => rb_yookassa_money($total), 'currency' => 'RUB'],
        'capture' => true,
        'confirmation' => [
            'type' => 'redirect',
            'return_url' => add_query_arg(['rb_payment_return' => $order_id, 'token' => $return_token], home_url('/')),
        ],
        'description' => 'Заказ №' . $order_id . ' Roastberry Coffee Roasters',
        'metadata' => ['order_id' => (string) $order_id],
    ];

    $email = sanitize_email((string) get_post_meta($order_id, 'rb_customer_email', true));
    $phone = rb_normalize_phone((string) get_post_meta($order_id, 'rb_customer_phone', true));
    if ($email !== '' || $phone !== '') {
        $customer = $email !== '' ? ['email' => $email] : ['phone' => '+' . $phone];
        $body['receipt'] = ['customer' => $customer, 'items' => rb_yookassa_receipt_items($order_id)];
        $tax_system_code = (string) rb_yookassa_settings()['tax_system_code'];
        if ($tax_system_code !== '') {
            $body['receipt']['tax_system_code'] = (int) $tax_system_code;
        }
    }

    $payment = rb_yookassa_api_request('POST', 'payments', $body, $idempotence_key);
    if (is_wp_error($payment)) {
        return $payment;
    }

    update_post_meta($order_id, 'rb_payment_id', sanitize_text_field((string) ($payment['id'] ?? '')));
    update_post_meta($order_id, 'rb_payment_status', sanitize_key((string) ($payment['status'] ?? 'pending')));
    update_post_meta($order_id, 'rb_payment_method', 'yookassa');
    update_post_meta($order_id, 'rb_payment_payload', wp_json_encode($payment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $confirmation_url = (string) ($payment['confirmation']['confirmation_url'] ?? '');
    return $confirmation_url !== '' ? $confirmation_url : new WP_Error('rb_yookassa_confirmation_missing', 'YooKassa не вернула ссылку на оплату.');
}

add_action('rest_api_init', 'rb_yookassa_register_webhook');
function rb_yookassa_register_webhook(): void
{
    register_rest_route('rb/v1', '/yookassa/webhook', [
        'methods' => 'POST',
        'callback' => 'rb_yookassa_webhook',
        'permission_callback' => '__return_true',
    ]);
}

function rb_yookassa_webhook(WP_REST_Request $request)
{
    $event = $request->get_json_params();
    $payment_id = sanitize_text_field((string) ($event['object']['id'] ?? ''));
    $event_order_id = absint($event['object']['metadata']['order_id'] ?? 0);
    if ($payment_id === '' || !$event_order_id || get_post_type($event_order_id) !== 'rb_order' || get_post_meta($event_order_id, 'rb_payment_id', true) !== $payment_id) {
        return new WP_Error('rb_yookassa_payment_missing', 'Не указан идентификатор платежа.', ['status' => 400]);
    }

    $payment = rb_yookassa_api_request('GET', 'payments/' . rawurlencode($payment_id));
    if (is_wp_error($payment)) {
        return $payment;
    }

    $order_id = absint($payment['metadata']['order_id'] ?? 0);
    if (!$order_id || get_post_type($order_id) !== 'rb_order' || get_post_meta($order_id, 'rb_payment_id', true) !== $payment_id) {
        return new WP_Error('rb_yookassa_order_missing', 'Заказ платежа не найден.', ['status' => 404]);
    }
    $expected = rb_yookassa_money((int) get_post_meta($order_id, 'rb_order_total_amount', true));
    if (($payment['amount']['currency'] ?? '') !== 'RUB' || (string) ($payment['amount']['value'] ?? '') !== $expected) {
        return new WP_Error('rb_yookassa_amount_mismatch', 'Сумма платежа не совпадает с заказом.', ['status' => 409]);
    }

    rb_yookassa_apply_payment_status($order_id, $payment);
    return rest_ensure_response(['success' => true]);
}

function rb_yookassa_apply_payment_status(int $order_id, array $payment): void
{
    $status = sanitize_key((string) ($payment['status'] ?? 'pending'));
    update_post_meta($order_id, 'rb_payment_status', $status);
    update_post_meta($order_id, 'rb_payment_payload', wp_json_encode($payment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($status === 'succeeded' && !empty($payment['paid'])) {
        update_post_meta($order_id, 'rb_order_status', 'processing');
        update_post_meta($order_id, 'rb_paid_at', current_time('mysql'));
        rb_notify_managers_order($order_id, 'paid');
        rb_notify_customer_order($order_id, 'paid');
    } elseif ($status === 'canceled') {
        update_post_meta($order_id, 'rb_order_status', 'canceled');
        rb_release_order_stock($order_id);
    }
}

add_action('template_redirect', 'rb_yookassa_handle_return', 5);
function rb_yookassa_handle_return(): void
{
    if (empty($_GET['rb_payment_return'])) {
        return;
    }
    $order_id = absint($_GET['rb_payment_return']);
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    $stored_token = (string) get_post_meta($order_id, 'rb_payment_return_token', true);
    if (!$order_id || $stored_token === '' || !hash_equals($stored_token, $token)) {
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    $payment_id = (string) get_post_meta($order_id, 'rb_payment_id', true);
    if ($payment_id !== '' && rb_yookassa_is_configured()) {
        $payment = rb_yookassa_api_request('GET', 'payments/' . rawurlencode($payment_id));
        if (!is_wp_error($payment)) {
            rb_yookassa_apply_payment_status($order_id, $payment);
        }
    }
    $status = (string) get_post_meta($order_id, 'rb_payment_status', true);
    if (get_post_meta($order_id, 'rb_order_type', true) === 'business') {
        wp_safe_redirect(add_query_arg('business_result', $status === 'succeeded' ? 'payment_success' : 'payment_pending', route_url('business-account')));
        exit;
    }
    $_SESSION['rb_payment_notice'] = [
        'order_id' => $order_id,
        'status' => $status === 'succeeded' ? 'success' : 'pending',
    ];
    wp_safe_redirect(route_url('cart'));
    exit;
}

function rb_pull_payment_notice(): array
{
    $notice = isset($_SESSION['rb_payment_notice']) && is_array($_SESSION['rb_payment_notice']) ? $_SESSION['rb_payment_notice'] : [];
    unset($_SESSION['rb_payment_notice']);
    return $notice;
}
