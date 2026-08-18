<?php
/**
 * Incoming CommerceML catalog import from 1C.
 *
 * @package ROASTBERRY_THEME
 */

function rb_1c_default_settings(): array
{
    return [
        'token' => '',
        'retail_price_type' => '',
        'wholesale_price_type' => '',
        'publish_new_products' => '1',
    ];
}

function rb_1c_settings(): array
{
    return wp_parse_args((array) get_option('rb_1c_settings', []), rb_1c_default_settings());
}

add_action('admin_init', 'rb_1c_register_settings');
function rb_1c_register_settings(): void
{
    register_setting('rb_1c_settings_group', 'rb_1c_settings', [
        'type' => 'array',
        'sanitize_callback' => 'rb_1c_sanitize_settings',
        'default' => rb_1c_default_settings(),
    ]);

    $settings = rb_1c_settings();
    if ($settings['token'] === '') {
        $settings['token'] = wp_generate_password(40, false, false);
        update_option('rb_1c_settings', $settings, false);
    }
}

function rb_1c_sanitize_settings(array $input): array
{
    $current = rb_1c_settings();
    $token = sanitize_text_field((string) ($input['token'] ?? ''));

    return [
        'token' => $token !== '' ? $token : $current['token'],
        'retail_price_type' => sanitize_text_field((string) ($input['retail_price_type'] ?? '')),
        'wholesale_price_type' => sanitize_text_field((string) ($input['wholesale_price_type'] ?? '')),
        'publish_new_products' => empty($input['publish_new_products']) ? '0' : '1',
    ];
}

add_action('admin_menu', 'rb_1c_admin_menu', 20);
function rb_1c_admin_menu(): void
{
    add_submenu_page('rb-roasters', 'Интеграция с 1С', 'Интеграция с 1С', 'manage_options', 'rb-1c', 'rb_1c_render_settings_page');
}

