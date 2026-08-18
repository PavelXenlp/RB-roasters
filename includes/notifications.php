<?php
/**
 * Manager notifications for orders and company applications.
 *
 * @package ROASTBERRY_THEME
 */

function rb_notification_default_settings(): array
{
    return [
        'emails' => (string) get_option('admin_email'),
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',
        'abandoned_cart_enabled' => '1',
        'abandoned_cart_hours' => '24',
    ];
}

function rb_notification_settings(): array
{
    return wp_parse_args((array) get_option('rb_notification_settings', []), rb_notification_default_settings());
}

add_action('admin_init', 'rb_notification_register_settings');
function rb_notification_register_settings(): void
{
    register_setting('rb_notification_settings_group', 'rb_notification_settings', [
        'type' => 'array',
        'sanitize_callback' => 'rb_notification_sanitize_settings',
        'default' => rb_notification_default_settings(),
    ]);
}

function rb_notification_sanitize_settings(array $input): array
{
    $current = rb_notification_settings();
    $emails = array_filter(array_map('sanitize_email', preg_split('/[,;\s]+/', (string) ($input['emails'] ?? ''))));
    $token = trim((string) ($input['telegram_bot_token'] ?? ''));
    return [
        'emails' => implode(', ', $emails),
        'telegram_bot_token' => $token === '' ? $current['telegram_bot_token'] : sanitize_text_field($token),
        'telegram_chat_id' => sanitize_text_field((string) ($input['telegram_chat_id'] ?? '')),
        'abandoned_cart_enabled' => !empty($input['abandoned_cart_enabled']) ? '1' : '0',
        'abandoned_cart_hours' => (string) min(168, max(1, absint($input['abandoned_cart_hours'] ?? 24))),
    ];
}

add_action('admin_menu', 'rb_notification_admin_menu', 22);
function rb_notification_admin_menu(): void
{
    add_submenu_page('rb-roasters', 'Уведомления', 'Уведомления', 'manage_options', 'rb-notifications', 'rb_notification_render_settings');
}

