<?php
/**
 * ROASTBERRY THEME functions.
 *
 * @package ROASTBERRY_THEME
 */

require_once __DIR__ . '/includes/image-optimizer.php';
require_once __DIR__ . '/includes/translate-slug.php';
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/legal.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cdek.php';
require_once __DIR__ . '/includes/store.php';
require_once __DIR__ . '/includes/commerceml.php';
require_once __DIR__ . '/includes/yookassa.php';
require_once __DIR__ . '/includes/discounts.php';
require_once __DIR__ . '/includes/business.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/admin-orders.php';

add_action('init', 'rb_start_cart_session', 1);
function rb_start_cart_session(): void
{
    if (!session_id() && !headers_sent()) {
        session_start();
    }
}

add_action('after_setup_theme', 'rb_theme_setup');
function rb_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => 'Основное меню',
    ]);
}

add_action('wp_enqueue_scripts', 'rb_enqueue_assets');
function rb_enqueue_assets(): void
{
    wp_enqueue_style('rb-theme', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
    wp_enqueue_style('rb-main', rb_asset_url('css/style.css'), [], rb_asset_version('css/style.css'));

    wp_enqueue_script('rb-phone-mask', rb_asset_url('js/phone-mask.js'), [], rb_asset_version('js/phone-mask.js'), true);
    wp_enqueue_script('rb-main', rb_asset_url('js/main.js'), [], rb_asset_version('js/main.js'), true);
    wp_enqueue_script('rb-cookie-consent', rb_asset_url('js/cookie-consent.js'), [], rb_asset_version('js/cookie-consent.js'), true);
}

function rb_asset_url(string $path): string
{
    return get_template_directory_uri() . '/assets/' . ltrim($path, '/');
}

function rb_asset_version(string $path): string
{
    $file = get_template_directory() . '/assets/' . ltrim($path, '/');

    return file_exists($file) ? (string) filemtime($file) : wp_get_theme()->get('Version');
}

function rb_parse_price(string $price): int
{
    return (int) preg_replace('/[^\d]/', '', $price);
}

function rb_format_price(int $price): string
{
    return number_format($price, 0, '', ' ') . ' ₽';
}

function rb_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }

    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }

    if (strpos($digits, '77') === 0) {
        $digits = substr($digits, 1);
    }

    return $digits;
}

function rb_format_phone(string $phone): string
{
    $digits = rb_normalize_phone($phone);

    if (strlen($digits) === 11 && $digits[0] === '7') {
        return sprintf(
            '+7 (%s) %s-%s-%s',
            substr($digits, 1, 3),
            substr($digits, 4, 3),
            substr($digits, 7, 2),
            substr($digits, 9, 2)
        );
    }

    return $phone;
}

function rb_is_valid_ru_phone(string $phone): bool
{
    return (bool) preg_match('/^7[3489]\d{9}$/', rb_normalize_phone($phone));
}

function rb_phone_input_pattern(): string
{
    return '\\+7 \\([0-9]{3}\\) [0-9]{3}-[0-9]{2}-[0-9]{2}';
}

function rb_phone_input_title(): string
{
    return 'Введите российский номер в формате +7 (999) 123-45-67';
}

function rb_phone_href(string $phone): string
{
    $digits = rb_normalize_phone($phone);

    return $digits ? '+' . $digits : $phone;
}

function rb_get_cart(): array
{
    return isset($_SESSION['rb_cart']) && is_array($_SESSION['rb_cart']) ? $_SESSION['rb_cart'] : [];
}

add_action('init', 'rb_restore_user_cart', 5);
function rb_restore_user_cart(): void
{
    if (!is_user_logged_in() || isset($_SESSION['rb_cart'])) return;
    $saved_cart = get_user_meta(get_current_user_id(), 'rb_saved_cart', true);
    if (is_array($saved_cart)) $_SESSION['rb_cart'] = $saved_cart;
}

function rb_save_cart(array $cart): void
{
    $_SESSION['rb_cart'] = $cart;

    if (is_user_logged_in() && function_exists('rb_schedule_abandoned_cart_reminder')) {
        rb_schedule_abandoned_cart_reminder(get_current_user_id(), $cart);
    }
}

function rb_cart_count(): int
{
    $count = 0;

    foreach (rb_get_cart() as $item) {
        $count += max(0, (int) ($item['quantity'] ?? 0));
    }

    return $count;
}

function rb_cart_total(): int
{
    $total = 0;

    foreach (rb_get_cart() as $item) {
        $total += (int) ($item['price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
    }

    return $total;
}

function rb_cart_widget_data(): array
{
    $items = [];
    $count = 0;
    $total = 0;
    foreach (rb_get_cart() as $key => $item) {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $product_id = absint($item['product_id'] ?? 0);
        $price = $product_id ? rb_product_price_by_size($product_id, rb_cart_item_size($item)) : max(0, (int) ($item['price'] ?? 0));
        $count += $quantity;
        $total += $price * $quantity;
        $items[] = [
            'key' => sanitize_text_field((string) $key),
            'title' => sanitize_text_field((string) ($item['title'] ?? 'Товар')),
            'details' => sanitize_text_field(trim((string) ($item['size'] ?? '') . ', ' . (string) ($item['grind'] ?? ''), ', ')),
            'quantity' => $quantity,
            'line_total' => $price * $quantity,
            'line_total_formatted' => rb_format_price($price * $quantity),
            'image' => esc_url_raw((string) ($item['image'] ?? '')),
            'url' => esc_url_raw((string) ($item['url'] ?? route_url('catalog'))),
        ];
    }

    return [
        'count' => $count,
        'total' => $total,
        'total_formatted' => rb_format_price($total),
        'cart_url' => route_url('cart'),
        'items' => $items,
    ];
}

function rb_is_cart_ajax_request(): bool
{
    return !empty($_POST['rb_cart_ajax']);
}

function rb_set_checkout_error(string $message): void
{
    $_SESSION['rb_checkout_error'] = $message;
}

function rb_pull_checkout_error(): string
{
    $message = isset($_SESSION['rb_checkout_error']) ? (string) $_SESSION['rb_checkout_error'] : '';
    unset($_SESSION['rb_checkout_error']);

    return $message;
}

function rb_cart_items_text(): string
{
    $lines = [];

    foreach (rb_get_cart() as $item) {
        $lines[] = sprintf(
            '%s, %s, %s, %d шт. — %s',
            $item['title'] ?? '',
            $item['size'] ?? '',
            $item['grind'] ?? '',
            (int) ($item['quantity'] ?? 1),
            rb_format_price((int) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1))
        );
    }

    return implode("\n", $lines);
}

function rb_cart_item_key(int $product_id, string $size, string $grind): string
{
    return md5($product_id . '|' . $size . '|' . $grind);
}

function rb_product_price_by_size(int $product_id, string $size): int
{
    $meta_key = $size === '1000' ? 'rb_price_1000' : 'rb_price_200';
    $price = (string) get_post_meta($product_id, $meta_key, true);

    return rb_parse_price($price);
}

function rb_contacts(): array
{
    global $contacts;

    $defaults = is_array($contacts ?? null) ? $contacts : [];

    return wp_parse_args((array) get_option('rb_contact_settings', []), $defaults);
}

function rb_contact_settings_sanitize(array $input): array
{
    $current = rb_contacts();
    $phone = sanitize_text_field((string) ($input['phone'] ?? ''));

    if (!rb_is_valid_ru_phone($phone)) {
        add_settings_error('rb_contact_settings', 'rb_contact_phone', rb_phone_input_title());
        $phone = (string) ($current['phone'] ?? '');
    } else {
        $phone = rb_format_phone($phone);
    }

    return [
        'phone' => $phone,
        'address' => sanitize_text_field((string) ($input['address'] ?? '')),
        'vk' => esc_url_raw((string) ($input['vk'] ?? '')),
        'tg' => esc_url_raw((string) ($input['tg'] ?? '')),
        'manager' => esc_url_raw((string) ($input['manager'] ?? '')),
        'trainer' => esc_url_raw((string) ($input['trainer'] ?? '')),
        'map' => esc_url_raw((string) ($input['map'] ?? '')),
    ];
}

add_action('admin_init', 'rb_register_contact_settings');
function rb_register_contact_settings(): void
{
    register_setting('rb_contact_settings_group', 'rb_contact_settings', [
        'type' => 'array',
        'sanitize_callback' => 'rb_contact_settings_sanitize',
        'default' => [],
    ]);
}

function rb_render_contact_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = rb_contacts();
    ?>
    <div class="wrap">
        <h1>Контакты сайта</h1>
        <p>Эти данные используются в шапке, подвале и контактных блоках сайта.</p>
        <?php settings_errors('rb_contact_settings'); ?>
        <form method="post" action="options.php">
            <?php settings_fields('rb_contact_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="rb-contact-phone">Телефон</label></th>
                    <td><input class="regular-text" id="rb-contact-phone" name="rb_contact_settings[phone]" type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" value="<?= esc_attr((string) ($settings['phone'] ?? '')) ?>" placeholder="+7 (___) ___-__-__" required></td>
                </tr>
                <tr>
                    <th><label for="rb-contact-address">Адрес</label></th>
                    <td><input class="regular-text" id="rb-contact-address" name="rb_contact_settings[address]" value="<?= esc_attr((string) ($settings['address'] ?? '')) ?>" required></td>
                </tr>
                <tr>
                    <th><label for="rb-contact-vk">ВКонтакте</label></th>
                    <td><input class="regular-text code" id="rb-contact-vk" name="rb_contact_settings[vk]" type="url" value="<?= esc_attr((string) ($settings['vk'] ?? '')) ?>" placeholder="https://vk.com/..."></td>
                </tr>
                <tr>
                    <th><label for="rb-contact-tg">Telegram</label></th>
                    <td><input class="regular-text code" id="rb-contact-tg" name="rb_contact_settings[tg]" type="url" value="<?= esc_attr((string) ($settings['tg'] ?? '')) ?>" placeholder="https://t.me/..."></td>
                </tr>
                <tr>
                    <th><label for="rb-contact-manager">Связь с менеджером</label></th>
                    <td><input class="regular-text code" id="rb-contact-manager" name="rb_contact_settings[manager]" type="url" value="<?= esc_attr((string) ($settings['manager'] ?? '')) ?>" placeholder="https://t.me/..."></td>
                </tr>
                <tr>
                    <th><label for="rb-contact-trainer">Связь по обучению</label></th>
                    <td><input class="regular-text code" id="rb-contact-trainer" name="rb_contact_settings[trainer]" type="url" value="<?= esc_attr((string) ($settings['trainer'] ?? '')) ?>" placeholder="https://t.me/..."></td>
                </tr>
                <tr>
                    <th><label for="rb-contact-map">Ссылка на карту</label></th>
                    <td><input class="regular-text code" id="rb-contact-map" name="rb_contact_settings[map]" type="url" value="<?= esc_attr((string) ($settings['map'] ?? '')) ?>" placeholder="https://yandex.ru/maps/..."><p class="description">Используется как резервная ссылка на странице контактов.</p></td>
                </tr>
            </table>
            <?php submit_button('Сохранить контакты'); ?>
        </form>
    </div>
    <?php
}

function rb_menu(): array
{
    global $rb_menu_items;

    return is_array($rb_menu_items ?? null) ? $rb_menu_items : [];
}

function rb_load_page_part(string $name): void
{
    global $heroCards, $news, $brewMethods, $products, $categories, $contacts, $courses, $rb_menu_items;

    $contacts = rb_contacts();

    $file = get_template_directory() . '/pages/' . sanitize_file_name($name) . '.php';

    if (file_exists($file)) {
        require $file;
    }
}

add_action('init', 'rb_register_content_types');
function rb_register_content_types(): void
{
    register_post_type('rb_product', [
        'labels' => [
            'name' => 'Товары',
            'singular_name' => 'Товар',
            'add_new_item' => 'Добавить товар',
            'edit_item' => 'Редактировать товар',
            'menu_name' => 'Товары',
        ],
        'public' => true,
        'show_in_menu' => 'rb-roasters',
        'menu_icon' => 'dashicons-coffee',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'catalog', 'with_front' => false],
        'show_in_rest' => true,
    ]);

    register_taxonomy('rb_product_category', ['rb_product'], [
        'labels' => [
            'name' => 'Категории товаров',
            'singular_name' => 'Категория товара',
            'add_new_item' => 'Добавить категорию',
            'edit_item' => 'Редактировать категорию',
        ],
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'coffee-category', 'with_front' => false],
        'show_in_rest' => true,
    ]);

    register_post_type('rb_order', [
        'labels' => [
            'name' => 'Заказы',
            'singular_name' => 'Заказ',
            'add_new_item' => 'Добавить заказ',
            'edit_item' => 'Редактировать заказ',
            'menu_name' => 'Заказы',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title', 'author'],
        'capability_type' => 'post',
    ]);

    register_post_type('rb_article', [
        'labels' => [
            'name' => 'Новости и статьи',
            'singular_name' => 'Новость',
            'add_new_item' => 'Добавить новость',
            'edit_item' => 'Редактировать новость',
            'menu_name' => 'Новости и статьи',
        ],
        'public' => true,
        'show_in_menu' => 'rb-roasters',
        'menu_icon' => 'dashicons-megaphone',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'news', 'with_front' => false],
        'show_in_rest' => true,
    ]);

    register_post_type('rb_brew_method', [
        'labels' => [
            'name' => 'Способы приготовления',
            'singular_name' => 'Способ приготовления',
            'add_new_item' => 'Добавить способ приготовления',
            'edit_item' => 'Редактировать способ приготовления',
            'menu_name' => 'Способы приготовления',
        ],
        'public' => true,
        'show_in_menu' => 'rb-roasters',
        'menu_icon' => 'dashicons-coffee',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'brewing', 'with_front' => false],
        'show_in_rest' => true,
    ]);

    register_post_type('rb_training', [
        'labels' => [
            'name' => 'Курсы',
            'singular_name' => 'Курс',
            'add_new_item' => 'Добавить курс',
            'edit_item' => 'Редактировать курс',
            'menu_name' => 'Курсы',
        ],
        'public' => true,
        'show_in_menu' => 'rb-roasters',
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'training', 'with_front' => false],
        'show_in_rest' => true,
    ]);
}