function rb_1c_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = rb_1c_settings();
    $logs = array_slice((array) get_option('rb_1c_import_logs', []), 0, 10);
    $price_types = (array) get_option('rb_1c_last_price_types', []);
    $result = isset($_GET['rb_1c_result']) ? sanitize_key(wp_unslash($_GET['rb_1c_result'])) : '';
    ?>
    <div class="wrap">
        <h1>Интеграция с 1С</h1>
        <?php if ($result === 'success'): ?><div class="notice notice-success"><p>Импорт CommerceML завершен.</p></div><?php endif; ?>
        <?php if ($result === 'error'): ?><div class="notice notice-error"><p>Импорт завершился с ошибкой. Подробности находятся в журнале.</p></div><?php endif; ?>

        <h2>Подключение</h2>
        <form method="post" action="options.php">
            <?php settings_fields('rb_1c_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr><th><label for="rb-1c-token">Токен обмена</label></th><td><input class="regular-text code" id="rb-1c-token" name="rb_1c_settings[token]" value="<?= esc_attr($settings['token']) ?>" autocomplete="off"><p class="description">Передавайте его в заголовке <code>X-RB-1C-Token</code>.</p></td></tr>
                <tr><th><label for="rb-1c-retail-price">ID розничного типа цен</label></th><td><input class="regular-text" id="rb-1c-retail-price" name="rb_1c_settings[retail_price_type]" value="<?= esc_attr($settings['retail_price_type']) ?>"><p class="description">Если поле пустое, будет использована первая цена предложения.</p></td></tr>
                <tr><th><label for="rb-1c-wholesale-price">ID оптового типа цен</label></th><td><input class="regular-text" id="rb-1c-wholesale-price" name="rb_1c_settings[wholesale_price_type]" value="<?= esc_attr($settings['wholesale_price_type']) ?>"></td></tr>
                <tr><th>Новые товары</th><td><label><input type="checkbox" name="rb_1c_settings[publish_new_products]" value="1" <?= checked($settings['publish_new_products'], '1', false) ?>> Сразу публиковать в каталоге</label></td></tr>
            </table>
            <?php submit_button('Сохранить настройки'); ?>
        </form>

        <p><strong>URL загрузки:</strong> <code><?= esc_html(rest_url('rb/v1/1c/import')) ?></code></p>
        <p class="description">Принимает multipart-поле <code>file</code> с XML или ZIP. Параметр <code>full=1</code> обнулит остатки импортированных ранее товаров, отсутствующих в полном файле.</p>

        <?php if ($price_types): ?>
            <h3>Типы цен из последнего файла</h3>
            <table class="widefat striped" style="max-width:760px"><thead><tr><th>Название</th><th>ID для настроек</th></tr></thead><tbody>
                <?php foreach ($price_types as $price_type_id => $price_type_name): ?><tr><td><?= esc_html((string) $price_type_name) ?></td><td><code><?= esc_html((string) $price_type_id) ?></code></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>

        <h2>Проверочная загрузка</h2>
        <form method="post" enctype="multipart/form-data" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <input type="hidden" name="action" value="rb_1c_import">
            <?php wp_nonce_field('rb_1c_import', 'rb_1c_import_nonce'); ?>
            <input type="file" name="file" accept=".xml,.zip,text/xml,application/xml,application/zip" required>
            <label style="margin-left:12px"><input type="checkbox" name="full" value="1"> Полный импорт</label>
            <?php submit_button('Импортировать файл', 'primary', 'submit', false); ?>
        </form>

        <h2>Последние запуски</h2>
        <?php if ($logs): ?>
            <table class="widefat striped"><thead><tr><th>Дата</th><th>Источник</th><th>Результат</th><th>Категории</th><th>Товары</th><th>Предложения</th><th>Сообщение</th></tr></thead><tbody>
                <?php foreach ($logs as $log): ?><tr><td><?= esc_html($log['date'] ?? '') ?></td><td><?= esc_html($log['source'] ?? '') ?></td><td><?= esc_html(($log['success'] ?? false) ? 'Успешно' : 'Ошибка') ?></td><td><?= esc_html((string) ($log['categories'] ?? 0)) ?></td><td><?= esc_html((string) ($log['products'] ?? 0)) ?></td><td><?= esc_html((string) ($log['offers'] ?? 0)) ?></td><td><?= esc_html($log['message'] ?? '') ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php else: ?><p>Импорты еще не запускались.</p><?php endif; ?>
    </div>
    <?php
}

add_action('admin_post_rb_1c_import', 'rb_1c_handle_admin_import');
function rb_1c_handle_admin_import(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Недостаточно прав.');
    }
    check_admin_referer('rb_1c_import', 'rb_1c_import_nonce');

    $file = isset($_FILES['file']) && is_array($_FILES['file']) ? $_FILES['file'] : [];
    $result = rb_1c_import_uploaded_file($file, !empty($_POST['full']), 'Админ-панель');
    wp_safe_redirect(add_query_arg('rb_1c_result', is_wp_error($result) ? 'error' : 'success', admin_url('admin.php?page=rb-1c')));
    exit;
}

add_action('rest_api_init', 'rb_1c_register_rest_route');
function rb_1c_register_rest_route(): void
{
    register_rest_route('rb/v1', '/1c/import', [
        'methods' => 'POST',
        'callback' => 'rb_1c_rest_import',
        'permission_callback' => 'rb_1c_rest_permission',
    ]);
}

function rb_1c_rest_permission(WP_REST_Request $request): bool
{
    $configured = (string) (rb_1c_settings()['token'] ?? '');
    $provided = (string) $request->get_header('X-RB-1C-Token');
    return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
}

