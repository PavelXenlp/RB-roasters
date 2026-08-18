<?php
/**
 * CDEK delivery integration.
 *
 * @package ROASTBERRY_THEME
 */

function rb_cdek_default_settings(): array
{
    return [
        'client_id' => '',
        'client_secret' => '',
        'sender_city' => 'Пермь',
        'sender_city_code' => '',
        'sender_address' => 'ул. Деревообделочная, 8к6',
        'package_length' => 30,
        'package_width' => 20,
        'package_height' => 12,
    ];
}

function rb_cdek_settings(): array
{
    return wp_parse_args((array) get_option('rb_cdek_settings', []), rb_cdek_default_settings());
}

function rb_cdek_is_configured(): bool
{
    $settings = rb_cdek_settings();

    return $settings['client_id'] !== ''
        && $settings['client_secret'] !== ''
        && absint($settings['sender_city_code']) > 0;
}

add_action('admin_init', 'rb_cdek_register_settings');
function rb_cdek_register_settings(): void
{
    register_setting('rb_cdek_settings_group', 'rb_cdek_settings', [
        'type' => 'array',
        'sanitize_callback' => 'rb_cdek_sanitize_settings',
        'default' => rb_cdek_default_settings(),
    ]);
}

function rb_cdek_sanitize_settings(array $input): array
{
    $current = rb_cdek_settings();
    $defaults = rb_cdek_default_settings();
    $secret = isset($input['client_secret']) ? trim((string) $input['client_secret']) : '';

    return [
        'client_id' => sanitize_text_field((string) ($input['client_id'] ?? '')),
        'client_secret' => $secret !== '' ? sanitize_text_field($secret) : (string) $current['client_secret'],
        'sender_city' => sanitize_text_field((string) ($input['sender_city'] ?? $defaults['sender_city'])),
        'sender_city_code' => (string) absint($input['sender_city_code'] ?? 0),
        'sender_address' => sanitize_text_field((string) ($input['sender_address'] ?? $defaults['sender_address'])),
        'package_length' => max(1, min(200, absint($input['package_length'] ?? $defaults['package_length']))),
        'package_width' => max(1, min(200, absint($input['package_width'] ?? $defaults['package_width']))),
        'package_height' => max(1, min(200, absint($input['package_height'] ?? $defaults['package_height']))),
    ];
}