add_action('rest_api_init', 'rb_register_product_search_route');
function rb_register_product_search_route(): void
{
    register_rest_route('rb/v1', '/product-search', [
        'methods' => 'GET',
        'callback' => 'rb_product_search_suggestions',
        'permission_callback' => '__return_true',
        'args' => [
            'q' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]);
}

function rb_product_search_suggestions(WP_REST_Request $request)
{
    $search_query = trim((string) $request->get_param('q'));
    $search_length = function_exists('mb_strlen') ? mb_strlen($search_query) : strlen($search_query);
    if ($search_length < 2) {
        return rest_ensure_response([]);
    }

    $products_query = new WP_Query([
        'post_type' => 'rb_product',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        's' => $search_query,
        'orderby' => 'relevance',
        'order' => 'DESC',
        'no_found_rows' => true,
    ]);

    $suggestions = [];
    foreach ($products_query->posts as $product) {
        $categories = get_the_terms($product->ID, 'rb_product_category');
        $suggestions[] = [
            'title' => get_the_title($product),
            'url' => get_permalink($product),
            'image' => get_the_post_thumbnail_url($product, 'thumbnail') ?: rb_asset_url('img/1.webp'),
            'category' => !is_wp_error($categories) && $categories ? implode(', ', wp_list_pluck($categories, 'name')) : 'Товар',
        ];
    }

    return rest_ensure_response($suggestions);
}

add_action('add_meta_boxes', 'rb_register_meta_boxes');
function rb_register_meta_boxes(): void
{
    add_meta_box('rb_product_details', 'Характеристики товара', 'rb_render_product_meta_box', 'rb_product', 'normal', 'high');
    add_meta_box('rb_product_prices', 'Цены и наличие', 'rb_render_product_prices_meta_box', 'rb_product', 'side', 'default');
    add_meta_box('rb_product_integration', 'Учет и интеграция', 'rb_render_product_integration_meta_box', 'rb_product', 'side', 'default');
    add_meta_box('rb_product_recommendations', 'С этим товаром рекомендуем', 'rb_render_product_recommendations_meta_box', 'rb_product', 'normal', 'default');
    add_meta_box('rb_order_details', 'Данные заказа', 'rb_render_order_meta_box', 'rb_order', 'normal', 'high');
    add_meta_box('rb_training_details', 'Параметры курса', 'rb_render_training_meta_box', 'rb_training', 'normal', 'high');
    add_meta_box('rb_brew_method_details', 'Настройки способа приготовления', 'rb_render_brew_method_meta_box', 'rb_brew_method', 'normal', 'high');
}

add_action('add_meta_boxes_page', 'rb_register_business_page_meta_box');
function rb_register_business_page_meta_box(WP_Post $post): void
{
    if (get_page_template_slug($post->ID) !== 'template-business.php') {
        return;
    }

    add_meta_box(
        'rb_business_logos',
        'Логотипы партнеров',
        'rb_render_business_logos_meta_box',
        'page',
        'normal',
        'default'
    );
}

add_action('add_meta_boxes_page', 'rb_register_contacts_page_meta_box');
function rb_register_contacts_page_meta_box(WP_Post $post): void
{
    if (get_page_template_slug($post->ID) !== 'template-contacts.php') {
        return;
    }

    add_meta_box(
        'rb_contacts_map',
        'Карта на странице контактов',
        'rb_render_contacts_map_meta_box',
        'page',
        'normal',
        'default'
    );
}

function rb_contacts_default_map_iframe(): string
{
    return '<iframe src="https://yandex.ru/map-widget/v1/org/rb_roasters/232344819285/?from=mapframe&amp;ll=56.147100%2C58.005299&amp;z=19.28" width="560" height="400" frameborder="0" allowfullscreen="true" loading="lazy" title="Карта Roastberry Coffee Roasters"></iframe>';
}

function rb_sanitize_contacts_map_iframe(string $iframe): string
{
    return wp_kses($iframe, [
        'iframe' => [
            'src' => true,
            'width' => true,
            'height' => true,
            'frameborder' => true,
            'allowfullscreen' => true,
            'loading' => true,
            'title' => true,
            'referrerpolicy' => true,
            'allow' => true,
            'style' => true,
        ],
    ]);
}

function rb_get_contacts_map_iframe(int $post_id): string
{
    $iframe = (string) get_post_meta($post_id, 'rb_contacts_map_iframe', true);

    $iframe = rb_sanitize_contacts_map_iframe($iframe !== '' ? $iframe : rb_contacts_default_map_iframe());
    return rb_cookie_deferred_embed($iframe, 'Карта Roastberry Coffee Roasters');
}

function rb_render_contacts_map_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_contacts_map', 'rb_contacts_map_nonce');
    $saved_iframe = (string) get_post_meta($post->ID, 'rb_contacts_map_iframe', true);
    $iframe = $saved_iframe !== '' ? $saved_iframe : rb_contacts_default_map_iframe();
    ?>
    <p><label for="rb-contacts-map-iframe"><strong>Код iframe карты</strong></label></p>
    <textarea class="widefat code" rows="8" id="rb-contacts-map-iframe" name="rb_contacts_map_iframe"><?= esc_textarea($iframe) ?></textarea>
    <p class="description">Вставьте iframe, полученный в конструкторе Яндекс.Карт. Внешние обертки и небезопасные атрибуты будут удалены при сохранении.</p>
    <?php
}

add_action('save_post_page', 'rb_save_contacts_map_meta');
function rb_save_contacts_map_meta(int $post_id): void
{
    if (!isset($_POST['rb_contacts_map_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_contacts_map_nonce'])), 'rb_save_contacts_map')) {
        return;
    }

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (get_page_template_slug($post_id) !== 'template-contacts.php') {
        return;
    }

    $iframe = isset($_POST['rb_contacts_map_iframe']) ? wp_unslash($_POST['rb_contacts_map_iframe']) : '';
    update_post_meta($post_id, 'rb_contacts_map_iframe', rb_sanitize_contacts_map_iframe((string) $iframe));
}

function rb_get_business_logos(int $post_id): array
{
    $logo_ids = get_post_meta($post_id, 'rb_business_logo_ids', true);

    return array_values(array_filter(array_map('absint', is_array($logo_ids) ? $logo_ids : []), 'wp_attachment_is_image'));
}

function rb_render_business_logos_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_business_logos', 'rb_business_logos_nonce');
    $logo_ids = rb_get_business_logos($post->ID);
    ?>
    <div class="rb-business-logos" data-business-logos-box>
        <input type="hidden" name="rb_business_logo_ids" value="<?= esc_attr(implode(',', $logo_ids)) ?>" data-business-logos-input>
        <div class="rb-business-logos__list" data-business-logos-list>
            <?php foreach ($logo_ids as $logo_id): ?>
                <div class="rb-business-logos__item" data-logo-id="<?= esc_attr($logo_id) ?>">
                    <?= wp_get_attachment_image($logo_id, 'thumbnail') ?>
                    <button type="button" data-remove-logo aria-label="Удалить логотип" title="Удалить">×</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-secondary" data-add-business-logos>Добавить логотипы</button>
        <p class="description">Можно выбрать несколько изображений из медиабиблиотеки. Порядок сохраняется в порядке добавления.</p>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', 'rb_enqueue_business_logos_admin_assets');