function rb_1c_rest_import(WP_REST_Request $request)
{
    $files = $request->get_file_params();
    $file = isset($files['file']) && is_array($files['file']) ? $files['file'] : [];

    $temporary = '';
    if (!$file) {
        $body = $request->get_body();
        if ($body === '') {
            return new WP_Error('rb_1c_file_required', 'Передайте XML или ZIP в поле file.', ['status' => 400]);
        }
        $temporary = wp_tempnam('rb-1c-import.xml');
        if (!$temporary || file_put_contents($temporary, $body) === false) {
            return new WP_Error('rb_1c_temp_failed', 'Не удалось сохранить входящий файл.', ['status' => 500]);
        }
        $file = ['name' => 'import.xml', 'tmp_name' => $temporary, 'error' => UPLOAD_ERR_OK, 'size' => strlen($body)];
    }

    $result = rb_1c_import_uploaded_file($file, (bool) $request->get_param('full'), 'REST API');
    if ($temporary !== '' && is_file($temporary)) {
        @unlink($temporary);
    }

    return is_wp_error($result) ? $result : rest_ensure_response($result);
}

function rb_1c_import_uploaded_file(array $file, bool $full, string $source)
{
    if (!class_exists('XMLReader')) {
        return rb_1c_log_error('На сервере недоступно расширение XMLReader.', $source);
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_file($file['tmp_name'])) {
        return rb_1c_log_error('Файл не был загружен.', $source);
    }
    if ((int) ($file['size'] ?? filesize($file['tmp_name'])) > wp_max_upload_size()) {
        return rb_1c_log_error('Размер файла превышает ограничение загрузки на сервере.', $source);
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['xml', 'zip'], true)) {
        return rb_1c_log_error('Поддерживаются только файлы XML и ZIP.', $source);
    }

    $lock = get_transient('rb_1c_import_lock');
    if ($lock) {
        return new WP_Error('rb_1c_import_locked', 'Другой импорт уже выполняется.', ['status' => 409]);
    }
    set_transient('rb_1c_import_lock', time(), 30 * MINUTE_IN_SECONDS);
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }

    $workspace = trailingslashit(get_temp_dir()) . 'rb-1c-' . wp_generate_uuid4();
    wp_mkdir_p($workspace);
    $result = null;

    try {
        $files = $extension === 'zip'
            ? rb_1c_extract_archive($file['tmp_name'], $workspace)
            : [rb_1c_copy_xml($file['tmp_name'], $workspace . '/import.xml')];
        if (is_wp_error($files)) {
            throw new RuntimeException($files->get_error_message());
        }

        $result = rb_1c_import_files(array_values(array_filter($files)), $workspace, $full);
        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }
        rb_1c_add_log(array_merge($result, ['success' => true, 'source' => $source, 'message' => 'Импорт завершен.']));
    } catch (Throwable $error) {
        $result = rb_1c_log_error($error->getMessage(), $source);
    } finally {
        rb_1c_remove_directory($workspace);
        delete_transient('rb_1c_import_lock');
    }

    return $result;
}

function rb_1c_copy_xml(string $source, string $target): string
{
    if (!copy($source, $target)) {
        throw new RuntimeException('Не удалось подготовить XML к импорту.');
    }
    return $target;
}

function rb_1c_extract_archive(string $archive, string $workspace)
{
    if (!class_exists('ZipArchive')) {
        return new WP_Error('rb_1c_zip_unavailable', 'На сервере недоступно расширение ZipArchive.');
    }

    $zip = new ZipArchive();
    if ($zip->open($archive) !== true) {
        return new WP_Error('rb_1c_zip_invalid', 'Не удалось открыть ZIP-архив.');
    }

    $xml_files = [];
    $total_size = 0;
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $info = $zip->statIndex($index);
        $name = str_replace('\\', '/', (string) ($info['name'] ?? ''));
        if ($name === '' || substr($name, -1) === '/' || strpos($name, '../') !== false || strpos($name, ':') !== false || substr($name, 0, 1) === '/') {
            continue;
        }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xml', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            continue;
        }
        $total_size += (int) ($info['size'] ?? 0);
        if ($total_size > 300 * MB_IN_BYTES) {
            $zip->close();
            return new WP_Error('rb_1c_zip_too_large', 'Распакованный архив превышает 300 МБ.');
        }

        $target = $workspace . '/' . ltrim($name, '/');
        wp_mkdir_p(dirname($target));
        $input = $zip->getStream($info['name']);
        $output = fopen($target, 'wb');
        if (!$input || !$output) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
            continue;
        }
        stream_copy_to_stream($input, $output);
        fclose($input);
        fclose($output);
        if ($extension === 'xml') {
            $xml_files[] = $target;
        }
    }
    $zip->close();

    return $xml_files ?: new WP_Error('rb_1c_xml_missing', 'В архиве не найден XML-файл CommerceML.');
}

