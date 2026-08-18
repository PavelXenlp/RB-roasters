<?php
/**
 * Business customer registration and wholesale requests.
 *
 * @package ROASTBERRY_THEME
 */

function rb_is_business_customer(int $user_id = 0): bool
{
    $user = get_userdata($user_id ?: get_current_user_id());
    return $user instanceof WP_User && in_array('rb_business_customer', (array) $user->roles, true);
}

function rb_business_is_approved(int $user_id = 0): bool
{
    $user_id = $user_id ?: get_current_user_id();
    return rb_is_business_customer($user_id) && get_user_meta($user_id, 'rb_business_status', true) === 'approved';
}

function rb_business_companies(int $user_id = 0): array
{
    $user_id = $user_id ?: get_current_user_id();
    $companies = get_user_meta($user_id, 'rb_business_companies', true);
    if (is_array($companies) && $companies) return $companies;
    $name = (string) get_user_meta($user_id, 'rb_company_name', true);
    $inn = (string) get_user_meta($user_id, 'rb_company_inn', true);
    if ($name === '' && $inn === '') return [];
    return [[
        'id' => 'primary',
        'name' => $name,
        'inn' => $inn,
        'kpp' => (string) get_user_meta($user_id, 'rb_company_kpp', true),
        'city' => (string) get_user_meta($user_id, 'rb_company_city', true),
        'address' => (string) get_user_meta($user_id, 'rb_company_address', true),
    ]];
}

function rb_business_company_from_request(array $source): array
{
    return [
        'id' => wp_generate_uuid4(),
        'name' => sanitize_text_field(wp_unslash($source['company_name'] ?? '')),
        'inn' => preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($source['inn'] ?? ''))),
        'kpp' => preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($source['kpp'] ?? ''))),
        'city' => sanitize_text_field(wp_unslash($source['city'] ?? '')),
        'address' => sanitize_textarea_field(wp_unslash($source['legal_address'] ?? '')),
    ];
}

function rb_validate_inn(string $inn): bool
{
    $inn = preg_replace('/\D+/', '', $inn);
    if (strlen($inn) === 10) {
        $weights = [2, 4, 10, 3, 5, 9, 4, 6, 8];
        $sum = 0;
        foreach ($weights as $index => $weight) $sum += (int) $inn[$index] * $weight;
        return (int) $inn[9] === ($sum % 11) % 10;
    }
    if (strlen($inn) === 12) {
        $weights_11 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
        $weights_12 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
        $sum_11 = $sum_12 = 0;
        foreach ($weights_11 as $index => $weight) $sum_11 += (int) $inn[$index] * $weight;
        foreach ($weights_12 as $index => $weight) $sum_12 += (int) $inn[$index] * $weight;
        return (int) $inn[10] === ($sum_11 % 11) % 10 && (int) $inn[11] === ($sum_12 % 11) % 10;
    }
    return false;
}

add_action('init', 'rb_handle_business_forms', 9);
function rb_handle_business_forms(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['rb_action'])) {
        return;
    }
    $action = sanitize_key(wp_unslash($_POST['rb_action']));
    if ($action === 'business_register') rb_handle_business_registration();
    if ($action === 'business_profile' && is_user_logged_in()) rb_handle_business_profile();
    if ($action === 'business_order' && is_user_logged_in()) rb_handle_business_order();
    if ($action === 'business_price_request') rb_handle_business_price_request();
    if ($action === 'business_company_add' && is_user_logged_in()) rb_handle_business_company_add();
}

function rb_business_redirect(string $result): void
{
    $url = add_query_arg('business_result', $result, route_url('business-account'));
    wp_safe_redirect(in_array($result, ['code', 'code_rate', 'code_invalid'], true) ? $url . '#b-code' : $url);
    exit;
}

