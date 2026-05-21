<?php
/**
 * ROASTBERRY THEME functions.
 *
 * @package ROASTBERRY_THEME
 */

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/functions.php';

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
    wp_enqueue_script('rb-main', rb_asset_url('js/main.js'), [], rb_asset_version('js/main.js'), true);
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

function rb_contacts(): array
{
    global $contacts;

    return is_array($contacts ?? null) ? $contacts : [];
}

function rb_menu(): array
{
    global $menu;

    return is_array($menu ?? null) ? $menu : [];
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
        'show_in_menu' => true,
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
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title', 'author'],
        'capability_type' => 'post',
    ]);
}

add_action('add_meta_boxes', 'rb_register_meta_boxes');
function rb_register_meta_boxes(): void
{
    add_meta_box('rb_product_details', 'Характеристики товара', 'rb_render_product_meta_box', 'rb_product', 'normal', 'high');
    add_meta_box('rb_product_prices', 'Цены и наличие', 'rb_render_product_prices_meta_box', 'rb_product', 'side', 'default');
    add_meta_box('rb_order_details', 'Данные заказа', 'rb_render_order_meta_box', 'rb_order', 'normal', 'high');
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

function rb_render_product_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_product_meta', 'rb_product_meta_nonce');

    foreach (rb_product_meta_fields() as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
        echo '<input class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"></p>';
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

function rb_order_meta_fields(): array
{
    return [
        'rb_order_status' => 'Статус',
        'rb_customer_name' => 'ФИО',
        'rb_customer_phone' => 'Телефон',
        'rb_customer_email' => 'Почта',
        'rb_delivery_method' => 'Доставка',
        'rb_pickup_point' => 'Точка самовывоза',
        'rb_promocode' => 'Промокод',
        'rb_order_total' => 'Сумма',
        'rb_order_items' => 'Позиции заказа',
    ];
}

function rb_render_order_meta_box(WP_Post $post): void
{
    wp_nonce_field('rb_save_order_meta', 'rb_order_meta_nonce');

    foreach (rb_order_meta_fields() as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';

        if ($key === 'rb_order_items') {
            echo '<textarea class="widefat" rows="6" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }

        echo '</p>';
    }
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

    foreach (array_merge(array_keys(rb_product_meta_fields()), array_keys(rb_product_price_fields())) as $key) {
        $value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
        update_post_meta($post_id, $key, $value);
    }
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

    foreach (rb_order_meta_fields() as $key => $label) {
        $value = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
        update_post_meta($post_id, $key, $value);
    }
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
        echo esc_html((string) get_post_meta($post_id, $column, true));
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

    if ($action === 'profile' && is_user_logged_in()) {
        rb_handle_profile_update();
    }

    if ($action === 'order') {
        rb_handle_order_create();
    }
}

function rb_handle_registration(): void
{
    if (!isset($_POST['rb_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_register_nonce'])), 'rb_register')) {
        return;
    }

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $name = isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : wp_generate_password(12, false);

    if (!$email || email_exists($email)) {
        wp_safe_redirect(add_query_arg('auth', 'exists', wp_get_referer() ?: route_url('account')));
        exit;
    }

    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'display_name' => $name ?: $email,
        'first_name' => $name,
        'user_pass' => $password,
        'role' => get_role('rb_retail_customer') ? 'rb_retail_customer' : 'subscriber',
    ]);

    if (!is_wp_error($user_id)) {
        update_user_meta($user_id, 'rb_phone', $phone);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
    }

    wp_safe_redirect(route_url('account'));
    exit;
}

function rb_handle_login(): void
{
    if (!isset($_POST['rb_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_login_nonce'])), 'rb_login')) {
        return;
    }

    $creds = [
        'user_login' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
        'user_password' => isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '',
        'remember' => true,
    ];

    $user = wp_signon($creds, is_ssl());

    wp_safe_redirect(is_wp_error($user) ? add_query_arg('auth', 'failed', route_url('account')) : route_url('account'));
    exit;
}

function rb_handle_profile_update(): void
{
    if (!isset($_POST['rb_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_profile_nonce'])), 'rb_profile')) {
        return;
    }

    $user_id = get_current_user_id();
    wp_update_user([
        'ID' => $user_id,
        'display_name' => isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '',
        'user_email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
    ]);

    update_user_meta($user_id, 'rb_phone', isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '');

    wp_safe_redirect(route_url('account'));
    exit;
}

function rb_handle_order_create(): void
{
    if (!isset($_POST['rb_order_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rb_order_nonce'])), 'rb_order')) {
        return;
    }

    $order_id = wp_insert_post([
        'post_type' => 'rb_order',
        'post_status' => 'publish',
        'post_title' => 'Заказ от ' . current_time('d.m.Y H:i'),
        'post_author' => get_current_user_id(),
    ]);

    if ($order_id && !is_wp_error($order_id)) {
        update_post_meta($order_id, 'rb_order_status', 'Новый');
        foreach (rb_order_meta_fields() as $key => $label) {
            if ($key === 'rb_order_status') {
                continue;
            }

            $value = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
            update_post_meta($order_id, $key, $value);
        }
    }

    wp_safe_redirect(add_query_arg('order', 'created', route_url('cart')));
    exit;
}

add_action('show_user_profile', 'rb_render_user_profile_fields');
add_action('edit_user_profile', 'rb_render_user_profile_fields');
function rb_render_user_profile_fields(WP_User $user): void
{
    ?>
    <h2>RB Roasters</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="rb_phone">Телефон</label></th>
            <td><input type="text" name="rb_phone" id="rb_phone" value="<?= esc_attr(get_user_meta($user->ID, 'rb_phone', true)) ?>" class="regular-text"></td>
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

    update_user_meta($user_id, 'rb_phone', isset($_POST['rb_phone']) ? sanitize_text_field(wp_unslash($_POST['rb_phone'])) : '');
    update_user_meta($user_id, 'rb_company', isset($_POST['rb_company']) ? sanitize_text_field(wp_unslash($_POST['rb_company'])) : '');
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
    add_menu_page('RB Roasters', 'RB Roasters', 'edit_posts', 'rb-roasters', 'rb_render_admin_dashboard', 'dashicons-store', 26);
    add_submenu_page('rb-roasters', 'Товары', 'Товары', 'edit_posts', 'edit.php?post_type=rb_product');
    add_submenu_page('rb-roasters', 'Заказы', 'Заказы', 'edit_posts', 'edit.php?post_type=rb_order');
    add_submenu_page('rb-roasters', 'Пользователи', 'Пользователи', 'list_users', 'users.php');
}

function rb_render_admin_dashboard(): void
{
    ?>
    <div class="wrap">
        <h1>RB Roasters</h1>
        <p>Основные разделы управления сайтом.</p>
        <p>
            <a class="button button-primary" href="<?= esc_url(admin_url('post-new.php?post_type=rb_product')) ?>">Добавить товар</a>
            <a class="button" href="<?= esc_url(admin_url('edit.php?post_type=rb_order')) ?>">Открыть заказы</a>
            <a class="button" href="<?= esc_url(admin_url('users.php')) ?>">Пользователи</a>
        </p>
    </div>
    <?php
}