function rb_1c_import_files(array $files, string $workspace, bool $full)
{
    $context = [
        'properties' => [],
        'price_types' => [],
        'seen_products' => [],
        'categories' => 0,
        'products' => 0,
        'offers' => 0,
        'images' => 0,
        'offer_stock' => [],
    ];

    foreach ($files as $file) {
        rb_1c_read_reference_data($file, $context);
    }
    foreach ($files as $file) {
        rb_1c_import_products($file, $workspace, $context);
    }
    foreach ($files as $file) {
        rb_1c_import_offers($file, $context);
    }
    rb_1c_apply_offer_stock($context['offer_stock']);
    if ($full && $context['seen_products']) {
        rb_1c_zero_missing_products($context['seen_products']);
    }

    update_option('rb_1c_last_price_types', $context['price_types'], false);
    return array_intersect_key($context, array_flip(['categories', 'products', 'offers', 'images']));
}

function rb_1c_open_reader(string $file)
{
    $reader = new XMLReader();
    if (!$reader->open($file, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
        return new WP_Error('rb_1c_xml_invalid', 'Не удалось открыть XML: ' . basename($file));
    }
    return $reader;
}

function rb_1c_read_reference_data(string $file, array &$context): void
{
    $reader = rb_1c_open_reader($file);
    if (is_wp_error($reader)) {
        throw new RuntimeException($reader->get_error_message());
    }

    while ($reader->read()) {
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            continue;
        }
        if ($reader->localName === 'Классификатор') {
            $node = rb_1c_simplexml($reader->readOuterXML());
            if ($node) {
                rb_1c_import_classifier($node, $context);
            }
        } elseif ($reader->localName === 'ТипыЦен') {
            $node = rb_1c_simplexml($reader->readOuterXML());
            if ($node) {
                foreach (rb_1c_children($node, 'ТипЦены') as $price_type) {
                    $id = rb_1c_text($price_type, 'Ид');
                    if ($id !== '') $context['price_types'][$id] = rb_1c_text($price_type, 'Наименование');
                }
            }
        }
    }
    $reader->close();
}

function rb_1c_import_classifier(SimpleXMLElement $classifier, array &$context): void
{
    foreach ($classifier->xpath('.//*[local-name()="Свойство"]') ?: [] as $property) {
        $id = rb_1c_text($property, 'Ид');
        if ($id !== '') $context['properties'][$id] = rb_1c_text($property, 'Наименование');
    }

    $groups = rb_1c_child($classifier, 'Группы');
    if ($groups) {
        foreach (rb_1c_children($groups, 'Группа') as $group) {
            rb_1c_import_group($group, 0, $context);
        }
    }
}

function rb_1c_import_group(SimpleXMLElement $group, int $parent_id, array &$context): void
{
    $external_id = rb_1c_text($group, 'Ид');
    $name = rb_1c_text($group, 'Наименование');
    if ($external_id === '' || $name === '') return;

    $term_id = rb_1c_find_term($external_id);
    if ($term_id) {
        wp_update_term($term_id, 'rb_product_category', ['name' => $name, 'parent' => $parent_id]);
    } else {
        $created = wp_insert_term($name, 'rb_product_category', ['parent' => $parent_id]);
        if (is_wp_error($created)) return;
        $term_id = (int) $created['term_id'];
        update_term_meta($term_id, 'rb_1c_external_id', $external_id);
        $context['categories']++;
    }

    $children = rb_1c_child($group, 'Группы');
    if ($children) {
        foreach (rb_1c_children($children, 'Группа') as $child) rb_1c_import_group($child, $term_id, $context);
    }
}