function rb_render_cdek_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = rb_cdek_settings();
    ?>
    <div class="wrap">
        <h1>Доставка СДЭК</h1>
        <p>Ключи интеграции создаются в личном кабинете СДЭК. Города, пункты выдачи и стоимость доставки загружаются через сервер сайта.</p>
        <form method="post" action="options.php">
            <?php settings_fields('rb_cdek_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="rb-cdek-client-id">Идентификатор аккаунта</label></th>
                    <td><input class="regular-text" id="rb-cdek-client-id" name="rb_cdek_settings[client_id]" value="<?= esc_attr($settings['client_id']) ?>" autocomplete="off" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rb-cdek-secret">Секретный ключ</label></th>
                    <td>
                        <input class="regular-text" id="rb-cdek-secret" name="rb_cdek_settings[client_secret]" type="password" value="" autocomplete="new-password" placeholder="<?= $settings['client_secret'] !== '' ? 'Ключ сохранен' : '' ?>">
                        <p class="description">Оставьте поле пустым, чтобы сохранить текущий ключ.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rb-cdek-city">Город отправления</label></th>
                    <td><input class="regular-text" id="rb-cdek-city" name="rb_cdek_settings[sender_city]" value="<?= esc_attr($settings['sender_city']) ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rb-cdek-city-code">Код города СДЭК</label></th>
                    <td>
                        <input class="small-text" id="rb-cdek-city-code" name="rb_cdek_settings[sender_city_code]" type="number" min="1" value="<?= esc_attr($settings['sender_city_code']) ?>" required>
                        <p class="description">Числовой код населенного пункта из справочника СДЭК.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rb-cdek-address">Адрес отправления</label></th>
                    <td><input class="regular-text" id="rb-cdek-address" name="rb_cdek_settings[sender_address]" value="<?= esc_attr($settings['sender_address']) ?>" required></td>
                </tr>
                <tr>
                    <th scope="row">Габариты упаковки по умолчанию</th>
                    <td>
                        <label>Длина, см <input class="small-text" name="rb_cdek_settings[package_length]" type="number" min="1" max="200" value="<?= esc_attr($settings['package_length']) ?>"></label>
                        <label>Ширина, см <input class="small-text" name="rb_cdek_settings[package_width]" type="number" min="1" max="200" value="<?= esc_attr($settings['package_width']) ?>"></label>
                        <label>Высота, см <input class="small-text" name="rb_cdek_settings[package_height]" type="number" min="1" max="200" value="<?= esc_attr($settings['package_height']) ?>"></label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function rb_cdek_sender_location(): array
{
    $settings = rb_cdek_settings();

    return [
        'code' => absint($settings['sender_city_code']),
        'city' => (string) $settings['sender_city'],
        'address' => (string) $settings['sender_address'],
        'country_code' => 'RU',
    ];
}

function rb_cdek_cart_packages(): array
{
    $settings = rb_cdek_settings();
    $length = max(1, absint($settings['package_length']));
    $width = max(1, absint($settings['package_width']));
    $volume = 0;
    $weight = 0;

    foreach (rb_get_cart() as $item) {
        $product_id = absint($item['product_id'] ?? 0);
        $quantity = max(1, absint($item['quantity'] ?? 1));
        $item_length = max(1, absint(get_post_meta($product_id, 'rb_cdek_length', true)) ?: $length);
        $item_width = max(1, absint(get_post_meta($product_id, 'rb_cdek_width', true)) ?: $width);
        $item_height = max(1, absint(get_post_meta($product_id, 'rb_cdek_height', true)) ?: absint($settings['package_height']));
        $item_weight = absint(get_post_meta($product_id, 'rb_cdek_weight', true));

        if ($item_weight < 1) {
            $item_weight = strpos((string) ($item['size'] ?? ''), '1 кг') !== false ? 1080 : 240;
        }

        $length = max($length, $item_length);
        $width = max($width, $item_width);
        $volume += $item_length * $item_width * $item_height * $quantity;
        $weight += $item_weight * $quantity;
    }

    $height = max(1, (int) ceil($volume / max(1, $length * $width)));

    return [[
        'weight' => max(1, $weight),
        'length' => min(200, $length),
        'width' => min(200, $width),
        'height' => min(200, $height),
    ]];
}

function rb_cdek_access_token()
{
    $settings = rb_cdek_settings();
    if ($settings['client_id'] === '' || $settings['client_secret'] === '') {
        return new WP_Error('rb_cdek_not_configured', 'Не указаны ключи интеграции СДЭК.');
    }

    $cache_key = 'rb_cdek_token_' . md5($settings['client_id']);
    $cached = get_transient($cache_key);
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }

    $response = wp_remote_post('https://api.cdek.ru/v2/oauth/token', [
        'timeout' => 20,
        'body' => [
            'grant_type' => 'client_credentials',
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
        ],
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($status !== 200 || empty($body['access_token'])) {
        return new WP_Error('rb_cdek_auth_failed', 'СДЭК отклонил ключи интеграции.');
    }

    $ttl = max(60, absint($body['expires_in'] ?? 3600) - 120);
    set_transient($cache_key, (string) $body['access_token'], $ttl);

    return (string) $body['access_token'];
}

function rb_cdek_api_request(string $method, string $path, array $data = [])
{
    $token = rb_cdek_access_token();
    if (is_wp_error($token)) {
        return $token;
    }

    $url = 'https://api.cdek.ru/v2/' . ltrim($path, '/');
    $args = [
        'method' => strtoupper($method),
        'timeout' => 25,
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'X-App-Name' => 'roastberry-theme',
            'X-App-Version' => wp_get_theme()->get('Version'),
        ],
    ];

    if ($args['method'] === 'GET') {
        $url = add_query_arg($data, $url);
    } else {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body'] = wp_json_encode($data);
    }

    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($body) && !empty($body['message']) ? (string) $body['message'] : 'Сервис СДЭК временно недоступен.';
        return new WP_Error('rb_cdek_api_error', $message, ['status' => $status, 'response' => $body]);
    }

    return is_array($body) ? $body : [];
}