function rb_handle_business_registration(): void
{
    if (!isset($_POST['rb_business_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_business_register_nonce'])), 'rb_business_register')) {
        rb_business_redirect('security');
    }
    if (!rb_validate_legal_consents()) rb_business_redirect('consent');
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $inn = preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['inn'] ?? '')));
    if (!$email || email_exists($email)) rb_business_redirect('email');
    if (!rb_is_valid_ru_phone($phone)) rb_business_redirect('phone');
    if (!rb_validate_inn($inn)) rb_business_redirect('inn');

    $payload = [
        'email' => $email,
        'phone' => rb_format_phone($phone),
        'full_name' => sanitize_text_field(wp_unslash($_POST['full_name'] ?? '')),
        'company_name' => sanitize_text_field(wp_unslash($_POST['company_name'] ?? '')),
        'inn' => $inn,
        'kpp' => preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['kpp'] ?? ''))),
        'city' => sanitize_text_field(wp_unslash($_POST['city'] ?? '')),
        'legal_address' => sanitize_textarea_field(wp_unslash($_POST['legal_address'] ?? '')),
        'legal_acceptance' => rb_legal_acceptance_data('business_registration'),
    ];
    $sent = rb_auth_send_code($email, 'business_register', $payload);
    rb_business_redirect(is_wp_error($sent) ? ($sent->get_error_code() === 'rb_auth_rate' ? 'code_rate' : 'mail') : 'code');
}

function rb_complete_business_registration(array $payload)
{
    $email = sanitize_email((string) ($payload['email'] ?? ''));
    if (!$email || email_exists($email)) return new WP_Error('rb_business_email_exists', 'Пользователь уже существует.');
    $name = sanitize_text_field((string) ($payload['full_name'] ?? ''));
    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'display_name' => $name ?: $email,
        'first_name' => $name,
        'user_pass' => wp_generate_password(32, true, true),
        'role' => get_role('rb_business_customer') ? 'rb_business_customer' : 'subscriber',
    ]);
    if (is_wp_error($user_id)) return $user_id;

    $fields = [
        'rb_phone' => rb_format_phone((string) ($payload['phone'] ?? '')),
        'rb_company_name' => sanitize_text_field((string) ($payload['company_name'] ?? '')),
        'rb_company_inn' => preg_replace('/\D+/', '', (string) ($payload['inn'] ?? '')),
        'rb_company_kpp' => preg_replace('/\D+/', '', (string) ($payload['kpp'] ?? '')),
        'rb_company_city' => sanitize_text_field((string) ($payload['city'] ?? '')),
        'rb_company_address' => sanitize_textarea_field((string) ($payload['legal_address'] ?? '')),
        'rb_business_status' => 'pending',
    ];
    foreach ($fields as $key => $value) update_user_meta($user_id, $key, $value);
    rb_record_user_legal_acceptance((int) $user_id, (array) ($payload['legal_acceptance'] ?? []));
    $company = [
        'id' => wp_generate_uuid4(),
        'name' => $fields['rb_company_name'],
        'inn' => $fields['rb_company_inn'],
        'kpp' => $fields['rb_company_kpp'],
        'city' => $fields['rb_company_city'],
        'address' => $fields['rb_company_address'],
    ];
    update_user_meta($user_id, 'rb_business_companies', [$company]);
    rb_notify_business_registration($user_id);
    return $user_id;
}