function rb_1c_import_products(string $file, string $workspace, array &$context): void
{
    $reader = rb_1c_open_reader($file);
    if (is_wp_error($reader)) throw new RuntimeException($reader->get_error_message());

    while ($reader->read()) {
        if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'Товар') continue;
        $product = rb_1c_simplexml($reader->readOuterXML());
        if (!$product) continue;

        $external_id = rb_1c_text($product, 'Ид');
        $title = rb_1c_text($product, 'Наименование');
        if ($external_id === '' || $title === '') continue;

        $post_id = rb_1c_find_product($external_id);
        $post_data = [
            'post_type' => 'rb_product',
            'post_title' => $title,
            'post_content' => rb_1c_text($product, 'Описание'),
        ];
        if ($post_id) {
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_data['post_status'] = rb_1c_settings()['publish_new_products'] === '1' ? 'publish' : 'draft';
            $post_id = wp_insert_post($post_data);
            if (is_wp_error($post_id) || !$post_id) continue;
            update_post_meta($post_id, 'rb_external_id', $external_id);
        }

        update_post_meta($post_id, 'rb_sku', rb_1c_text($product, 'Артикул') ?: 'RB-' . $post_id);
        update_post_meta($post_id, 'rb_unit', rb_1c_unit_name($product) ?: 'шт');
        update_post_meta($post_id, 'rb_1c_updated_at', current_time('mysql'));
        update_post_meta($post_id, 'rb_1c_payload_hash', md5($product->asXML()));
        rb_1c_apply_product_properties($post_id, $product, $context['properties']);

        $groups = rb_1c_child($product, 'Группы');
        $term_ids = [];
        if ($groups) {
            foreach (rb_1c_children($groups, 'Ид') as $group_id) {
                $term_id = rb_1c_find_term(trim((string) $group_id));
                if ($term_id) $term_ids[] = $term_id;
            }
        }
        if ($term_ids) wp_set_object_terms($post_id, $term_ids, 'rb_product_category');

        $image = rb_1c_text($product, 'Картинка');
        if ($image !== '' && !has_post_thumbnail($post_id) && rb_1c_attach_image($post_id, $workspace, $image)) {
            $context['images']++;
        }

        $context['seen_products'][$external_id] = $post_id;
        $context['products']++;
    }
    $reader->close();
}

function rb_1c_import_offers(string $file, array &$context): void
{
    $reader = rb_1c_open_reader($file);
    if (is_wp_error($reader)) throw new RuntimeException($reader->get_error_message());
    while ($reader->read()) {
        if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'Предложение') continue;
        $offer = rb_1c_simplexml($reader->readOuterXML());
        if (!$offer) continue;

        $offer_id = rb_1c_text($offer, 'Ид');
        $product_external_id = preg_split('/[#;]/', $offer_id)[0] ?? $offer_id;
        $post_id = $context['seen_products'][$product_external_id] ?? rb_1c_find_product($product_external_id);
        if (!$post_id) continue;

        $size = rb_1c_offer_size($offer);
        $prices = rb_1c_offer_prices($offer);
        $settings = rb_1c_settings();
        $retail_price = rb_1c_select_price($prices, $settings['retail_price_type']);
        $wholesale_price = rb_1c_select_price($prices, $settings['wholesale_price_type']);
        if ($retail_price !== null) update_post_meta($post_id, $size === '1000' ? 'rb_price_1000' : 'rb_price_200', (string) max(0, (int) round($retail_price)));
        if ($wholesale_price !== null) update_post_meta($post_id, 'rb_wholesale_price', (string) max(0, (int) round($wholesale_price)));

        $quantity = rb_1c_offer_quantity($offer);
        $context['offer_stock'][$post_id][$size] = ($context['offer_stock'][$post_id][$size] ?? 0) + $quantity;
        update_post_meta($post_id, 'rb_1c_updated_at', current_time('mysql'));
        $context['offers']++;
    }
    $reader->close();

}