function rb_enqueue_business_logos_admin_assets(string $hook_suffix): void
{
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style('rb-business-logos-admin', rb_asset_url('css/admin-business-logos.css'), [], rb_asset_version('css/admin-business-logos.css'));
    wp_enqueue_script('rb-business-logos-admin', rb_asset_url('js/admin-business-logos.js'), ['media-editor'], rb_asset_version('js/admin-business-logos.js'), true);
}

add_action('save_post_page', 'rb_save_business_logos_meta');
function rb_save_business_logos_meta(int $post_id): void
{
    if (!isset($_POST['rb_business_logos_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_business_logos_nonce'])), 'rb_save_business_logos')) {
        return;
    }

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw_ids = isset($_POST['rb_business_logo_ids']) ? sanitize_text_field(wp_unslash($_POST['rb_business_logo_ids'])) : '';
    $logo_ids = array_values(array_filter(array_unique(array_map('absint', explode(',', $raw_ids))), 'wp_attachment_is_image'));
    update_post_meta($post_id, 'rb_business_logo_ids', $logo_ids);
}

function rb_product_meta_fields(): array
{
    return [
        'rb_descriptors' => 'Вкусовые дескрипторы',
        'rb_process' => 'Способ обработки',
        'rb_roast' => 'Степень обжарки',
        'rb_country' => 'Страна',
        'rb_region' => 'Регион',
        'rb_height' => 'Высота',
        'rb_variety' => 'Разновидность',
        'rb_grind_options' => 'Варианты помола',
        'rb_cdek_weight' => 'Вес для СДЭК, г (без значения: 240 г / 1080 г)',
        'rb_cdek_length' => 'Длина упаковки для СДЭК, см',
        'rb_cdek_width' => 'Ширина упаковки для СДЭК, см',
        'rb_cdek_height' => 'Высота упаковки для СДЭК, см',
    ];
}

function rb_product_price_fields(): array
{
    return [
        'rb_price_200' => 'Цена 200 г',
        'rb_old_price_200' => 'Старая цена 200 г',
        'rb_price_1000' => 'Цена 1 кг',
        'rb_old_price_1000' => 'Старая цена 1 кг',
        'rb_stock' => 'Остаток',
    ];
}