function rb_cdek_extract_tariffs(array $response): array
{
    foreach (['tariff_codes', 'tariffs', 'data'] as $key) {
        if (isset($response[$key]) && is_array($response[$key])) {
            $response = $response[$key];
            break;
        }
    }

    return array_values(array_filter($response, static function ($tariff): bool {
        return is_array($tariff) && absint($tariff['tariff_code'] ?? 0) > 0;
    }));
}

function rb_cdek_is_pickup_tariff(array $tariff): bool
{
    if (!isset($tariff['delivery_sum']) || !is_numeric($tariff['delivery_sum'])) {
        return false;
    }

    $delivery_mode = absint($tariff['delivery_mode'] ?? 0);
    if ($delivery_mode) {
        return in_array($delivery_mode, [2, 4], true);
    }

    $tariff_code = absint($tariff['tariff_code'] ?? 0);
    if (in_array($tariff_code, [136, 138, 234, 236], true)) {
        return true;
    }

    return preg_match('/посылка.+(?:дверь|склад)-склад/iu', (string) ($tariff['tariff_name'] ?? '')) === 1;
}

function rb_cdek_select_pickup_tariff(array $tariffs)
{
    $tariffs = array_values(array_filter($tariffs, 'rb_cdek_is_pickup_tariff'));
    if (!$tariffs) {
        return new WP_Error('rb_cdek_tariff_not_found', 'СДЭК не вернул тариф доставки до выбранного пункта выдачи.', ['status' => 422]);
    }

    usort($tariffs, static function (array $first, array $second): int {
        return (float) $first['delivery_sum'] <=> (float) $second['delivery_sum'];
    });

    foreach ([136, 234, 138, 236] as $preferred_code) {
        foreach ($tariffs as $tariff) {
            if (absint($tariff['tariff_code'] ?? 0) === $preferred_code) {
                return $tariff;
            }
        }
    }

    foreach ($tariffs as $tariff) {
        if (preg_match('/посылка/iu', (string) ($tariff['tariff_name'] ?? '')) === 1) {
            return $tariff;
        }
    }

    return $tariffs[0];
}

add_action('rest_api_init', 'rb_cdek_register_rest_route');
function rb_cdek_register_rest_route(): void
{
    register_rest_route('rb/v1', '/cdek', [
        'methods' => ['GET', 'POST'],
        'callback' => 'rb_cdek_rest_service',
        'permission_callback' => '__return_true',
    ]);
}

function rb_cdek_rest_service(WP_REST_Request $request)
{
    if (!rb_cdek_is_configured()) {
        return new WP_Error('rb_cdek_not_configured', 'Доставка СДЭК еще не настроена.', ['status' => 503]);
    }

    $action = sanitize_key((string) $request->get_param('action'));
    if ($action === 'cities') {
        $city = sanitize_text_field((string) $request->get_param('city'));
        $city_length = function_exists('mb_strlen') ? mb_strlen($city) : strlen($city);
        if ($city_length < 2) {
            return new WP_Error('rb_cdek_city_query_short', 'Введите минимум два символа названия города.', ['status' => 400]);
        }

        $result = rb_cdek_api_request('GET', 'location/cities', [
            'city' => $city,
            'country_codes' => 'RU',
            'size' => 20,
        ]);
    } elseif ($action === 'offices') {
        $allowed = ['country_code', 'region_code', 'city_code', 'postal_code', 'type', 'is_handout', 'have_cashless', 'have_cash', 'is_dressing_room', 'allowed_cod', 'page', 'size'];
        $params = [];
        foreach ($allowed as $key) {
            $value = $request->get_param($key);
            if (is_scalar($value) && $value !== '') {
                $params[$key] = sanitize_text_field((string) $value);
            }
        }
        if (isset($params['page'])) {
            $params['page'] = max(0, absint($params['page']));
        }
        if (isset($params['size'])) {
            $params['size'] = max(1, min(500, absint($params['size'])));
        }
        $result = rb_cdek_api_request('GET', 'deliverypoints', $params);
    } elseif ($action === 'calculate') {
        $payload = (array) $request->get_json_params();
        $to_location = isset($payload['to_location']) && is_array($payload['to_location']) ? $payload['to_location'] : [];
        $city_code = absint($to_location['code'] ?? 0);
        if (!$city_code) {
            return new WP_Error('rb_cdek_city_required', 'Выберите город доставки.', ['status' => 400]);
        }

        $result = rb_cdek_api_request('POST', 'calculator/tarifflist', [
            'type' => 1,
            'currency' => 1,
            'lang' => 'rus',
            'from_location' => rb_cdek_sender_location(),
            'to_location' => ['code' => $city_code],
            'packages' => rb_cdek_cart_packages(),
        ]);
    } else {
        return new WP_Error('rb_cdek_action_invalid', 'Неизвестная операция СДЭК.', ['status' => 400]);
    }

    if (is_wp_error($result)) {
        $data = $result->get_error_data();
        $status = is_array($data) ? absint($data['status'] ?? 502) : 502;
        return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => $status ?: 502]);
    }

    if ($action === 'calculate') {
        $result = rb_cdek_select_pickup_tariff(rb_cdek_extract_tariffs($result));
        if (is_wp_error($result)) {
            return $result;
        }
    }

    return rest_ensure_response($result);
}

