<?php
/**
 * Passwordless authentication by email verification code.
 *
 * @package ROASTBERRY_THEME
 */

function rb_email_layout(string $title, string $content, string $button_label = '', string $button_url = ''): string
{
    $button = '';
    if ($button_label !== '' && $button_url !== '') {
        $button = '<p style="margin:28px 0 0"><a href="' . esc_url($button_url) . '" style="display:inline-block;padding:13px 20px;color:#fff;background:#8aa319;border-radius:6px;text-decoration:none;font-weight:700">' . esc_html($button_label) . '</a></p>';
    }

    return '<!doctype html><html><body style="margin:0;padding:24px;background:#f5f6f0;font-family:Arial,sans-serif;color:#20201e">'
        . '<div style="max-width:600px;margin:0 auto;padding:32px;background:#fff;border:1px solid #e8e8e2;border-radius:8px">'
        . '<p style="margin:0 0 22px;color:#8aa319;font-size:13px;font-weight:700;text-transform:uppercase">Roastberry Coffee Roasters</p>'
        . '<h1 style="margin:0 0 18px;font-size:28px;line-height:1.15">' . esc_html($title) . '</h1>'
        . '<div style="font-size:16px;line-height:1.6">' . wp_kses_post($content) . '</div>'
        . $button
        . '</div></body></html>';
}

function rb_html_mail_headers(): array
{
    return ['Content-Type: text/html; charset=UTF-8'];
}

function rb_auth_pending(): array
{
    return isset($_SESSION['rb_auth_pending']) && is_array($_SESSION['rb_auth_pending'])
        ? $_SESSION['rb_auth_pending']
        : [];
}

function rb_auth_pending_type(): string
{
    return sanitize_key((string) (rb_auth_pending()['type'] ?? ''));
}

function rb_auth_send_code(string $email, string $type, array $payload = [])
{
    $email = sanitize_email($email);
    if ($email === '' || !is_email($email)) {
        return new WP_Error('rb_auth_email', 'Введите корректный адрес электронной почты.');
    }

    $rate_key = 'rb_auth_rate_' . md5(strtolower($email));
    if (get_transient($rate_key)) {
        return new WP_Error('rb_auth_rate', 'Код уже отправлен. Подождите минуту перед повторной отправкой.');
    }

    $code = (string) random_int(100000, 999999);
    $_SESSION['rb_auth_pending'] = [
        'type' => sanitize_key($type),
        'email' => $email,
        'code_hash' => wp_hash_password($code),
        'expires' => time() + 10 * MINUTE_IN_SECONDS,
        'attempts' => 0,
        'payload' => $payload,
    ];

    $content = '<p>Ваш одноразовый код:</p>'
        . '<p style="margin:18px 0;font-size:34px;font-weight:800;letter-spacing:0">' . esc_html($code) . '</p>'
        . '<p>Код действует 10 минут. Никому его не сообщайте.</p>';
    $sent = wp_mail($email, 'Код входа Roastberry Coffee Roasters', rb_email_layout('Подтверждение почты', $content), rb_html_mail_headers());

    if (!$sent) {
        unset($_SESSION['rb_auth_pending']);
        return new WP_Error('rb_auth_mail', 'Не удалось отправить код. Проверьте настройки почты и попробуйте снова.');
    }

    set_transient($rate_key, '1', MINUTE_IN_SECONDS);
    return true;
}

function rb_auth_redirect_for_type(string $type, string $status): void
{
    if ($type === 'business_register') {
        wp_safe_redirect(add_query_arg('business_result', $status, route_url('business-account')) . '#b-code');
    } else {
        wp_safe_redirect(add_query_arg('auth', $status, route_url('account')) . '#auth-code');
    }
    exit;
}

function rb_handle_auth_code_verification(): void
{
    if (!isset($_POST['rb_auth_code_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_auth_code_nonce'])), 'rb_verify_auth_code')) {
        return;
    }

    $pending = rb_auth_pending();
    $type = sanitize_key((string) ($pending['type'] ?? ''));
    if (!$pending || !$type || (int) ($pending['expires'] ?? 0) < time()) {
        unset($_SESSION['rb_auth_pending']);
        rb_auth_redirect_for_type($type, 'code_expired');
    }

    $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
    $_SESSION['rb_auth_pending'] = $pending;
    $code = preg_replace('/\D+/', '', (string) wp_unslash($_POST['auth_code'] ?? ''));
    if ($pending['attempts'] > 5 || strlen($code) !== 6 || !wp_check_password($code, (string) $pending['code_hash'])) {
        if ($pending['attempts'] > 5) unset($_SESSION['rb_auth_pending']);
        rb_auth_redirect_for_type($type, $pending['attempts'] > 5 ? 'code_expired' : 'code_invalid');
    }

    $payload = isset($pending['payload']) && is_array($pending['payload']) ? $pending['payload'] : [];
    $user_id = 0;
    if ($type === 'retail_login') {
        $user = get_user_by('email', (string) $pending['email']);
        $user_id = $user instanceof WP_User ? $user->ID : 0;
    } elseif ($type === 'retail_register' && function_exists('rb_complete_retail_registration')) {
        $user_id = rb_complete_retail_registration($payload);
    } elseif ($type === 'business_register' && function_exists('rb_complete_business_registration')) {
        $user_id = rb_complete_business_registration($payload);
    }

    if (!$user_id || is_wp_error($user_id)) {
        unset($_SESSION['rb_auth_pending']);
        rb_auth_redirect_for_type($type, 'email');
    }

    if (!empty($payload['legal_acceptance']) && is_array($payload['legal_acceptance'])) {
        rb_record_user_legal_acceptance((int) $user_id, $payload['legal_acceptance']);
    }

    unset($_SESSION['rb_auth_pending']);
    wp_set_current_user((int) $user_id);
    wp_set_auth_cookie((int) $user_id, true, is_ssl());
    $current_cart = rb_get_cart();
    $saved_cart = get_user_meta((int) $user_id, 'rb_saved_cart', true);
    if (!$current_cart && is_array($saved_cart)) {
        $current_cart = $saved_cart;
    }
    rb_save_cart($current_cart);

    $user = get_userdata((int) $user_id);
    $is_business = $user instanceof WP_User && in_array('rb_business_customer', (array) $user->roles, true);
    if ($is_business) {
        $redirect_url = $type === 'business_register'
            ? add_query_arg('business_result', 'registered', route_url('business-account'))
            : route_url('business-account');
    } else {
        $redirect_url = route_url('account');
    }
    wp_safe_redirect($redirect_url);
    exit;
}