function rb_get_product_categories(bool $hide_empty = false): array
{
    $terms = get_terms([
        'taxonomy' => 'rb_product_category',
        'hide_empty' => $hide_empty,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (is_wp_error($terms)) {
        return [];
    }

    usort($terms, static function (WP_Term $first, WP_Term $second): int {
        $first_order = get_term_meta($first->term_id, 'rb_category_order', true);
        $second_order = get_term_meta($second->term_id, 'rb_category_order', true);
        $first_order = $first_order === '' ? PHP_INT_MAX : (int) $first_order;
        $second_order = $second_order === '' ? PHP_INT_MAX : (int) $second_order;

        return $first_order === $second_order
            ? strcasecmp($first->name, $second->name)
            : $first_order <=> $second_order;
    });

    return $terms;
}

function rb_render_product_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_product_meta', 'rb_product_meta_nonce');

    foreach (rb_product_meta_fields() as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
        echo '<textarea class="widefat" rows="3" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea></p>';
    }
}

function rb_render_product_prices_meta_box(WP_Post $post): void
{
    foreach (rb_product_price_fields() as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
        echo '<input class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"></p>';
    }
}

function rb_render_product_integration_meta_box(WP_Post $post): void
{
    foreach (rb_product_integration_fields() as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        $readonly = $key === 'rb_1c_updated_at' ? ' readonly' : '';
        $is_stock = strpos($key, 'rb_stock_') === 0;
        $type = $is_stock || $key === 'rb_wholesale_price' ? 'number' : 'text';
        $step = $key === 'rb_wholesale_price' ? ' step="0.01" min="0"' : ($is_stock ? ' step="1" min="0"' : '');
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
        echo '<input class="widefat" type="' . esc_attr($type) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . $step . $readonly . '></p>';
    }
    echo '<p class="description">Поля с идентификаторами и остатками могут обновляться импортом CommerceML.</p>';
}

function rb_get_product_recommendations(int $post_id): array
{
    $product_ids = get_post_meta($post_id, 'rb_recommended_products', true);

    return array_values(array_filter(array_unique(array_map('absint', is_array($product_ids) ? $product_ids : [])), static function (int $product_id) use ($post_id): bool {
        return $product_id !== $post_id && get_post_type($product_id) === 'rb_product';
    }));
}

function rb_render_product_recommendations_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_product_recommendations', 'rb_product_recommendations_nonce');
    $selected_products = rb_get_product_recommendations($post->ID);
    $products = get_posts([
        'post_type' => 'rb_product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'post__not_in' => [$post->ID],
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    ?>
    <p>Отметьте товары, которые нужно показать в блоке рекомендаций. Они выводятся в порядке этого списка.</p>
    <?php if ($products): ?>
        <div class="rb-product-recommendations-admin">
            <?php foreach ($products as $product): ?>
                <label>
                    <input type="checkbox" name="rb_recommended_products[]" value="<?= esc_attr($product->ID) ?>" <?= checked(in_array($product->ID, $selected_products, true), true, false) ?>>
                    <?= get_the_post_thumbnail($product, 'thumbnail') ?>
                    <span><?= esc_html($product->post_title) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Сначала добавьте другие товары.</p>
    <?php endif; ?>
    <?php
}

function rb_order_meta_fields(): array
{
    return [
        'rb_order_status' => 'Статус',
        'rb_payment_status' => 'Статус оплаты',
        'rb_order_type' => 'Тип заказа',
        'rb_payment_method' => 'Способ оплаты',
        'rb_payment_id' => 'Идентификатор платежа',
        'rb_customer_name' => 'ФИО',
        'rb_customer_phone' => 'Телефон',
        'rb_customer_email' => 'Почта',
        'rb_company_name' => 'Компания',
        'rb_company_inn' => 'ИНН компании',
        'rb_company_kpp' => 'КПП компании',
        'rb_company_address' => 'Адрес компании',
        'rb_delivery_method' => 'Доставка',
        'rb_pickup_point' => 'Точка самовывоза',
        'rb_delivery_cost' => 'Стоимость доставки',
        'rb_cdek_office_code' => 'Код ПВЗ СДЭК',
        'rb_cdek_city_code' => 'Код города СДЭК',
        'rb_cdek_tariff_code' => 'Код тарифа СДЭК',
        'rb_cdek_tariff_name' => 'Тариф СДЭК',
        'rb_cdek_delivery_period' => 'Срок доставки СДЭК',
        'rb_promocode' => 'Промокод',
        'rb_order_subtotal_amount' => 'Товары, ₽',
        'rb_discount_total_amount' => 'Скидка, ₽',
        'rb_delivery_cost_amount' => 'Доставка, ₽',
        'rb_order_total_amount' => 'Итого, ₽',
        'rb_order_total' => 'Сумма',
        'rb_order_items' => 'Позиции заказа',
    ];
}

function rb_render_order_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_order_meta', 'rb_order_meta_nonce');
    $meta = [];
    foreach (array_keys(rb_order_meta_fields()) as $key) $meta[$key] = get_post_meta($post->ID, $key, true);
    $order_type = (string) ($meta['rb_order_type'] ?: 'retail');
    $is_lead = $order_type === 'business_lead';
    $back_url = admin_url('admin.php?page=' . ($is_lead ? 'rb-price-requests' : 'rb-orders'));
    $status = (string) ($meta['rb_order_status'] ?: 'new');
    ?>
    <div class="rb-order-editor<?= $is_lead ? ' rb-order-editor--lead' : '' ?>">
        <header class="rb-order-editor__head">
            <div>
                <a class="rb-order-editor__back" href="<?= esc_url($back_url) ?>"><span class="dashicons dashicons-arrow-left-alt2"></span><?= $is_lead ? 'Все заявки на прайс' : 'Все заказы' ?></a>
                <span class="rb-orders-kicker"><?= $is_lead ? 'Оптовая заявка' : esc_html(rb_order_admin_type_label($order_type)) . ' заказ' ?></span>
                <h2><?= $is_lead ? 'Заявка на прайс-лист' : 'Заказ' ?> #<?= esc_html((string) $post->ID) ?></h2>
                <p>Создан <?= esc_html(get_the_date('d.m.Y', $post)) ?> в <?= esc_html(get_the_time('H:i', $post)) ?></p>
            </div>
            <div class="rb-order-editor__state"><?= rb_order_admin_badge($status) ?><span><?= esc_html((string) ($meta['rb_order_total'] ?: ($is_lead ? 'Запрос прайса' : 'Сумма не рассчитана'))) ?></span></div>
        </header>

        <?php if ($is_lead): ?>
            <div class="rb-lead-layout">
                <section class="rb-editor-panel rb-lead-contact">
                    <div class="rb-editor-panel__title"><div><span class="dashicons dashicons-businessperson"></span><h3>Контактное лицо</h3></div><p>Данные для первого контакта и отправки предложения.</p></div>
                    <div class="rb-editor-fields rb-editor-fields--two">
                        <label><span>ФИО</span><input name="rb_customer_name" value="<?= esc_attr((string) $meta['rb_customer_name']) ?>"></label>
                        <label><span>Компания</span><input name="rb_company_name" value="<?= esc_attr((string) $meta['rb_company_name']) ?>" placeholder="Не указана"></label>
                        <label><span>Телефон</span><input name="rb_customer_phone" type="tel" inputmode="tel" maxlength="18" data-phone-mask value="<?= esc_attr(rb_format_phone((string) $meta['rb_customer_phone'])) ?>"></label>
                        <label><span>Электронная почта</span><input name="rb_customer_email" type="email" value="<?= esc_attr((string) $meta['rb_customer_email']) ?>"></label>
                    </div>
                    <div class="rb-lead-actions">
                        <?php if ($meta['rb_customer_phone']): ?><a class="button button-primary" href="tel:<?= esc_attr(rb_phone_href((string) $meta['rb_customer_phone'])) ?>"><span class="dashicons dashicons-phone"></span>Позвонить</a><?php endif; ?>
                        <?php if ($meta['rb_customer_email']): ?><a class="button" href="mailto:<?= esc_attr((string) $meta['rb_customer_email']) ?>?subject=Прайс-лист Roastberry Coffee Roasters"><span class="dashicons dashicons-email"></span>Написать</a><?php endif; ?>
                    </div>
                </section>
                <aside class="rb-editor-panel rb-lead-status">
                    <div class="rb-editor-panel__title"><div><span class="dashicons dashicons-flag"></span><h3>Обработка заявки</h3></div></div>
                    <label class="rb-editor-field"><span>Статус</span><select name="rb_order_status"><?php foreach (rb_order_statuses() as $key => $label): ?><option value="<?= esc_attr($key) ?>" <?= selected($status, $key, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select></label>
                    <div class="rb-lead-facts"><div><span>Источник</span><strong>Форма «Запросить прайс»</strong></div><div><span>Дата заявки</span><strong><?= esc_html(get_the_date('d.m.Y H:i', $post)) ?></strong></div></div>
                    <input type="hidden" name="rb_order_type" value="business_lead"><input type="hidden" name="rb_order_total" value="<?= esc_attr((string) $meta['rb_order_total']) ?>">
                </aside>
            </div>
        <?php else: ?>
            <div class="rb-order-editor__layout">
                <main>
                    <section class="rb-editor-panel">
                        <div class="rb-editor-panel__title"><div><span class="dashicons dashicons-cart"></span><h3>Состав заказа</h3></div><p><?= count(rb_get_order_items($post->ID)) ?> позиций</p></div>
                        <?php $items = rb_get_order_items($post->ID); ?>
                        <?php if ($items): ?>
                            <div class="rb-order-items-table"><table><thead><tr><th>Товар</th><th>Вариант</th><th>Кол-во</th><th>Цена</th><th>Сумма</th></tr></thead><tbody><?php foreach ($items as $item): $product_id = absint($item['product_id'] ?? 0); ?><tr><td><div class="rb-order-product"><?= $product_id ? get_the_post_thumbnail($product_id, 'thumbnail') : '' ?><div><strong><?= esc_html((string) ($item['title'] ?? 'Товар')) ?></strong><small><?= esc_html((string) ($item['sku'] ?? '')) ?></small></div></div></td><td><?= esc_html(trim((string) ($item['size_label'] ?? '') . ', ' . (string) ($item['grind'] ?? ''), ', ')) ?></td><td><?= esc_html((string) ($item['quantity'] ?? 0)) ?></td><td><?= esc_html(rb_format_price((int) ($item['unit_price'] ?? 0))) ?></td><td><strong><?= esc_html(rb_format_price((int) ($item['line_total'] ?? 0))) ?></strong></td></tr><?php endforeach; ?></tbody></table></div>
                        <?php else: ?><p class="rb-editor-empty">В заказе нет структурированных товарных позиций.</p><?php endif; ?>
                        <input type="hidden" name="rb_order_items" value="<?= esc_attr((string) $meta['rb_order_items']) ?>">
                    </section>

                    <section class="rb-editor-panel">
                        <div class="rb-editor-panel__title"><div><span class="dashicons dashicons-admin-users"></span><h3>Покупатель</h3></div></div>
                        <div class="rb-editor-fields rb-editor-fields--three">
                            <label><span>ФИО</span><input name="rb_customer_name" value="<?= esc_attr((string) $meta['rb_customer_name']) ?>"></label>
                            <label><span>Телефон</span><input name="rb_customer_phone" type="tel" inputmode="tel" maxlength="18" data-phone-mask value="<?= esc_attr(rb_format_phone((string) $meta['rb_customer_phone'])) ?>"></label>
                            <label><span>Электронная почта</span><input name="rb_customer_email" type="email" value="<?= esc_attr((string) $meta['rb_customer_email']) ?>"></label>
                        </div>
                        <?php if ($order_type === 'business'): ?><div class="rb-editor-fields rb-editor-fields--three rb-company-fields"><label><span>Компания</span><input name="rb_company_name" value="<?= esc_attr((string) $meta['rb_company_name']) ?>"></label><label><span>ИНН</span><input name="rb_company_inn" value="<?= esc_attr((string) $meta['rb_company_inn']) ?>"></label><label><span>КПП</span><input name="rb_company_kpp" value="<?= esc_attr((string) $meta['rb_company_kpp']) ?>"></label><label class="rb-field-wide"><span>Юридический адрес</span><input name="rb_company_address" value="<?= esc_attr((string) $meta['rb_company_address']) ?>"></label></div><?php endif; ?>
                    </section>

                    <section class="rb-editor-panel">
                        <div class="rb-editor-panel__title"><div><span class="dashicons dashicons-location-alt"></span><h3>Доставка</h3></div></div>
                        <div class="rb-editor-fields rb-editor-fields--two"><label><span>Способ доставки</span><input name="rb_delivery_method" value="<?= esc_attr((string) $meta['rb_delivery_method']) ?>"></label><label><span>Адрес или пункт выдачи</span><input name="rb_pickup_point" value="<?= esc_attr((string) $meta['rb_pickup_point']) ?>"></label></div>
                        <?php if ($meta['rb_cdek_office_code']): ?><details class="rb-cdek-details"><summary>Данные отправления СДЭК</summary><div class="rb-editor-fields rb-editor-fields--three"><label><span>Код ПВЗ</span><input name="rb_cdek_office_code" value="<?= esc_attr((string) $meta['rb_cdek_office_code']) ?>"></label><label><span>Код города</span><input name="rb_cdek_city_code" value="<?= esc_attr((string) $meta['rb_cdek_city_code']) ?>"></label><label><span>Тариф</span><input name="rb_cdek_tariff_name" value="<?= esc_attr((string) $meta['rb_cdek_tariff_name']) ?>"></label><label><span>Код тарифа</span><input name="rb_cdek_tariff_code" value="<?= esc_attr((string) $meta['rb_cdek_tariff_code']) ?>"></label><label><span>Срок</span><input name="rb_cdek_delivery_period" value="<?= esc_attr((string) $meta['rb_cdek_delivery_period']) ?>"></label></div></details><?php endif; ?>
                    </section>
                </main>

                <aside>
                    <section class="rb-editor-panel rb-order-control">
                        <div class="rb-editor-panel__title"><div><span class="dashicons dashicons-controls-repeat"></span><h3>Статус</h3></div></div>
                        <label class="rb-editor-field"><span>Выполнение</span><select name="rb_order_status"><?php foreach (rb_order_statuses() as $key => $label): ?><option value="<?= esc_attr($key) ?>" <?= selected($status, $key, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select></label>
                        <label class="rb-editor-field"><span>Оплата</span><select name="rb_payment_status"><?php foreach (rb_payment_statuses() as $key => $label): ?><option value="<?= esc_attr($key) ?>" <?= selected((string) $meta['rb_payment_status'], $key, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select></label>
                        <label class="rb-editor-field"><span>Способ оплаты</span><input name="rb_payment_method" value="<?= esc_attr((string) $meta['rb_payment_method']) ?>"></label>
                        <input type="hidden" name="rb_order_type" value="<?= esc_attr($order_type) ?>"><input type="hidden" name="rb_payment_id" value="<?= esc_attr((string) $meta['rb_payment_id']) ?>">
                    </section>
                    <section class="rb-editor-panel rb-order-summary">
                        <div class="rb-editor-panel__title"><div><span class="dashicons dashicons-money-alt"></span><h3>Итог</h3></div></div>
                        <label><span>Товары</span><input type="number" name="rb_order_subtotal_amount" value="<?= esc_attr((string) $meta['rb_order_subtotal_amount']) ?>"><b>₽</b></label>
                        <label><span>Скидка</span><input type="number" name="rb_discount_total_amount" value="<?= esc_attr((string) $meta['rb_discount_total_amount']) ?>"><b>₽</b></label>
                        <label><span>Доставка</span><input type="number" name="rb_delivery_cost_amount" value="<?= esc_attr((string) $meta['rb_delivery_cost_amount']) ?>"><b>₽</b></label>
                        <div class="rb-order-summary__total"><span>Итого</span><strong><?= esc_html((string) $meta['rb_order_total']) ?></strong></div>
                        <input type="hidden" name="rb_order_total_amount" value="<?= esc_attr((string) $meta['rb_order_total_amount']) ?>"><input type="hidden" name="rb_order_total" value="<?= esc_attr((string) $meta['rb_order_total']) ?>"><input type="hidden" name="rb_delivery_cost" value="<?= esc_attr((string) $meta['rb_delivery_cost']) ?>"><input type="hidden" name="rb_promocode" value="<?= esc_attr((string) $meta['rb_promocode']) ?>">
                    </section>
                    <?php $acceptance = get_post_meta($post->ID, 'rb_legal_acceptance', true); if (is_array($acceptance) && $acceptance): ?><section class="rb-editor-panel rb-order-legal"><span class="dashicons dashicons-shield"></span><div><strong>Согласия зафиксированы</strong><p><?= esc_html((string) ($acceptance['accepted_at_utc'] ?? '')) ?><br>Версия <?= esc_html((string) ($acceptance['document_version'] ?? '')) ?></p></div></section><?php endif; ?>
                </aside>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function rb_training_meta_fields(): array
{
    return [
        'rb_training_duration' => 'Продолжительность',
        'rb_training_price' => 'Стоимость',
        'rb_training_points' => 'Пункты программы',
        'rb_training_link' => 'Ссылка на тренера',
    ];
}

function rb_render_training_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_training_meta', 'rb_training_meta_nonce');

    foreach (rb_training_meta_fields() as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';

        if ($key === 'rb_training_points') {
            echo '<textarea class="widefat" rows="7" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea>';
            echo '<span class="description">Каждый пункт с новой строки.</span>';
        } else {
            echo '<input class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }

        echo '</p>';
    }
}

function rb_brew_method_icons(): array
{
    return [
        'espresso' => 'Эспрессо',
        'cezve' => 'Турка',
        'moka' => 'Гейзерная кофеварка',
        'dripper' => 'Воронка',
        'aeropress' => 'Аэропресс',
    ];
}

function rb_render_brew_method_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_brew_method_meta', 'rb_brew_method_meta_nonce');

    $selected_icon = (string) get_post_meta($post->ID, 'rb_brew_icon', true);
    $selected_products = array_map('intval', (array) get_post_meta($post->ID, 'rb_brew_products', true));
    $products = get_posts([
        'post_type' => 'rb_product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    ?>
    <p>
        <label for="rb_brew_icon"><strong>Иконка на главной</strong></label>
        <select class="widefat" id="rb_brew_icon" name="rb_brew_icon">
            <?php foreach (rb_brew_method_icons() as $value => $label): ?>
                <option value="<?= esc_attr($value) ?>" <?= selected($selected_icon, $value, false) ?>><?= esc_html($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p><strong>Рекомендуемые лоты</strong></p>
    <?php if ($products): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px 18px;max-height:320px;overflow:auto;padding:12px;border:1px solid #dcdcde;background:#fff;">
            <?php foreach ($products as $product): ?>
                <label>
                    <input type="checkbox" name="rb_brew_products[]" value="<?= esc_attr($product->ID) ?>" <?= checked(in_array($product->ID, $selected_products, true), true, false) ?>>
                    <?= esc_html($product->post_title) ?>
                </label>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Сначала добавьте товары в каталог.</p>
    <?php endif; ?>
    <p class="description">Главное изображение задается в блоке «Изображение записи». Дополнительные изображения можно добавить в текст через редактор.</p>
    <?php
}

add_action('save_post_rb_product', 'rb_save_product_meta');
function rb_save_product_meta(int $post_id): void
{
    if (!isset($_POST['rb_product_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_product_meta_nonce'])), 'rb_save_product_meta')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (array_keys(rb_product_meta_fields()) as $key) {
        $value = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
        update_post_meta($post_id, $key, $value);
    }

    foreach (array_keys(rb_product_price_fields()) as $key) {
        $value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
        update_post_meta($post_id, $key, $value);
    }

    foreach (array_keys(rb_product_integration_fields()) as $key) {
        if ($key === 'rb_1c_updated_at' || !isset($_POST[$key])) {
            continue;
        }
        $value = sanitize_text_field(wp_unslash($_POST[$key]));
        if (strpos($key, 'rb_stock_') === 0) {
            $value = $value === '' ? '' : (string) max(0, (int) $value);
        }
        update_post_meta($post_id, $key, $value);
    }
}

add_action('save_post_rb_product', 'rb_save_product_recommendations');
function rb_save_product_recommendations(int $post_id): void
{
    if (!isset($_POST['rb_product_recommendations_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_product_recommendations_nonce'])), 'rb_save_product_recommendations')) {
        return;
    }

    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $product_ids = isset($_POST['rb_recommended_products']) ? array_map('absint', (array) wp_unslash($_POST['rb_recommended_products'])) : [];
    $product_ids = array_values(array_filter(array_unique($product_ids), static function (int $product_id) use ($post_id): bool {
        return $product_id !== $post_id && get_post_type($product_id) === 'rb_product';
    }));
    update_post_meta($post_id, 'rb_recommended_products', $product_ids);
}

add_action('admin_enqueue_scripts', 'rb_enqueue_product_recommendations_admin_style');
function rb_enqueue_product_recommendations_admin_style(): void
{
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'rb_product') {
        return;
    }

    wp_enqueue_style('rb-product-recommendations-admin', rb_asset_url('css/admin-product-recommendations.css'), [], rb_asset_version('css/admin-product-recommendations.css'));
}

add_action('save_post_rb_order', 'rb_save_order_meta');
function rb_save_order_meta(int $post_id): void
{
    if (!isset($_POST['rb_order_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_order_meta_nonce'])), 'rb_save_order_meta')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $previous_status = (string) get_post_meta($post_id, 'rb_order_status', true);
    foreach (rb_order_meta_fields() as $key => $label) {
        if (!isset($_POST[$key])) {
            continue;
        }
        $value = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
        if ($key === 'rb_order_status' && !array_key_exists($value, rb_order_statuses())) {
            continue;
        }
        if ($key === 'rb_payment_status' && !array_key_exists($value, rb_payment_statuses())) {
            continue;
        }
        if ($key === 'rb_customer_phone') {
            if ($value !== '' && !rb_is_valid_ru_phone($value)) {
                continue;
            }
            $value = rb_format_phone($value);
        }
        update_post_meta($post_id, $key, $value);
    }

    if (isset($_POST['rb_order_subtotal_amount'], $_POST['rb_discount_total_amount'], $_POST['rb_delivery_cost_amount'])) {
        $subtotal = max(0, (int) $_POST['rb_order_subtotal_amount']);
        $discount = max(0, (int) $_POST['rb_discount_total_amount']);
        $delivery = max(0, (int) $_POST['rb_delivery_cost_amount']);
        $total = max(0, $subtotal - $discount + $delivery);
        update_post_meta($post_id, 'rb_order_subtotal_amount', $subtotal);
        update_post_meta($post_id, 'rb_discount_total_amount', $discount);
        update_post_meta($post_id, 'rb_delivery_cost_amount', $delivery);
        update_post_meta($post_id, 'rb_delivery_cost', rb_format_price($delivery));
        update_post_meta($post_id, 'rb_order_total_amount', $total);
        update_post_meta($post_id, 'rb_order_total', rb_format_price($total));
    }

    $current_status = (string) get_post_meta($post_id, 'rb_order_status', true);
    if ($current_status === 'canceled' && $previous_status !== 'canceled') {
        rb_release_order_stock($post_id);
    }
}

add_filter('manage_rb_product_posts_columns', 'rb_product_admin_columns');
function rb_product_admin_columns(array $columns): array
{
    $result = [];
    foreach ($columns as $key => $label) {
        $result[$key] = $label;
        if ($key === 'title') {
            $result['rb_sku'] = 'Артикул';
            $result['rb_prices'] = 'Цены';
            $result['rb_stock'] = 'Остатки';
        }
    }
    return $result;
}

add_action('manage_rb_product_posts_custom_column', 'rb_product_admin_column_content', 10, 2);
function rb_product_admin_column_content(string $column, int $post_id): void
{
    if ($column === 'rb_sku') {
        echo esc_html((string) (get_post_meta($post_id, 'rb_sku', true) ?: 'RB-' . $post_id));
    } elseif ($column === 'rb_prices') {
        echo '<strong>200 г:</strong> ' . esc_html(rb_format_price(rb_product_price_by_size($post_id, '200')));
        echo '<br><strong>1 кг:</strong> ' . esc_html(rb_format_price(rb_product_price_by_size($post_id, '1000')));
    } elseif ($column === 'rb_stock') {
        $stock_200 = rb_product_stock_by_size($post_id, '200');
        $stock_1000 = rb_product_stock_by_size($post_id, '1000');
        if ($stock_200 === null && $stock_1000 === null) {
            echo '<span aria-label="Остатки не ведутся">—</span>';
        } else {
            echo '<strong>200 г:</strong> ' . esc_html((string) (int) $stock_200);
            echo '<br><strong>1 кг:</strong> ' . esc_html((string) (int) $stock_1000);
        }
    }
}

add_filter('manage_rb_order_posts_columns', 'rb_order_admin_columns');
function rb_order_admin_columns(array $columns): array
{
    return [
        'cb' => $columns['cb'] ?? '<input type="checkbox">',
        'title' => 'Заказ',
        'rb_customer' => 'Покупатель',
        'rb_order_status_column' => 'Статус',
        'rb_payment_status_column' => 'Оплата',
        'rb_order_total_column' => 'Сумма',
        'date' => 'Дата',
    ];
}

add_action('manage_rb_order_posts_custom_column', 'rb_order_admin_column_content', 10, 2);
function rb_order_admin_column_content(string $column, int $post_id): void
{
    if ($column === 'rb_customer') {
        echo '<strong>' . esc_html((string) get_post_meta($post_id, 'rb_customer_name', true)) . '</strong>';
        echo '<br><a href="' . esc_url('tel:' . rb_phone_href((string) get_post_meta($post_id, 'rb_customer_phone', true))) . '">' . esc_html((string) get_post_meta($post_id, 'rb_customer_phone', true)) . '</a>';
    } elseif ($column === 'rb_order_status_column') {
        $status = (string) get_post_meta($post_id, 'rb_order_status', true);
        echo esc_html(rb_order_statuses()[$status] ?? ($status ?: 'Новый'));
    } elseif ($column === 'rb_payment_status_column') {
        $status = (string) get_post_meta($post_id, 'rb_payment_status', true);
        echo esc_html(rb_payment_statuses()[$status] ?? ($status ?: '—'));
    } elseif ($column === 'rb_order_total_column') {
        $amount = get_post_meta($post_id, 'rb_order_total_amount', true);
        echo '<strong>' . esc_html($amount !== '' ? rb_format_price((int) $amount) : (string) get_post_meta($post_id, 'rb_order_total', true)) . '</strong>';
    }
}

add_action('admin_enqueue_scripts', 'rb_enqueue_phone_mask_admin_asset');
function rb_enqueue_phone_mask_admin_asset(): void
{
    $screen = get_current_screen();
    if (!$screen) {
        return;
    }

    $is_user_screen = in_array($screen->base, ['profile', 'user-edit'], true);
    $is_order_screen = $screen->base === 'post' && $screen->post_type === 'rb_order';
    $is_contact_settings = isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === 'rb-site-contacts';
    if (!$is_user_screen && !$is_order_screen && !$is_contact_settings) {
        return;
    }

    wp_enqueue_script('rb-phone-mask', rb_asset_url('js/phone-mask.js'), [], rb_asset_version('js/phone-mask.js'), true);
}

add_action('save_post_rb_training', 'rb_save_training_meta');
function rb_save_training_meta(int $post_id): void
{
    if (!isset($_POST['rb_training_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_training_meta_nonce'])), 'rb_save_training_meta')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (rb_training_meta_fields() as $key => $label) {
        $value = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
        update_post_meta($post_id, $key, $value);
    }
}

add_action('save_post_rb_brew_method', 'rb_save_brew_method_meta');
function rb_save_brew_method_meta(int $post_id): void
{
    if (!isset($_POST['rb_brew_method_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_brew_method_meta_nonce'])), 'rb_save_brew_method_meta')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $icon = isset($_POST['rb_brew_icon']) ? sanitize_key(wp_unslash($_POST['rb_brew_icon'])) : 'espresso';
    if (!array_key_exists($icon, rb_brew_method_icons())) {
        $icon = 'espresso';
    }

    $products = isset($_POST['rb_brew_products']) ? array_map('absint', (array) wp_unslash($_POST['rb_brew_products'])) : [];
    $products = array_values(array_filter(array_unique($products), static fn (int $product_id): bool => get_post_type($product_id) === 'rb_product'));

    update_post_meta($post_id, 'rb_brew_icon', $icon);
    update_post_meta($post_id, 'rb_brew_products', $products);
}