function rb_handle_business_profile(): void
{
    if (!rb_is_business_customer() || !isset($_POST['rb_business_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_business_profile_nonce'])), 'rb_business_profile')) {
        rb_business_redirect('security');
    }
    if (!rb_validate_legal_consents()) rb_business_redirect('consent');
    $user_id = get_current_user_id();
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $inn = preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['inn'] ?? '')));
    if (!rb_is_valid_ru_phone($phone)) rb_business_redirect('phone');
    if (!rb_validate_inn($inn)) rb_business_redirect('inn');
    update_user_meta($user_id, 'rb_phone', rb_format_phone($phone));
    update_user_meta($user_id, 'rb_company_name', sanitize_text_field(wp_unslash($_POST['company_name'] ?? '')));
    update_user_meta($user_id, 'rb_company_inn', $inn);
    update_user_meta($user_id, 'rb_company_kpp', preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['kpp'] ?? ''))));
    update_user_meta($user_id, 'rb_company_city', sanitize_text_field(wp_unslash($_POST['city'] ?? '')));
    update_user_meta($user_id, 'rb_company_address', sanitize_textarea_field(wp_unslash($_POST['legal_address'] ?? '')));
    $companies = rb_business_companies($user_id);
    $updated_company = rb_business_company_from_request($_POST);
    if ($companies) {
        $updated_company['id'] = (string) ($companies[0]['id'] ?? $updated_company['id']);
        $companies[0] = $updated_company;
    } else {
        $companies[] = $updated_company;
    }
    update_user_meta($user_id, 'rb_business_companies', array_values($companies));
    rb_record_user_legal_acceptance($user_id, rb_legal_acceptance_data('business_profile'));
    rb_business_redirect('saved');
}

function rb_business_unit_price(int $product_id, int $quantity): int
{
    $price = rb_parse_price((string) get_post_meta($product_id, 'rb_wholesale_price', true));
    if ($price <= 0) $price = rb_product_price_by_size($product_id, '1000');
    if ($quantity >= 25) return (int) round($price * .8);
    if ($quantity >= 10) return (int) round($price * .9);
    return $price;
}

function rb_handle_business_company_add(): void
{
    if (!rb_is_business_customer() || !isset($_POST['rb_business_company_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_business_company_nonce'])), 'rb_business_company_add')) {
        rb_business_redirect('security');
    }
    if (!rb_validate_legal_consents()) rb_business_redirect('consent');
    $company = rb_business_company_from_request($_POST);
    if ($company['name'] === '' || !rb_validate_inn($company['inn'])) rb_business_redirect('inn');
    $companies = rb_business_companies();
    $companies[] = $company;
    update_user_meta(get_current_user_id(), 'rb_business_companies', array_values($companies));
    rb_record_user_legal_acceptance(get_current_user_id(), rb_legal_acceptance_data('business_company'));
    rb_business_redirect('company_added');
}