function rb_notification_render_settings(): void
{
    if (!current_user_can('manage_options')) return;
    $settings = rb_notification_settings();
    ?>
    <div class="wrap">
        <h1>Уведомления менеджеров</h1>
        <form method="post" action="options.php">
            <?php settings_fields('rb_notification_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr><th><label for="rb-notification-emails">Почта для заявок</label></th><td><input class="regular-text" type="email" multiple id="rb-notification-emails" name="rb_notification_settings[emails]" value="<?= esc_attr($settings['emails']) ?>"><p class="description">Сюда приходят новые заказы, B2B-заявки и запросы прайс-листа. Несколько адресов можно разделить запятой.</p></td></tr>
                <tr><th>Незавершённая корзина</th><td><label><input type="checkbox" name="rb_notification_settings[abandoned_cart_enabled]" value="1" <?= checked($settings['abandoned_cart_enabled'], '1', false) ?>> Отправлять покупателю напоминание</label><p><label>Через <input class="small-text" type="number" min="1" max="168" name="rb_notification_settings[abandoned_cart_hours]" value="<?= esc_attr($settings['abandoned_cart_hours']) ?>"> ч.</label></p><p class="description">Работает для авторизованных покупателей с непустой корзиной.</p></td></tr>
                <tr><th><label for="rb-telegram-token">Токен Telegram Bot</label></th><td><input class="regular-text" type="password" id="rb-telegram-token" name="rb_notification_settings[telegram_bot_token]" value="" placeholder="<?= $settings['telegram_bot_token'] !== '' ? esc_attr('Токен сохранен, оставьте поле пустым') : '' ?>" autocomplete="new-password"></td></tr>
                <tr><th><label for="rb-telegram-chat">Telegram chat ID</label></th><td><input class="regular-text" id="rb-telegram-chat" name="rb_notification_settings[telegram_chat_id]" value="<?= esc_attr($settings['telegram_chat_id']) ?>"></td></tr>
            </table>
            <?php submit_button('Сохранить настройки'); ?>
        </form>
    </div>
    <?php
}

function rb_order_notification_text(int $order_id, string $event): string
{
    $order_type = (string) get_post_meta($order_id, 'rb_order_type', true);
    $type = $order_type === 'business' ? 'Оптовая заявка' : ($order_type === 'business_lead' ? 'Запрос прайса' : 'Розничный заказ');
    $event_label = $event === 'paid' ? 'Оплата получена' : 'Новый заказ';
    $lines = [
        $event_label . ': ' . $type . ' №' . $order_id,
        'Покупатель: ' . (string) get_post_meta($order_id, 'rb_customer_name', true),
        'Телефон: ' . (string) get_post_meta($order_id, 'rb_customer_phone', true),
        'Сумма: ' . (string) get_post_meta($order_id, 'rb_order_total', true),
        'Доставка: ' . (string) get_post_meta($order_id, 'rb_delivery_method', true),
        '',
        rb_order_items_text(rb_get_order_items($order_id)),
        '',
        'Открыть заказ: ' . admin_url('post.php?post=' . $order_id . '&action=edit'),
    ];
    return implode("\n", $lines);
}

function rb_notify_managers_order(int $order_id, string $event = 'new'): void
{
    $meta_key = 'rb_notification_' . sanitize_key($event) . '_sent';
    if (get_post_meta($order_id, $meta_key, true) === '1') return;
    $settings = rb_notification_settings();
    $text = rb_order_notification_text($order_id, $event);
    $order_type = (string) get_post_meta($order_id, 'rb_order_type', true);
    $subject = $order_type === 'business_lead'
        ? 'Новый запрос прайс-листа №' . $order_id
        : (($event === 'paid' ? 'Оплачен заказ' : 'Новый заказ') . ' №' . $order_id);

    if ($settings['emails'] !== '') {
        wp_mail(array_map('trim', explode(',', $settings['emails'])), $subject, $text);
    }
    if ($settings['telegram_bot_token'] !== '' && $settings['telegram_chat_id'] !== '') {
        $telegram_text = implode("\n", [
            $event === 'paid' ? 'Оплата получена по заказу №' . $order_id : 'Новый заказ №' . $order_id,
            'Тип: ' . $type,
            'Сумма: ' . (string) get_post_meta($order_id, 'rb_order_total', true),
            'Персональные данные доступны только в защищенной админ-панели:',
            admin_url('post.php?post=' . $order_id . '&action=edit'),
        ]);
        wp_remote_post('https://api.telegram.org/bot' . rawurlencode($settings['telegram_bot_token']) . '/sendMessage', [
            'timeout' => 15,
            'body' => ['chat_id' => $settings['telegram_chat_id'], 'text' => $telegram_text, 'disable_web_page_preview' => 'true'],
        ]);
    }
    update_post_meta($order_id, $meta_key, '1');
}

function rb_notify_customer_order(int $order_id, string $event = 'new'): void
{
    $email = sanitize_email((string) get_post_meta($order_id, 'rb_customer_email', true));
    if ($email === '') return;
    $meta_key = 'rb_customer_notification_' . sanitize_key($event) . '_sent';
    if (get_post_meta($order_id, $meta_key, true) === '1') return;
    $is_price_request = get_post_meta($order_id, 'rb_order_type', true) === 'business_lead';
    $subject = $is_price_request ? 'Запрос прайса Roastberry Coffee Roasters' : ($event === 'paid' ? 'Оплата заказа №' . $order_id : 'Заказ №' . $order_id . ' принят');
    if ($is_price_request) {
        $message = rb_email_layout('Запрос прайс-листа принят', '<p>Мы получили ваш запрос. Менеджер свяжется с вами по указанным контактам.</p>');
    } elseif ($event === 'paid') {
        $message = rb_email_layout('Оплата получена', '<p>Оплата заказа №' . esc_html((string) $order_id) . ' получена. Заказ передан в обработку.</p>', 'Открыть личный кабинет', route_url('account'));
    } else {
        $items = '';
        foreach (rb_get_order_items($order_id) as $item) {
            $items .= '<tr><td style="padding:9px 0;border-bottom:1px solid #e8e8e2">' . esc_html((string) ($item['title'] ?? 'Товар')) . '<br><small>' . esc_html(trim((string) ($item['size_label'] ?? '') . ', ' . (string) ($item['grind'] ?? ''), ', ')) . '</small></td><td style="padding:9px 0;border-bottom:1px solid #e8e8e2;text-align:center">' . esc_html((string) ($item['quantity'] ?? 1)) . '</td><td style="padding:9px 0;border-bottom:1px solid #e8e8e2;text-align:right">' . esc_html(rb_format_price((int) ($item['line_total'] ?? 0))) . '</td></tr>';
        }
        $content = '<p>Спасибо! Заказ №' . esc_html((string) $order_id) . ' оформлен и передан менеджеру.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:20px 0"><tbody>' . $items . '</tbody></table>'
            . '<p><strong>Доставка:</strong> ' . esc_html((string) get_post_meta($order_id, 'rb_delivery_method', true)) . '</p>'
            . '<p style="font-size:20px"><strong>Итого: ' . esc_html((string) get_post_meta($order_id, 'rb_order_total', true)) . '</strong></p>';
        $message = rb_email_layout('Заказ оформлен', $content, 'Открыть личный кабинет', route_url('account'));
    }
    if (wp_mail($email, $subject, $message, rb_html_mail_headers())) update_post_meta($order_id, $meta_key, '1');
}

function rb_schedule_abandoned_cart_reminder(int $user_id, array $cart): void
{
    if (!$user_id) return;
    wp_clear_scheduled_hook('rb_abandoned_cart_reminder_event', [$user_id]);

    if (!$cart) {
        delete_user_meta($user_id, 'rb_saved_cart');
        delete_user_meta($user_id, 'rb_abandoned_cart_hash');
        delete_user_meta($user_id, 'rb_abandoned_cart_sent_hash');
        return;
    }

    update_user_meta($user_id, 'rb_saved_cart', $cart);
    $hash = md5(wp_json_encode(array_map(static function (array $item): array {
        return [(int) ($item['product_id'] ?? 0), (int) ($item['quantity'] ?? 0), (string) ($item['size_code'] ?? '')];
    }, $cart)));
    update_user_meta($user_id, 'rb_abandoned_cart_hash', $hash);
    delete_user_meta($user_id, 'rb_abandoned_cart_sent_hash');

    $settings = rb_notification_settings();
    if ($settings['abandoned_cart_enabled'] !== '1') return;
    $delay = min(168, max(1, absint($settings['abandoned_cart_hours']))) * HOUR_IN_SECONDS;
    wp_schedule_single_event(time() + $delay, 'rb_abandoned_cart_reminder_event', [$user_id]);
}

add_action('rb_abandoned_cart_reminder_event', 'rb_send_abandoned_cart_reminder');
function rb_send_abandoned_cart_reminder(int $user_id): void
{
    $settings = rb_notification_settings();
    if ($settings['abandoned_cart_enabled'] !== '1') return;
    $user = get_userdata($user_id);
    $cart = get_user_meta($user_id, 'rb_saved_cart', true);
    $hash = (string) get_user_meta($user_id, 'rb_abandoned_cart_hash', true);
    if (!$user instanceof WP_User || !is_array($cart) || !$cart || $hash === '' || get_user_meta($user_id, 'rb_abandoned_cart_sent_hash', true) === $hash) return;

    $items = '';
    $total = 0;
    foreach ($cart as $item) {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $line_total = max(0, (int) ($item['price'] ?? 0)) * $quantity;
        $total += $line_total;
        $items .= '<li style="margin:8px 0">' . esc_html((string) ($item['title'] ?? 'Товар')) . ' — ' . esc_html((string) $quantity) . ' шт., ' . esc_html(rb_format_price($line_total)) . '</li>';
    }
    $content = '<p>В вашей корзине остались товары:</p><ul style="padding-left:20px">' . $items . '</ul><p style="font-size:20px"><strong>Сумма: ' . esc_html(rb_format_price($total)) . '</strong></p>';
    $message = rb_email_layout('Вы оставили товары в корзине', $content, 'Вернуться в корзину', route_url('cart'));
    if (wp_mail($user->user_email, 'Товары ждут вас в корзине', $message, rb_html_mail_headers())) {
        update_user_meta($user_id, 'rb_abandoned_cart_sent_hash', $hash);
    }
}

function rb_notify_business_registration(int $user_id): void
{
    if (get_user_meta($user_id, 'rb_registration_notification_sent', true) === '1') return;
    $settings = rb_notification_settings();
    $user = get_userdata($user_id);
    if (!$user) return;
    $text = implode("\n", [
        'Новая заявка юридического лица',
        'Компания: ' . (string) get_user_meta($user_id, 'rb_company_name', true),
        'ИНН: ' . (string) get_user_meta($user_id, 'rb_company_inn', true),
        'Контакт: ' . $user->display_name,
        'Телефон: ' . (string) get_user_meta($user_id, 'rb_phone', true),
        'Проверить: ' . admin_url('user-edit.php?user_id=' . $user_id),
    ]);
    if ($settings['emails'] !== '') wp_mail(array_map('trim', explode(',', $settings['emails'])), 'Новая B2B-заявка', $text);
    if ($settings['telegram_bot_token'] !== '' && $settings['telegram_chat_id'] !== '') {
        $telegram_text = "Новая B2B-заявка №{$user_id}\nПерсональные данные доступны только в защищенной админ-панели:\n" . admin_url('user-edit.php?user_id=' . $user_id);
        wp_remote_post('https://api.telegram.org/bot' . rawurlencode($settings['telegram_bot_token']) . '/sendMessage', ['timeout' => 15, 'body' => ['chat_id' => $settings['telegram_chat_id'], 'text' => $telegram_text, 'disable_web_page_preview' => 'true']]);
    }
    update_user_meta($user_id, 'rb_registration_notification_sent', '1');
}