add_action('init', 'rb_seed_brew_methods', 30);
function rb_seed_brew_methods(): void
{
    if (get_option('rb_brew_methods_seed_version') === '1') {
        return;
    }

    $methods = [
        ['title' => 'Эспрессо', 'slug' => 'espresso', 'icon' => 'espresso', 'image' => 'IMG_0972_1.jpg'],
        ['title' => 'Турка', 'slug' => 'turka', 'icon' => 'cezve', 'image' => '3_1.webp'],
        ['title' => 'Гейзерная кофеварка', 'slug' => 'geysernaya-kofevarka', 'icon' => 'moka', 'image' => '4.webp'],
        ['title' => 'Воронка', 'slug' => 'voronka', 'icon' => 'dripper', 'image' => 'IMG_1463_1.jpg'],
        ['title' => 'Аэропресс', 'slug' => 'aeropress', 'icon' => 'aeropress', 'image' => 'IMG_1352_1.jpg'],
    ];

    foreach ($methods as $index => $method) {
        $existing = get_page_by_path($method['slug'], OBJECT, 'rb_brew_method');
        $post_id = $existing instanceof WP_Post ? $existing->ID : wp_insert_post([
            'post_type' => 'rb_brew_method',
            'post_status' => 'publish',
            'post_title' => $method['title'],
            'post_name' => $method['slug'],
            'menu_order' => $index,
        ]);

        if (!is_wp_error($post_id) && $post_id) {
            add_post_meta((int) $post_id, 'rb_brew_icon', $method['icon'], true);
            add_post_meta((int) $post_id, 'rb_brew_default_image', $method['image'], true);
        }
    }

    update_option('rb_brew_methods_seed_version', '1');
    flush_rewrite_rules(false);
}