function rb_handle_business_order(): void
{
    if (!rb_business_is_approved() || !isset($_POST['rb_business_order_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_business_order_nonce'])), 'rb_business_order')) {
        rb_business_redirect('approval');
    }
    if (!rb_validate_legal_consents(true)) rb_business_redirect('consent');
    $quantities = isset($_POST['quantity']) && is_array($_POST['quantity']) ? wp_unslash($_POST['quantity']) : [];
    $items = [];
    foreach ($quantities as $product_id => $quantity) {
        $product_id = absint($product_id);
        $quantity = max(0, absint($quantity));
        if (!$quantity || get_post_type($product_id) !== 'rb_product' || get_post_status($product_id) !== 'publish') continue;
        $unit_price = rb_business_unit_price($product_id, $quantity);
        if ($unit_price <= 0) continue;
        $items[] = [
            'product_id' => $product_id,
            'external_id' => sanitize_text_field((string) get_post_meta($product_id, 'rb_external_id', true)),
            'sku' => sanitize_text_field((string) (get_post_meta($product_id, 'rb_sku', true) ?: 'RB-' . $product_id)),
            'title' => sanitize_text_field(get_the_title($product_id)),
            'size' => '1000',
            'size_label' => '1 кг',
            'grind' => 'Согласовать',
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'line_total' => $unit_price * $quantity,
            'vat_code' => sanitize_text_field((string) (get_post_meta($product_id, 'rb_vat_code', true) ?: '1')),
        ];
    }
    if (!$items) rb_business_redirect('empty');

    $user_id = get_current_user_id();
    $companies = rb_business_companies($user_id);
    $company_id = sanitize_text_field(wp_unslash($_POST['company_id'] ?? ''));
    $selected_company = null;
    foreach ($companies as $company) {
        if (hash_equals((string) ($company['id'] ?? ''), $company_id)) {
            $selected_company = $company;
            break;
        }
    }
    if (!$selected_company) rb_business_redirect('company');
    $total = rb_order_items_subtotal($items);
    $order_id = wp_insert_post([
        'post_type' => 'rb_order',
        'post_status' => 'publish',
        'post_title' => 'Оптовая заявка от ' . current_time('d.m.Y H:i'),
        'post_author' => $user_id,
    ]);
    if (!$order_id || is_wp_error($order_id)) rb_business_redirect('error');

    $user = wp_get_current_user();
    $payment_method = isset($_POST['payment_method']) && $_POST['payment_method'] === 'yookassa' && rb_yookassa_is_configured() ? 'yookassa' : 'invoice';
    update_post_meta($order_id, 'rb_order_status', $payment_method === 'yookassa' ? 'awaiting_payment' : 'new');
    update_post_meta($order_id, 'rb_payment_status', $payment_method === 'yookassa' ? 'pending' : 'not_required');
    update_post_meta($order_id, 'rb_payment_method', $payment_method);
    update_post_meta($order_id, 'rb_order_type', 'business');
    update_post_meta($order_id, 'rb_order_items_data', $items);
    update_post_meta($order_id, 'rb_order_items', rb_order_items_text($items));
    update_post_meta($order_id, 'rb_order_subtotal_amount', $total);
    update_post_meta($order_id, 'rb_discount_total_amount', 0);
    update_post_meta($order_id, 'rb_delivery_cost_amount', 0);
    update_post_meta($order_id, 'rb_order_total_amount', $total);
    update_post_meta($order_id, 'rb_order_total', rb_format_price($total));
    update_post_meta($order_id, 'rb_customer_name', $user->display_name);
    update_post_meta($order_id, 'rb_customer_email', $user->user_email);
    update_post_meta($order_id, 'rb_customer_phone', (string) get_user_meta($user_id, 'rb_phone', true));
    update_post_meta($order_id, 'rb_company_name', (string) $selected_company['name']);
    update_post_meta($order_id, 'rb_company_inn', (string) $selected_company['inn']);
    update_post_meta($order_id, 'rb_company_kpp', (string) ($selected_company['kpp'] ?? ''));
    update_post_meta($order_id, 'rb_company_address', (string) ($selected_company['address'] ?? ''));
    update_post_meta($order_id, 'rb_delivery_method', 'Согласовать с менеджером');
    rb_record_order_legal_acceptance((int) $order_id, 'business_order');
    $reservation = rb_reserve_order_stock($order_id);
    if (is_wp_error($reservation)) {
        update_post_meta($order_id, 'rb_order_status', 'canceled');
        rb_business_redirect('stock');
    }
    if ($payment_method === 'yookassa') {
        $confirmation_url = rb_yookassa_create_payment($order_id);
        if (is_wp_error($confirmation_url)) {
            update_post_meta($order_id, 'rb_order_status', 'canceled');
            update_post_meta($order_id, 'rb_payment_status', 'canceled');
            update_post_meta($order_id, 'rb_payment_error', $confirmation_url->get_error_message());
            rb_release_order_stock($order_id);
            rb_business_redirect('payment');
        }
        rb_notify_managers_order($order_id, 'new');
        rb_notify_customer_order($order_id, 'new');
        wp_redirect(esc_url_raw($confirmation_url));
        exit;
    }
    rb_notify_managers_order($order_id, 'new');
    rb_notify_customer_order($order_id, 'new');
    rb_business_redirect('ordered');
}