function rb_1c_apply_offer_stock(array $stock): void
{
    foreach ($stock as $post_id => $sizes) {
        $stock_200 = max(0, (int) ($sizes['200'] ?? 0));
        $stock_1000 = max(0, (int) ($sizes['1000'] ?? 0));
        update_post_meta((int) $post_id, 'rb_stock_200', $stock_200);
        update_post_meta((int) $post_id, 'rb_stock_1000', $stock_1000);
        update_post_meta((int) $post_id, 'rb_stock', $stock_200 + $stock_1000);
    }
}

function rb_1c_apply_product_properties(int $post_id, SimpleXMLElement $product, array $property_names): void
{
    $values = rb_1c_child($product, 'ЗначенияСвойств');
    if (!$values) return;

    foreach (rb_1c_children($values, 'ЗначенияСвойства') as $property) {
        $property_id = rb_1c_text($property, 'Ид');
        $property_name = (string) ($property_names[$property_id] ?? $property_id);
        $name = function_exists('mb_strtolower') ? mb_strtolower($property_name) : strtolower($property_name);
        $value = rb_1c_text($property, 'Значение');
        $meta_key = '';
        if (strpos($name, 'вкус') !== false || strpos($name, 'дескрип') !== false) $meta_key = 'rb_descriptors';
        elseif (strpos($name, 'обработ') !== false) $meta_key = 'rb_process';
        elseif (strpos($name, 'обжарк') !== false) $meta_key = 'rb_roast';
        elseif (strpos($name, 'стран') !== false) $meta_key = 'rb_country';
        elseif (strpos($name, 'регион') !== false) $meta_key = 'rb_region';
        elseif (strpos($name, 'высот') !== false) $meta_key = 'rb_height';
        elseif (strpos($name, 'разновид') !== false || strpos($name, 'сорт') !== false) $meta_key = 'rb_variety';
        if ($meta_key && $value !== '') update_post_meta($post_id, $meta_key, sanitize_textarea_field($value));
    }
}

function rb_1c_offer_prices(SimpleXMLElement $offer): array
{
    $result = [];
    $prices = rb_1c_child($offer, 'Цены');
    if (!$prices) return $result;
    foreach (rb_1c_children($prices, 'Цена') as $price) {
        $type_id = rb_1c_text($price, 'ИдТипаЦены');
        $amount = (float) str_replace(',', '.', rb_1c_text($price, 'ЦенаЗаЕдиницу'));
        if ($amount >= 0) $result[$type_id] = $amount;
    }
    return $result;
}

function rb_1c_select_price(array $prices, string $type_id): ?float
{
    if (!$prices) return null;
    if ($type_id !== '' && array_key_exists($type_id, $prices)) return (float) $prices[$type_id];
    return $type_id === '' ? (float) reset($prices) : null;
}

function rb_1c_offer_size(SimpleXMLElement $offer): string
{
    $text = rb_1c_text($offer, 'Наименование') . ' ' . $offer->asXML();
    return preg_match('/(?:1000\s*(?:г|гр)?|1\s*кг)/ui', $text) ? '1000' : '200';
}

function rb_1c_offer_quantity(SimpleXMLElement $offer): int
{
    $warehouse_nodes = $offer->xpath('.//*[local-name()="Склад"]') ?: [];
    if ($warehouse_nodes) {
        $sum = 0.0;
        foreach ($warehouse_nodes as $warehouse) $sum += (float) str_replace(',', '.', (string) ($warehouse['КоличествоНаСкладе'] ?? 0));
        if ($sum > 0) return max(0, (int) floor($sum));
    }
    return max(0, (int) floor((float) str_replace(',', '.', rb_1c_text($offer, 'Количество'))));
}