function rb_get_brew_method_image(int $post_id, string $size = 'large'): string
{
    $thumbnail = get_the_post_thumbnail_url($post_id, $size);
    if ($thumbnail) {
        return $thumbnail;
    }

    $default_image = sanitize_file_name((string) get_post_meta($post_id, 'rb_brew_default_image', true));

    return rb_asset_url('img/' . ($default_image ?: 'IMG_1463_1.jpg'));
}

add_filter('manage_rb_product_posts_columns', 'rb_product_columns');
function rb_product_columns(array $columns): array
{
    $columns['rb_price_200'] = 'Цена 200 г';
    $columns['rb_stock'] = 'Остаток';

    return $columns;
}

add_action('manage_rb_product_posts_custom_column', 'rb_product_column_content', 10, 2);
function rb_product_column_content(string $column, int $post_id): void
{
    if (in_array($column, ['rb_price_200', 'rb_stock'], true)) {
        echo esc_html((string) get_post_meta($post_id, $column, true));
    }
}

add_filter('manage_rb_order_posts_columns', 'rb_order_columns');
function rb_order_columns(array $columns): array
{
    $columns['rb_customer_phone'] = 'Телефон';
    $columns['rb_order_status'] = 'Статус';
    $columns['rb_order_total'] = 'Сумма';

    return $columns;
}

add_action('manage_rb_order_posts_custom_column', 'rb_order_column_content', 10, 2);
function rb_order_column_content(string $column, int $post_id): void
{
    if (in_array($column, ['rb_customer_phone', 'rb_order_status', 'rb_order_total'], true)) {
        $value = (string) get_post_meta($post_id, $column, true);
        echo esc_html($column === 'rb_customer_phone' ? rb_format_phone($value) : $value);
    }
}

add_action('init', 'rb_handle_front_forms');
function rb_handle_front_forms(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['rb_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['rb_action']));

    if ($action === 'register') {
        rb_handle_registration();
    }

    if ($action === 'login') {
        rb_handle_login();
    }

    if ($action === 'verify_auth_code') {
        rb_handle_auth_code_verification();
    }

    if ($action === 'profile' && is_user_logged_in()) {
        rb_handle_profile_update();
    }

    if ($action === 'add_to_cart') {
        rb_handle_add_to_cart();
    }

    if ($action === 'update_cart') {
        rb_handle_update_cart();
    }

    if ($action === 'order') {
        rb_handle_order_create();
    }
}

function rb_handle_add_to_cart(): void
{
    if (!isset($_POST['rb_cart_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_cart_nonce'])), 'rb_add_to_cart')) {
        if (rb_is_cart_ajax_request()) {
            wp_send_json_error(['message' => 'Обновите страницу и попробуйте снова.'], 403);
        }
        return;
    }

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if (!$product_id || get_post_type($product_id) !== 'rb_product') {
        if (rb_is_cart_ajax_request()) {
            wp_send_json_error(['message' => 'Товар не найден.'], 404);
        }
        wp_safe_redirect(route_url('catalog'));
        exit;
    }

    $size = isset($_POST['size']) && $_POST['size'] === '1000' ? '1000' : '200';
    $grind = isset($_POST['grind']) ? sanitize_text_field(wp_unslash($_POST['grind'])) : 'В зернах';
    $quantity = isset($_POST['quantity']) ? max(1, absint($_POST['quantity'])) : 1;
    $price = rb_product_price_by_size($product_id, $size);
    $key = rb_cart_item_key($product_id, $size, $grind);
    $cart = rb_get_cart();

    if ($price <= 0) {
        if (rb_is_cart_ajax_request()) {
            wp_send_json_error(['message' => 'Для выбранного товара пока не указана цена.'], 409);
        }
        rb_set_checkout_error('Для выбранного товара пока не указана цена. Обратитесь к менеджеру.');
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    $stock = rb_product_stock_by_size($product_id, $size);
    $requested_quantity = $quantity + (isset($cart[$key]) ? max(0, (int) ($cart[$key]['quantity'] ?? 0)) : 0);
    if ($stock !== null && $requested_quantity > $stock) {
        $stock_message = $stock > 0
            ? sprintf('В выбранной фасовке доступно только %d шт.', $stock)
            : 'Выбранная фасовка закончилась.';
        if (rb_is_cart_ajax_request()) {
            wp_send_json_error(['message' => $stock_message], 409);
        }
        rb_set_checkout_error($stock_message);
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    if (isset($cart[$key])) {
        $cart[$key]['quantity'] += $quantity;
    } else {
        $cart[$key] = [
            'product_id' => $product_id,
            'title' => get_the_title($product_id),
            'url' => get_permalink($product_id),
            'image' => get_the_post_thumbnail_url($product_id, 'thumbnail') ?: rb_asset_url('img/1.webp'),
            'size_code' => $size,
            'size' => $size === '1000' ? '1 кг' : '200 г',
            'grind' => $grind,
            'quantity' => $quantity,
            'price' => $price,
        ];
    }

    rb_save_cart($cart);
    if (rb_is_cart_ajax_request()) {
        wp_send_json_success(array_merge(rb_cart_widget_data(), [
            'message' => 'Товар добавлен в корзину',
            'added_title' => get_the_title($product_id),
        ]));
    }
    wp_safe_redirect(add_query_arg('added', '1', route_url('cart')));
    exit;
}

function rb_handle_update_cart(): void
{
    if (!isset($_POST['rb_cart_update_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_cart_update_nonce'])), 'rb_update_cart')) {
        return;
    }

    $cart = rb_get_cart();
    $quantities = isset($_POST['quantity']) && is_array($_POST['quantity']) ? wp_unslash($_POST['quantity']) : [];
    $remove_key = isset($_POST['remove_key']) ? sanitize_text_field(wp_unslash($_POST['remove_key'])) : '';

    foreach ($quantities as $key => $quantity) {
        $key = sanitize_text_field((string) $key);
        $quantity = max(0, absint($quantity));

        if (!isset($cart[$key])) {
            continue;
        }

        if ($quantity === 0 || $key === $remove_key) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity'] = $quantity;
        }
    }

    if ($remove_key && isset($cart[$remove_key])) {
        unset($cart[$remove_key]);
    }

    $refresh_result = rb_refresh_cart_from_catalog($cart, true);
    if (is_wp_error($refresh_result)) {
        if (!empty($_POST['rb_cart_ajax'])) {
            wp_send_json_error(['message' => $refresh_result->get_error_message()], 409);
        }
        rb_set_checkout_error($refresh_result->get_error_message());
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    $cart = $refresh_result['cart'];
    rb_save_cart($cart);
    $notices = $refresh_result['notices'];

    if (!empty($_POST['rb_cart_ajax'])) {
        wp_send_json_success([
            'cart_total' => rb_cart_total(),
            'cart_count' => rb_cart_count(),
            'notice' => implode(' ', $notices),
            'items' => array_map(static function (array $item): array {
                return [
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['price'],
                ];
            }, $cart),
            'mini_cart' => rb_cart_widget_data(),
        ]);
    }

    wp_safe_redirect(route_url('cart'));
    exit;
}

function rb_handle_registration(): void
{
    if (!isset($_POST['rb_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_register_nonce'])), 'rb_register')) {
        return;
    }

    if (!rb_validate_legal_consents()) {
        wp_safe_redirect(add_query_arg('auth', 'consent', route_url('account')) . '#register');
        exit;
    }

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $phone_raw = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $phone = rb_format_phone($phone_raw);
    $name = isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '';
    if (!$email || email_exists($email)) {
        wp_safe_redirect(add_query_arg('auth', 'exists', wp_get_referer() ?: route_url('account')));
        exit;
    }

    if (!rb_is_valid_ru_phone($phone_raw)) {
        wp_safe_redirect(add_query_arg('auth', 'phone', route_url('account')) . '#register');
        exit;
    }

    $sent = rb_auth_send_code($email, 'retail_register', [
        'email' => $email,
        'phone' => $phone,
        'name' => $name,
        'legal_acceptance' => rb_legal_acceptance_data('retail_registration'),
    ]);
    $status = is_wp_error($sent) ? ($sent->get_error_code() === 'rb_auth_rate' ? 'code_rate' : 'mail') : 'code';
    wp_safe_redirect(add_query_arg('auth', $status, route_url('account')) . '#auth-code');
    exit;
}

function rb_complete_retail_registration(array $payload)
{
    $email = sanitize_email((string) ($payload['email'] ?? ''));
    if (!$email || email_exists($email)) {
        return new WP_Error('rb_registration_exists', 'Пользователь с такой почтой уже существует.');
    }

    $name = sanitize_text_field((string) ($payload['name'] ?? ''));
    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'display_name' => $name ?: $email,
        'first_name' => $name,
        'user_pass' => wp_generate_password(32, true, true),
        'role' => get_role('rb_retail_customer') ? 'rb_retail_customer' : 'subscriber',
    ]);
    if (!is_wp_error($user_id)) {
        update_user_meta((int) $user_id, 'rb_phone', rb_format_phone((string) ($payload['phone'] ?? '')));
        rb_record_user_legal_acceptance((int) $user_id, (array) ($payload['legal_acceptance'] ?? []));
    }
    return $user_id;
}