function rb_handle_business_price_request(): void
{
    if (!isset($_POST['rb_business_price_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_business_price_nonce'])), 'rb_business_price_request')) {
        wp_safe_redirect(route_url('business') . '#price');
        exit;
    }
    if (!rb_validate_legal_consents()) {
        wp_safe_redirect(add_query_arg('price', 'consent', route_url('business')) . '#price');
        exit;
    }
    $name = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    if ($name === '' || !$email || !rb_is_valid_ru_phone($phone)) {
        wp_safe_redirect(add_query_arg('price', 'error', route_url('business')) . '#price');
        exit;
    }
    $order_id = wp_insert_post(['post_type' => 'rb_order', 'post_status' => 'publish', 'post_title' => 'Запрос прайса от ' . current_time('d.m.Y H:i'), 'post_author' => get_current_user_id()]);
    if (!$order_id || is_wp_error($order_id)) {
        wp_safe_redirect(add_query_arg('price', 'error', route_url('business')) . '#price');
        exit;
    }
    update_post_meta($order_id, 'rb_order_type', 'business_lead');
    update_post_meta($order_id, 'rb_order_status', 'new');
    update_post_meta($order_id, 'rb_payment_status', 'not_required');
    update_post_meta($order_id, 'rb_customer_name', $name);
    update_post_meta($order_id, 'rb_customer_email', $email);
    update_post_meta($order_id, 'rb_customer_phone', rb_format_phone($phone));
    update_post_meta($order_id, 'rb_order_total_amount', 0);
    update_post_meta($order_id, 'rb_order_total', 'Запрос прайса');
    update_post_meta($order_id, 'rb_delivery_method', 'Связаться с клиентом');
    rb_record_order_legal_acceptance((int) $order_id, 'business_price_request');
    rb_notify_managers_order($order_id, 'new');
    rb_notify_customer_order($order_id, 'new');
    wp_safe_redirect(add_query_arg('price', 'sent', route_url('business')) . '#price');
    exit;
}

add_action('show_user_profile', 'rb_render_business_user_fields');
add_action('edit_user_profile', 'rb_render_business_user_fields');
function rb_render_business_user_fields(WP_User $user): void
{
    if (!in_array('rb_business_customer', (array) $user->roles, true)) return;
    $status = (string) (get_user_meta($user->ID, 'rb_business_status', true) ?: 'pending');
    ?>
    <h2>Реквизиты юридического лица</h2>
    <table class="form-table" role="presentation">
        <tr><th><label for="rb-company-name">Компания</label></th><td><input class="regular-text" id="rb-company-name" name="rb_company_name" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_name', true)) ?>"></td></tr>
        <tr><th><label for="rb-company-inn">ИНН</label></th><td><input class="regular-text" id="rb-company-inn" name="rb_company_inn" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_inn', true)) ?>"></td></tr>
        <tr><th><label for="rb-company-kpp">КПП</label></th><td><input class="regular-text" id="rb-company-kpp" name="rb_company_kpp" value="<?= esc_attr((string) get_user_meta($user->ID, 'rb_company_kpp', true)) ?>"></td></tr>
        <tr><th><label for="rb-business-status">Доступ к оптовым ценам</label></th><td><select id="rb-business-status" name="rb_business_status"><option value="pending" <?= selected($status, 'pending', false) ?>>На проверке</option><option value="approved" <?= selected($status, 'approved', false) ?>>Одобрен</option><option value="rejected" <?= selected($status, 'rejected', false) ?>>Отклонен</option></select></td></tr>
    </table>
    <?php
}

add_action('personal_options_update', 'rb_save_business_user_fields');
add_action('edit_user_profile_update', 'rb_save_business_user_fields');
function rb_save_business_user_fields(int $user_id): void
{
    if (!current_user_can('edit_user', $user_id) || !isset($_POST['rb_business_status'])) return;
    $status = sanitize_key(wp_unslash($_POST['rb_business_status']));
    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) $status = 'pending';
    update_user_meta($user_id, 'rb_business_status', $status);
    foreach (['rb_company_name', 'rb_company_inn', 'rb_company_kpp'] as $key) {
        if (isset($_POST[$key])) update_user_meta($user_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
    }
}