function rb_1c_unit_name(SimpleXMLElement $product): string
{
    $unit = rb_1c_child($product, 'БазоваяЕдиница');
    return $unit ? trim((string) ($unit['НаименованиеПолное'] ?? $unit['Код'] ?? '')) : '';
}

function rb_1c_find_product(string $external_id): int
{
    if ($external_id === '') return 0;
    $ids = get_posts(['post_type' => 'rb_product', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'rb_external_id', 'meta_value' => $external_id]);
    return $ids ? (int) $ids[0] : 0;
}

function rb_1c_find_term(string $external_id): int
{
    if ($external_id === '') return 0;
    $terms = get_terms(['taxonomy' => 'rb_product_category', 'hide_empty' => false, 'number' => 1, 'fields' => 'ids', 'meta_key' => 'rb_1c_external_id', 'meta_value' => $external_id, 'rb_skip_category_order' => true]);
    return is_wp_error($terms) || !$terms ? 0 : (int) $terms[0];
}

function rb_1c_attach_image(int $post_id, string $workspace, string $relative_path): bool
{
    $base = realpath($workspace);
    $source = realpath($workspace . '/' . ltrim(str_replace('\\', '/', $relative_path), '/'));
    if (!$base || !$source || strpos($source, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($source)) return false;

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $temporary = wp_tempnam(basename($source));
    if (!$temporary || !copy($source, $temporary)) return false;
    $attachment_id = media_handle_sideload(['name' => basename($source), 'tmp_name' => $temporary, 'error' => 0, 'size' => filesize($source)], $post_id);
    if (is_wp_error($attachment_id)) {
        @unlink($temporary);
        return false;
    }
    set_post_thumbnail($post_id, $attachment_id);
    return true;
}

function rb_1c_zero_missing_products(array $seen_products): void
{
    $ids = get_posts(['post_type' => 'rb_product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => [['key' => 'rb_external_id', 'compare' => 'EXISTS']]]);
    foreach ($ids as $post_id) {
        $external_id = (string) get_post_meta($post_id, 'rb_external_id', true);
        if (!isset($seen_products[$external_id])) {
            update_post_meta($post_id, 'rb_stock_200', 0);
            update_post_meta($post_id, 'rb_stock_1000', 0);
            update_post_meta($post_id, 'rb_stock', 0);
        }
    }
}

function rb_1c_simplexml(string $xml): ?SimpleXMLElement
{
    if ($xml === '') return null;
    $previous = libxml_use_internal_errors(true);
    $node = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return $node instanceof SimpleXMLElement ? $node : null;
}

function rb_1c_child(SimpleXMLElement $node, string $name): ?SimpleXMLElement
{
    $children = $node->xpath('./*[local-name()="' . $name . '"]') ?: [];
    return isset($children[0]) && $children[0] instanceof SimpleXMLElement ? $children[0] : null;
}

function rb_1c_children(SimpleXMLElement $node, string $name): array
{
    return $node->xpath('./*[local-name()="' . $name . '"]') ?: [];
}

function rb_1c_text(SimpleXMLElement $node, string $name): string
{
    $child = rb_1c_child($node, $name);
    return $child ? trim((string) $child) : '';
}

function rb_1c_add_log(array $log): void
{
    $logs = (array) get_option('rb_1c_import_logs', []);
    array_unshift($logs, array_merge(['date' => current_time('mysql'), 'categories' => 0, 'products' => 0, 'offers' => 0], $log));
    update_option('rb_1c_import_logs', array_slice($logs, 0, 30), false);
}

function rb_1c_log_error(string $message, string $source): WP_Error
{
    rb_1c_add_log(['success' => false, 'source' => $source, 'message' => $message]);
    return new WP_Error('rb_1c_import_failed', $message, ['status' => 422]);
}

function rb_1c_remove_directory(string $directory): void
{
    if (!is_dir($directory)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $item) {
        if ($item->isDir()) rmdir($item->getPathname()); else unlink($item->getPathname());
    }
    rmdir($directory);
}