function rb_handle_login(): void
{
    if (!isset($_POST['rb_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_login_nonce'])), 'rb_login')) {
        return;
    }

    if (!rb_validate_legal_consents()) {
        wp_safe_redirect(add_query_arg('auth', 'consent', route_url('account')) . '#login');
        exit;
    }

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $user = $email ? get_user_by('email', $email) : false;
    if (!$user instanceof WP_User) {
        wp_safe_redirect(add_query_arg('auth', 'failed', route_url('account')) . '#login');
        exit;
    }

    $sent = rb_auth_send_code($email, 'retail_login', ['user_id' => $user->ID, 'legal_acceptance' => rb_legal_acceptance_data('retail_login')]);
    $status = is_wp_error($sent) ? ($sent->get_error_code() === 'rb_auth_rate' ? 'code_rate' : 'mail') : 'code';
    wp_safe_redirect(add_query_arg('auth', $status, route_url('account')) . '#auth-code');
    exit;
}

function rb_handle_profile_update(): void
{
    if (!isset($_POST['rb_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_profile_nonce'])), 'rb_profile')) {
        return;
    }

    if (!rb_validate_legal_consents()) {
        wp_safe_redirect(add_query_arg('profile', 'consent', route_url('account')) . '#profile');
        exit;
    }

    $phone_raw = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    if (!rb_is_valid_ru_phone($phone_raw)) {
        wp_safe_redirect(add_query_arg('profile', 'phone', route_url('account')) . '#profile');
        exit;
    }

    $user_id = get_current_user_id();
    wp_update_user([
        'ID' => $user_id,
        'display_name' => isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '',
        'user_email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
    ]);

    update_user_meta($user_id, 'rb_phone', rb_format_phone($phone_raw));
    rb_record_user_legal_acceptance($user_id, rb_legal_acceptance_data('retail_profile'));

    wp_safe_redirect(route_url('account'));
    exit;
}