function rb_cdek_validate_order(array $input)
{
    if (!rb_cdek_is_configured()) {
        return new WP_Error('rb_cdek_not_configured', 'Доставка СДЭК еще не настроена.');
    }

    $office_code = sanitize_text_field((string) ($input['rb_cdek_office_code'] ?? ''));
    $city_code = absint($input['rb_cdek_city_code'] ?? 0);
    $tariff_code = absint($input['rb_cdek_tariff_code'] ?? 0);
    if ($office_code === '' || !$city_code || !$tariff_code) {
        return new WP_Error('rb_cdek_selection_required', 'Выберите пункт выдачи СДЭК.');
    }

    $offices = rb_cdek_api_request('GET', 'deliverypoints', ['code' => $office_code]);
    if (is_wp_error($offices) || empty($offices[0]) || absint($offices[0]['location']['city_code'] ?? 0) !== $city_code) {
        return new WP_Error('rb_cdek_office_invalid', 'Не удалось подтвердить выбранный пункт СДЭК. Выберите его еще раз.');
    }

    $calculations = rb_cdek_api_request('POST', 'calculator/tarifflist', [
        'type' => 1,
        'currency' => 1,
        'lang' => 'rus',
        'from_location' => rb_cdek_sender_location(),
        'to_location' => ['code' => $city_code],
        'packages' => rb_cdek_cart_packages(),
    ]);

    if (is_wp_error($calculations)) {
        return new WP_Error('rb_cdek_calculation_failed', 'Не удалось пересчитать стоимость СДЭК. Попробуйте выбрать пункт еще раз.');
    }

    $calculation = null;
    foreach (rb_cdek_extract_tariffs($calculations) as $tariff) {
        if (absint($tariff['tariff_code'] ?? 0) === $tariff_code && rb_cdek_is_pickup_tariff($tariff)) {
            $calculation = $tariff;
            break;
        }
    }

    if (!$calculation) {
        return new WP_Error('rb_cdek_tariff_invalid', 'Выбранный тариф не предназначен для доставки до пункта выдачи.');
    }

    $office = $offices[0];

    return [
        'office_code' => $office_code,
        'office_address' => sanitize_text_field((string) ($office['location']['address_full'] ?? $office['location']['address'] ?? '')),
        'city' => sanitize_text_field((string) ($office['location']['city'] ?? '')),
        'city_code' => $city_code,
        'tariff_code' => $tariff_code,
        'tariff_name' => sanitize_text_field((string) ($calculation['tariff_name'] ?? $input['rb_cdek_tariff_name'] ?? 'СДЭК до ПВЗ')),
        'cost' => max(0, (int) round((float) $calculation['delivery_sum'])),
        'period_min' => absint($calculation['period_min'] ?? 0),
        'period_max' => absint($calculation['period_max'] ?? 0),
    ];
}