function rb_handle_order_create(): void
{
    if (!isset($_POST['rb_order_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_order_nonce'])), 'rb_order')) {
        return;
    }

    if (!rb_get_cart()) {
        rb_set_checkout_error('Корзина пуста. Добавьте товары перед оформлением заказа.');
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    if (!rb_validate_legal_consents(true)) {
        rb_set_checkout_error('Подтвердите согласие на обработку данных, ознакомление с политикой и принятие оферты.');
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    $refresh_result = rb_refresh_cart_from_catalog(rb_get_cart(), false);
    if (is_wp_error($refresh_result)) {
        rb_set_checkout_error($refresh_result->get_error_message());
        wp_safe_redirect(route_url('cart'));
        exit;
    }
    rb_save_cart($refresh_result['cart']);

    $customer_phone_raw = isset($_POST['rb_customer_phone']) ? sanitize_text_field(wp_unslash($_POST['rb_customer_phone'])) : '';
    if (!rb_is_valid_ru_phone($customer_phone_raw)) {
        rb_set_checkout_error('Введите корректный российский номер телефона в формате +7 (999) 123-45-67.');
        wp_safe_redirect(route_url('cart'));
        exit;
    }
    $customer_phone = rb_format_phone($customer_phone_raw);

    $customer_email = isset($_POST['rb_customer_email']) ? sanitize_email(wp_unslash($_POST['rb_customer_email'])) : '';
    if ($customer_email === '' || !is_email($customer_email)) {
        rb_set_checkout_error('Введите корректный адрес электронной почты.');
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    $delivery_method = isset($_POST['rb_delivery_method']) ? sanitize_key(wp_unslash($_POST['rb_delivery_method'])) : 'pickup_cafe';
    $delivery_cost = 0;
    $pickup_point = '';
    $cdek_data = null;

    if ($delivery_method === 'cdek') {
        $cdek_data = rb_cdek_validate_order(wp_unslash($_POST));
        if (is_wp_error($cdek_data)) {
            rb_set_checkout_error($cdek_data->get_error_message());
            wp_safe_redirect(route_url('cart'));
            exit;
        }

        $delivery_cost = (int) $cdek_data['cost'];
        $pickup_point = trim($cdek_data['city'] . ', ' . $cdek_data['office_address'], ', ');
    } elseif ($delivery_method === 'pickup_production') {
        $pickup_point = 'Пермь, ул. Деревообделочная, 8к6';
    } else {
        $delivery_method = 'pickup_cafe';
        $pickup_point = isset($_POST['rb_cafe_pickup_point']) ? sanitize_text_field(wp_unslash($_POST['rb_cafe_pickup_point'])) : '';
        $cafe_points = ['Революции, 24', 'Ленина, 68'];
        if (!in_array($pickup_point, $cafe_points, true)) {
            rb_set_checkout_error('Выберите доступную кофейню для самовывоза.');
            wp_safe_redirect(route_url('cart'));
            exit;
        }
    }

    $order_items = rb_cart_snapshot();
    if (!$order_items) {
        rb_set_checkout_error('Не удалось подготовить товары к оформлению. Обновите корзину и попробуйте еще раз.');
        wp_safe_redirect(route_url('cart'));
        exit;
    }
    $order_subtotal = rb_order_items_subtotal($order_items);
    $promocode = isset($_POST['rb_promocode']) ? sanitize_text_field(wp_unslash($_POST['rb_promocode'])) : '';
    $discount_data = rb_calculate_order_discounts($order_subtotal, $delivery_method, $promocode, get_current_user_id());
    if (is_wp_error($discount_data)) {
        rb_set_checkout_error($discount_data->get_error_message());
        wp_safe_redirect(route_url('cart'));
        exit;
    }
    $discount_total = (int) $discount_data['total'];
    $order_total = $order_subtotal - $discount_total + $delivery_cost;

    $order_id = wp_insert_post([
        'post_type' => 'rb_order',
        'post_status' => 'publish',
        'post_title' => 'Заказ от ' . current_time('d.m.Y H:i'),
        'post_author' => get_current_user_id(),
    ]);

    if (!$order_id || is_wp_error($order_id)) {
        rb_set_checkout_error('Не удалось создать заказ. Попробуйте еще раз.');
        wp_safe_redirect(route_url('cart'));
        exit;
    }

    if ($order_id && !is_wp_error($order_id)) {
        $requested_payment_method = isset($_POST['rb_payment_method'])
            ? sanitize_key(wp_unslash($_POST['rb_payment_method']))
            : 'manager';
        $use_yookassa = $requested_payment_method === 'yookassa' && rb_yookassa_is_configured() && $order_total > 0;
        update_post_meta($order_id, 'rb_order_status', $use_yookassa ? 'awaiting_payment' : ($order_total === 0 ? 'processing' : 'new'));
        update_post_meta($order_id, 'rb_payment_status', $use_yookassa ? 'pending' : 'not_required');
        update_post_meta($order_id, 'rb_order_type', 'retail');
        update_post_meta($order_id, 'rb_payment_method', $use_yookassa ? 'yookassa' : 'manager');
        update_post_meta($order_id, 'rb_order_items_data', $order_items);
        update_post_meta($order_id, 'rb_order_items', rb_order_items_text($order_items));
        update_post_meta($order_id, 'rb_order_subtotal_amount', $order_subtotal);
        update_post_meta($order_id, 'rb_discount_total_amount', $discount_total);
        update_post_meta($order_id, 'rb_discount_breakdown', $discount_data);
        update_post_meta($order_id, 'rb_promocode_id', (int) $discount_data['promocode_id']);
        update_post_meta($order_id, 'rb_delivery_cost_amount', $delivery_cost);
        update_post_meta($order_id, 'rb_order_total_amount', $order_total);
        update_post_meta($order_id, 'rb_order_total', rb_format_price($order_total));
        $delivery_labels = [
            'cdek' => 'СДЭК до пункта выдачи',
            'pickup_cafe' => 'Самовывоз из кофейни',
            'pickup_production' => 'Самовывоз с производства',
        ];
        update_post_meta($order_id, 'rb_delivery_method', $delivery_labels[$delivery_method]);
        update_post_meta($order_id, 'rb_pickup_point', $pickup_point);
        update_post_meta($order_id, 'rb_delivery_cost', rb_format_price($delivery_cost));
        rb_record_order_legal_acceptance((int) $order_id, 'retail_checkout');

        if (is_array($cdek_data)) {
            update_post_meta($order_id, 'rb_cdek_office_code', $cdek_data['office_code']);
            update_post_meta($order_id, 'rb_cdek_city_code', $cdek_data['city_code']);
            update_post_meta($order_id, 'rb_cdek_tariff_code', $cdek_data['tariff_code']);
            update_post_meta($order_id, 'rb_cdek_tariff_name', $cdek_data['tariff_name']);
            $period = $cdek_data['period_min'] === $cdek_data['period_max']
                ? $cdek_data['period_min'] . ' дн.'
                : $cdek_data['period_min'] . '–' . $cdek_data['period_max'] . ' дн.';
            update_post_meta($order_id, 'rb_cdek_delivery_period', $period);
        }

        foreach (rb_order_meta_fields() as $key => $label) {
            if (in_array($key, [
                'rb_order_status',
                'rb_payment_status',
                'rb_order_type',
                'rb_payment_method',
                'rb_payment_id',
                'rb_order_items',
                'rb_order_total',
                'rb_order_subtotal_amount',
                'rb_discount_total_amount',
                'rb_delivery_cost_amount',
                'rb_order_total_amount',
                'rb_delivery_method',
                'rb_pickup_point',
                'rb_delivery_cost',
                'rb_cdek_office_code',
                'rb_cdek_city_code',
                'rb_cdek_tariff_code',
                'rb_cdek_tariff_name',
                'rb_cdek_delivery_period',
            ], true)) {
                continue;
            }

            $value = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
            if ($key === 'rb_customer_phone') {
                $value = $customer_phone;
            } elseif ($key === 'rb_customer_email') {
                $value = $customer_email;
            }
            update_post_meta($order_id, $key, $value);
        }

        $reservation = rb_reserve_order_stock($order_id);
        if (is_wp_error($reservation)) {
            update_post_meta($order_id, 'rb_order_status', 'canceled');
            update_post_meta($order_id, 'rb_payment_status', 'canceled');
            rb_set_checkout_error($reservation->get_error_message());
            wp_safe_redirect(route_url('cart'));
            exit;
        }

        if ($use_yookassa) {
            $confirmation_url = rb_yookassa_create_payment($order_id);
            if (is_wp_error($confirmation_url)) {
                update_post_meta($order_id, 'rb_order_status', 'canceled');
                update_post_meta($order_id, 'rb_payment_status', 'canceled');
                update_post_meta($order_id, 'rb_payment_error', $confirmation_url->get_error_message());
                rb_release_order_stock($order_id);
                rb_set_checkout_error('Не удалось перейти к онлайн-оплате: ' . $confirmation_url->get_error_message());
                wp_safe_redirect(route_url('cart'));
                exit;
            }

            rb_notify_managers_order($order_id, 'new');
            rb_notify_customer_order($order_id, 'new');
            rb_save_cart([]);
            wp_redirect(esc_url_raw($confirmation_url));
            exit;
        }

        rb_notify_managers_order($order_id, 'new');
        rb_notify_customer_order($order_id, 'new');
        rb_save_cart([]);
    }

    wp_safe_redirect(add_query_arg('order', 'created', route_url('cart')));
    exit;
}

add_action('show_user_profile', 'rb_render_user_profile_fields');
add_action('edit_user_profile', 'rb_render_user_profile_fields');
function rb_render_user_profile_fields(WP_User $user): void
{
    ?>
    <h2>Roastberry Coffee Roasters</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="rb_phone">Телефон</label></th>
            <td><input type="tel" inputmode="tel" autocomplete="tel" maxlength="18" data-phone-mask pattern="<?= esc_attr(rb_phone_input_pattern()) ?>" title="<?= esc_attr(rb_phone_input_title()) ?>" name="rb_phone" id="rb_phone" value="<?= esc_attr(rb_format_phone((string) get_user_meta($user->ID, 'rb_phone', true))) ?>" placeholder="+7 (___) ___-__-__" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="rb_company">Компания / ИНН</label></th>
            <td><input type="text" name="rb_company" id="rb_company" value="<?= esc_attr(get_user_meta($user->ID, 'rb_company', true)) ?>" class="regular-text"></td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'rb_save_user_profile_fields');
add_action('edit_user_profile_update', 'rb_save_user_profile_fields');
function rb_save_user_profile_fields(int $user_id): void
{
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    $phone = isset($_POST['rb_phone']) ? sanitize_text_field(wp_unslash($_POST['rb_phone'])) : '';
    if ($phone === '' || rb_is_valid_ru_phone($phone)) {
        update_user_meta($user_id, 'rb_phone', rb_format_phone($phone));
    }
    update_user_meta($user_id, 'rb_company', isset($_POST['rb_company']) ? sanitize_text_field(wp_unslash($_POST['rb_company'])) : '');
}

add_action('user_profile_update_errors', 'rb_validate_user_profile_phone', 10, 3);
function rb_validate_user_profile_phone(WP_Error $errors, bool $update, stdClass $user): void
{
    if (!isset($_POST['rb_phone'])) {
        return;
    }

    $phone = sanitize_text_field(wp_unslash($_POST['rb_phone']));
    if ($phone !== '' && !rb_is_valid_ru_phone($phone)) {
        $errors->add('rb_invalid_phone', rb_phone_input_title());
    }
}

function rb_get_user_orders(int $user_id): WP_Query
{
    return new WP_Query([
        'post_type' => 'rb_order',
        'post_status' => 'any',
        'author' => $user_id,
        'posts_per_page' => 20,
    ]);
}

function rb_get_product_meta(int $post_id): array
{
    $keys = array_merge(array_keys(rb_product_meta_fields()), array_keys(rb_product_price_fields()));
    $meta = [];

    foreach ($keys as $key) {
        $meta[$key] = get_post_meta($post_id, $key, true);
    }

    return $meta;
}

add_action('after_switch_theme', 'rb_theme_activation_tasks');
function rb_theme_activation_tasks(): void
{
    add_role('rb_retail_customer', 'Розничный покупатель', ['read' => true]);
    add_role('rb_business_customer', 'Юрлицо', ['read' => true]);
    rb_register_content_types();
    flush_rewrite_rules();
}

add_action('admin_menu', 'rb_admin_menu');
function rb_admin_menu(): void
{
    add_menu_page('Roastberry Coffee Roasters', 'Roastberry Coffee Roasters', 'edit_posts', 'rb-roasters', 'rb_render_admin_dashboard', 'dashicons-store', 26);
    add_submenu_page('rb-roasters', 'Контакты сайта', 'Контакты сайта', 'manage_options', 'rb-site-contacts', 'rb_render_contact_settings_page');
    add_submenu_page('rb-roasters', 'Категории товаров', 'Категории товаров', 'manage_categories', 'edit-tags.php?taxonomy=rb_product_category&post_type=rb_product');
    add_submenu_page('rb-roasters', 'Пользователи', 'Пользователи', 'list_users', 'rb-users', 'rb_render_users_admin_link');
    add_submenu_page('rb-roasters', 'Доставка СДЭК', 'Доставка СДЭК', 'manage_options', 'rb-cdek', 'rb_render_cdek_settings_page');
}

add_action('created_rb_product_category', 'rb_set_new_product_category_order');
function rb_set_new_product_category_order(int $term_id): void
{
    $orders = get_terms([
        'taxonomy' => 'rb_product_category',
        'hide_empty' => false,
        'fields' => 'ids',
        'meta_key' => 'rb_category_order',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'number' => 1,
        'rb_skip_category_order' => true,
    ]);
    $last_order = !is_wp_error($orders) && $orders
        ? (int) get_term_meta((int) $orders[0], 'rb_category_order', true)
        : -1;

    update_term_meta($term_id, 'rb_category_order', $last_order + 1);
}

function rb_ensure_product_category_order(): void
{
    $terms = get_terms([
        'taxonomy' => 'rb_product_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'rb_skip_category_order' => true,
    ]);
    if (is_wp_error($terms) || !$terms) {
        return;
    }

    $next_order = -1;
    foreach ($terms as $term) {
        $value = get_term_meta($term->term_id, 'rb_category_order', true);
        if ($value !== '') {
            $next_order = max($next_order, (int) $value);
        }
    }

    foreach ($terms as $term) {
        if (get_term_meta($term->term_id, 'rb_category_order', true) === '') {
            update_term_meta($term->term_id, 'rb_category_order', ++$next_order);
        }
    }
}

add_action('load-edit-tags.php', 'rb_prepare_product_category_order');
function rb_prepare_product_category_order(): void
{
    $taxonomy = isset($_GET['taxonomy']) ? sanitize_key(wp_unslash($_GET['taxonomy'])) : '';
    if ($taxonomy === 'rb_product_category' && current_user_can('manage_categories')) {
        rb_ensure_product_category_order();
    }
}

add_filter('manage_edit-rb_product_category_columns', 'rb_product_category_order_column');
function rb_product_category_order_column(array $columns): array
{
    $ordered = [];
    foreach ($columns as $key => $label) {
        if ($key === 'name') {
            $ordered['rb_category_order'] = '<span class="dashicons dashicons-menu" aria-hidden="true"></span><span class="screen-reader-text">Порядок</span>';
        }
        $ordered[$key] = $label;
    }

    return $ordered;
}

add_filter('manage_rb_product_category_custom_column', 'rb_product_category_order_column_content', 10, 3);
function rb_product_category_order_column_content(string $content, string $column_name, int $term_id): string
{
    if ($column_name !== 'rb_category_order') {
        return $content;
    }

    return '<button type="button" class="rb-category-drag" data-rb-category-drag aria-label="Перетащить категорию" title="Перетащить категорию"><span class="dashicons dashicons-menu" aria-hidden="true"></span></button>';
}

add_filter('get_terms_args', 'rb_product_category_admin_order', 10, 2);
function rb_product_category_admin_order(array $args, array $taxonomies): array
{
    if (
        !is_admin()
        || !empty($args['rb_skip_category_order'])
        || !in_array('rb_product_category', $taxonomies, true)
        || isset($_GET['orderby'])
    ) {
        return $args;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->taxonomy !== 'rb_product_category') {
        return $args;
    }

    $args['meta_key'] = 'rb_category_order';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'ASC';

    return $args;
}

add_action('admin_enqueue_scripts', 'rb_enqueue_product_category_order_assets');
function rb_enqueue_product_category_order_assets(): void
{
    $screen = get_current_screen();
    if (!$screen || $screen->taxonomy !== 'rb_product_category') {
        return;
    }

    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style('rb-category-order-admin', rb_asset_url('css/admin-category-order.css'), [], rb_asset_version('css/admin-category-order.css'));
    wp_enqueue_script('rb-category-order-admin', rb_asset_url('js/admin-category-order.js'), ['jquery', 'jquery-ui-sortable'], rb_asset_version('js/admin-category-order.js'), true);
    wp_localize_script('rb-category-order-admin', 'rbCategoryOrder', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rb_save_category_order'),
    ]);
}

add_action('wp_ajax_rb_save_category_order', 'rb_save_product_category_order');
function rb_save_product_category_order(): void
{
    check_ajax_referer('rb_save_category_order', 'nonce');
    if (!current_user_can('manage_categories')) {
        wp_send_json_error(['message' => 'Недостаточно прав для сортировки категорий.'], 403);
    }

    $term_ids = isset($_POST['termIds']) && is_array($_POST['termIds'])
        ? array_values(array_unique(array_map('absint', wp_unslash($_POST['termIds']))))
        : [];
    if (!$term_ids) {
        wp_send_json_error(['message' => 'Не удалось получить порядок категорий.'], 400);
    }

    foreach ($term_ids as $order => $term_id) {
        if (term_exists($term_id, 'rb_product_category')) {
            update_term_meta($term_id, 'rb_category_order', $order);
        }
    }

    wp_send_json_success();
}

function rb_render_admin_dashboard(): void
{
    ?>
    <div class="wrap">
        <h1>Roastberry Coffee Roasters</h1>
        <p>Основные разделы управления сайтом.</p>
        <p>
            <a class="button button-primary" href="<?= esc_url(admin_url('post-new.php?post_type=rb_product')) ?>">Добавить товар</a>
            <a class="button" href="<?= esc_url(admin_url('post-new.php?post_type=rb_article')) ?>">Добавить новость</a>
            <a class="button" href="<?= esc_url(admin_url('post-new.php?post_type=rb_training')) ?>">Добавить курс</a>
            <a class="button" href="<?= esc_url(admin_url('edit.php?post_type=rb_brew_method')) ?>">Способы приготовления</a>
            <a class="button" href="<?= esc_url(admin_url('admin.php?page=rb-orders')) ?>">Открыть заказы</a>
            <a class="button" href="<?= esc_url(admin_url('admin.php?page=rb-price-requests')) ?>">Заявки на прайс</a>
            <a class="button" href="<?= esc_url(admin_url('users.php')) ?>">Пользователи</a>
            <a class="button" href="<?= esc_url(admin_url('admin.php?page=rb-site-contacts')) ?>">Контакты сайта</a>
            <a class="button" href="<?= esc_url(admin_url('admin.php?page=rb-cdek')) ?>">Настроить СДЭК</a>
        </p>
    </div>
    <?php
}

function rb_render_users_admin_link(): void
{
    ?>
    <div class="wrap">
        <h1>Пользователи</h1>
        <p>Здесь можно открыть список клиентов и изменить их данные.</p>
        <p><a class="button button-primary" href="<?= esc_url(admin_url('users.php')) ?>">Открыть пользователей</a></p>
    </div>
    <?php
}
