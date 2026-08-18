<?php
/**
 * Custom admin lists for orders and price-list requests.
 *
 * @package ROASTBERRY_THEME
 */

add_action('admin_menu', 'rb_register_order_admin_pages', 12);
function rb_register_order_admin_pages(): void
{
    add_submenu_page('rb-roasters', 'Заказы', 'Заказы', 'edit_posts', 'rb-orders', 'rb_render_orders_admin_page');
    add_submenu_page('rb-roasters', 'Заявки на прайс', 'Заявки на прайс', 'edit_posts', 'rb-price-requests', 'rb_render_price_requests_admin_page');
}

add_action('admin_enqueue_scripts', 'rb_enqueue_order_admin_assets');
function rb_enqueue_order_admin_assets(): void
{
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (!in_array($page, ['rb-orders', 'rb-price-requests'], true)) return;
    wp_enqueue_style('rb-orders-admin', rb_asset_url('css/admin-orders.css'), [], rb_asset_version('css/admin-orders.css'));
}

function rb_order_admin_filters(): array
{
    return [
        'search' => sanitize_text_field(wp_unslash($_GET['order_search'] ?? '')),
        'status' => sanitize_key(wp_unslash($_GET['order_status'] ?? '')),
        'payment_status' => sanitize_key(wp_unslash($_GET['payment_status'] ?? '')),
        'order_type' => sanitize_key(wp_unslash($_GET['order_type'] ?? '')),
        'payment_method' => sanitize_key(wp_unslash($_GET['payment_method'] ?? '')),
        'delivery_method' => sanitize_text_field(wp_unslash($_GET['delivery_method'] ?? '')),
        'date_from' => sanitize_text_field(wp_unslash($_GET['date_from'] ?? '')),
        'date_to' => sanitize_text_field(wp_unslash($_GET['date_to'] ?? '')),
        'paged' => max(1, absint($_GET['paged'] ?? 1)),
    ];
}

function rb_order_admin_search_ids(string $search): array
{
    global $wpdb;
    if ($search === '') return [];
    $like = '%' . $wpdb->esc_like($search) . '%';
    $meta_keys = ['rb_customer_name', 'rb_customer_phone', 'rb_customer_email', 'rb_company_name', 'rb_company_inn', 'rb_pickup_point'];
    $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
    $sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'rb_order' AND (p.post_title LIKE %s OR (pm.meta_key IN ({$placeholders}) AND pm.meta_value LIKE %s))";
    $params = array_merge([$like], $meta_keys, [$like]);
    return array_map('absint', $wpdb->get_col($wpdb->prepare($sql, $params)));
}

function rb_order_admin_query_args(string $view, array $filters, bool $export = false): array
{
    $meta_query = ['relation' => 'AND'];
    if ($view === 'price') {
        $meta_query[] = ['key' => 'rb_order_type', 'value' => 'business_lead'];
    } else {
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => 'rb_order_type', 'compare' => 'NOT EXISTS'],
            ['key' => 'rb_order_type', 'value' => 'business_lead', 'compare' => '!='],
        ];
        if (in_array($filters['order_type'], ['retail', 'business'], true)) {
            $meta_query[] = ['key' => 'rb_order_type', 'value' => $filters['order_type']];
        }
        if (array_key_exists($filters['payment_status'], rb_payment_statuses())) {
            $meta_query[] = ['key' => 'rb_payment_status', 'value' => $filters['payment_status']];
        }
        if (in_array($filters['payment_method'], ['manager', 'yookassa', 'invoice'], true)) {
            $meta_query[] = ['key' => 'rb_payment_method', 'value' => $filters['payment_method']];
        }
        if ($filters['delivery_method'] !== '') {
            $meta_query[] = ['key' => 'rb_delivery_method', 'value' => $filters['delivery_method']];
        }
    }
    if (array_key_exists($filters['status'], rb_order_statuses())) {
        $meta_query[] = ['key' => 'rb_order_status', 'value' => $filters['status']];
    }

    $args = [
        'post_type' => 'rb_order',
        'post_status' => 'any',
        'posts_per_page' => $export ? -1 : 25,
        'paged' => $export ? 1 : $filters['paged'],
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => $meta_query,
    ];
    if ($filters['search'] !== '') {
        $ids = rb_order_admin_search_ids($filters['search']);
        $args['post__in'] = $ids ?: [0];
    }
    $date_query = [];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) $date_query['after'] = $filters['date_from'] . ' 00:00:00';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) $date_query['before'] = $filters['date_to'] . ' 23:59:59';
    if ($date_query) {
        $date_query['inclusive'] = true;
        $args['date_query'] = [$date_query];
    }
    return $args;
}

function rb_order_admin_badge(string $value, string $kind = 'status'): string
{
    $labels = $kind === 'payment' ? rb_payment_statuses() : rb_order_statuses();
    return '<span class="rb-admin-badge rb-admin-badge--' . esc_attr(sanitize_html_class($value ?: 'unknown')) . '">' . esc_html($labels[$value] ?? ($value ?: 'Не указан')) . '</span>';
}

function rb_order_admin_type_label(string $type): string
{
    return $type === 'business' ? 'Оптовый' : 'Розничный';
}

function rb_order_admin_metrics(string $view): array
{
    $filters = array_fill_keys(['search', 'status', 'payment_status', 'order_type', 'payment_method', 'delivery_method', 'date_from', 'date_to'], '');
    $filters['paged'] = 1;
    $args = rb_order_admin_query_args($view, $filters, true);
    $args['fields'] = 'ids';
    $ids = get_posts($args);
    $metrics = ['total' => count($ids), 'new' => 0, 'active' => 0, 'amount' => 0];
    foreach ($ids as $id) {
        $status = (string) get_post_meta($id, 'rb_order_status', true);
        if ($status === 'new') $metrics['new']++;
        if (in_array($status, ['awaiting_payment', 'processing', 'ready', 'shipped'], true)) $metrics['active']++;
        if ($view === 'orders' && $status !== 'canceled') $metrics['amount'] += max(0, (int) get_post_meta($id, 'rb_order_total_amount', true));
    }
    return $metrics;
}

function rb_order_admin_export_url(string $view, array $filters): string
{
    $args = array_filter([
        'action' => 'rb_export_orders_csv', 'view' => $view, 'order_search' => $filters['search'],
        'order_status' => $filters['status'], 'payment_status' => $filters['payment_status'],
        'order_type' => $filters['order_type'], 'payment_method' => $filters['payment_method'],
        'delivery_method' => $filters['delivery_method'], 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to'],
    ], static fn($value): bool => $value !== '');
    return wp_nonce_url(add_query_arg($args, admin_url('admin-post.php')), 'rb_export_orders_csv');
}

function rb_render_order_admin_filters(string $view, array $filters): void
{
    $page = $view === 'price' ? 'rb-price-requests' : 'rb-orders';
    ?>
    <form class="rb-order-filters rb-order-filters--<?= esc_attr($view) ?>" method="get">
        <input type="hidden" name="page" value="<?= esc_attr($page) ?>">
        <label class="rb-order-search"><span>Поиск</span><input type="search" name="order_search" value="<?= esc_attr($filters['search']) ?>" placeholder="Номер, ФИО, телефон, почта<?= $view === 'price' ? '' : ', ИНН' ?>"></label>
        <label><span>Статус</span><select name="order_status"><option value="">Все статусы</option><?php foreach (rb_order_statuses() as $key => $label): ?><option value="<?= esc_attr($key) ?>" <?= selected($filters['status'], $key, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select></label>
        <?php if ($view === 'orders'): ?>
            <label><span>Тип заказа</span><select name="order_type"><option value="">Все типы</option><option value="retail" <?= selected($filters['order_type'], 'retail', false) ?>>Розничный</option><option value="business" <?= selected($filters['order_type'], 'business', false) ?>>Оптовый</option></select></label>
            <label><span>Оплата</span><select name="payment_status"><option value="">Любая оплата</option><?php foreach (rb_payment_statuses() as $key => $label): ?><option value="<?= esc_attr($key) ?>" <?= selected($filters['payment_status'], $key, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Способ оплаты</span><select name="payment_method"><option value="">Все способы</option><option value="manager" <?= selected($filters['payment_method'], 'manager', false) ?>>После оформления</option><option value="yookassa" <?= selected($filters['payment_method'], 'yookassa', false) ?>>YooKassa</option><option value="invoice" <?= selected($filters['payment_method'], 'invoice', false) ?>>Счет</option></select></label>
            <label><span>Доставка</span><select name="delivery_method"><option value="">Все способы</option><?php foreach (['Самовывоз из кофейни', 'Самовывоз с производства', 'СДЭК до пункта выдачи', 'Согласовать с менеджером'] as $method): ?><option value="<?= esc_attr($method) ?>" <?= selected($filters['delivery_method'], $method, false) ?>><?= esc_html($method) ?></option><?php endforeach; ?></select></label>
        <?php endif; ?>
        <label><span>С даты</span><input type="date" name="date_from" value="<?= esc_attr($filters['date_from']) ?>"></label>
        <label><span>По дату</span><input type="date" name="date_to" value="<?= esc_attr($filters['date_to']) ?>"></label>
        <div class="rb-order-filters__actions"><button class="button button-primary">Применить</button><a class="button" href="<?= esc_url(admin_url('admin.php?page=' . $page)) ?>">Сбросить</a></div>
    </form>
    <?php
}

function rb_render_order_status_form(int $order_id, string $current, string $view): void
{
    ?>
    <form class="rb-order-status-form" method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <input type="hidden" name="action" value="rb_update_order_status"><input type="hidden" name="order_id" value="<?= esc_attr((string) $order_id) ?>"><input type="hidden" name="view" value="<?= esc_attr($view) ?>">
        <?php wp_nonce_field('rb_update_order_status_' . $order_id); ?>
        <select name="order_status" aria-label="Статус заказа №<?= esc_attr((string) $order_id) ?>"><?php foreach (rb_order_statuses() as $key => $label): ?><option value="<?= esc_attr($key) ?>" <?= selected($current, $key, false) ?>><?= esc_html($label) ?></option><?php endforeach; ?></select>
        <button class="button" type="submit">Сохранить</button>
    </form>
    <?php
}

function rb_render_order_admin_page(string $view): void
{
    if (!current_user_can('edit_posts')) wp_die('Недостаточно прав.');
    $filters = rb_order_admin_filters();
    $query = new WP_Query(rb_order_admin_query_args($view, $filters));
    $metrics = rb_order_admin_metrics($view);
    $is_price = $view === 'price';
    ?>
    <div class="wrap rb-orders-admin">
        <div class="rb-orders-head"><div><span class="rb-orders-kicker">Roastberry Coffee Roasters</span><h1><?= $is_price ? 'Заявки на прайс-лист' : 'Заказы' ?></h1><p><?= $is_price ? 'Контакты потенциальных оптовых клиентов и история обработки заявок.' : 'Розничные и оптовые заказы, оплата, доставка и статусы выполнения.' ?></p></div><a class="button button-primary rb-export-button" href="<?= esc_url(rb_order_admin_export_url($view, $filters)) ?>"><span class="dashicons dashicons-download"></span>Экспорт CSV</a></div>
        <div class="rb-order-metrics">
            <article><span>Всего</span><strong><?= esc_html((string) $metrics['total']) ?></strong></article>
            <article><span>Новые</span><strong><?= esc_html((string) $metrics['new']) ?></strong></article>
            <article><span>В работе</span><strong><?= esc_html((string) $metrics['active']) ?></strong></article>
            <?php if (!$is_price): ?><article><span>Сумма без отмененных</span><strong><?= esc_html(rb_format_price((int) $metrics['amount'])) ?></strong></article><?php endif; ?>
        </div>
        <?php if (isset($_GET['updated'])): ?><div class="notice notice-success is-dismissible"><p>Статус обновлен.</p></div><?php endif; ?>
        <?php rb_render_order_admin_filters($view, $filters); ?>
        <div class="rb-order-table-wrap">
            <table class="rb-order-table"><thead><tr><th>№ и дата</th><th>Клиент</th><?php if (!$is_price): ?><th>Заказ</th><th>Доставка</th><th>Оплата</th><th>Сумма</th><?php endif; ?><th>Статус</th><th></th></tr></thead><tbody>
            <?php if ($query->have_posts()): while ($query->have_posts()): $query->the_post(); $id = get_the_ID(); $status = (string) get_post_meta($id, 'rb_order_status', true); ?>
                <tr>
                    <td data-label="Заявка"><a class="rb-order-number" href="<?= esc_url(get_edit_post_link($id)) ?>">#<?= esc_html((string) $id) ?></a><time><?= esc_html(get_the_date('d.m.Y')) ?><small><?= esc_html(get_the_time('H:i')) ?></small></time></td>
                    <td data-label="Клиент"><strong><?= esc_html((string) get_post_meta($id, 'rb_customer_name', true)) ?></strong><a href="tel:<?= esc_attr(rb_phone_href((string) get_post_meta($id, 'rb_customer_phone', true))) ?>"><?= esc_html(rb_format_phone((string) get_post_meta($id, 'rb_customer_phone', true))) ?></a><a href="mailto:<?= esc_attr((string) get_post_meta($id, 'rb_customer_email', true)) ?>"><?= esc_html((string) get_post_meta($id, 'rb_customer_email', true)) ?></a><?php if ($is_price): ?><small><?= esc_html((string) get_post_meta($id, 'rb_company_name', true)) ?></small><?php endif; ?></td>
                    <?php if (!$is_price): ?>
                        <?php $items = rb_get_order_items($id); $type = (string) get_post_meta($id, 'rb_order_type', true); ?>
                        <td data-label="Заказ"><strong><?= esc_html(rb_order_admin_type_label($type)) ?></strong><span><?= esc_html((string) count($items)) ?> поз.</span><?php if ($type === 'business'): ?><small><?= esc_html((string) get_post_meta($id, 'rb_company_name', true)) ?></small><?php endif; ?></td>
                        <td data-label="Доставка"><strong><?= esc_html((string) get_post_meta($id, 'rb_delivery_method', true)) ?></strong><small><?= esc_html((string) get_post_meta($id, 'rb_pickup_point', true)) ?></small></td>
                        <td data-label="Оплата"><?= rb_order_admin_badge((string) get_post_meta($id, 'rb_payment_status', true), 'payment') ?><small><?= esc_html((string) get_post_meta($id, 'rb_payment_method', true)) ?></small></td>
                        <td data-label="Сумма"><strong class="rb-order-total"><?= esc_html((string) (get_post_meta($id, 'rb_order_total', true) ?: rb_format_price((int) get_post_meta($id, 'rb_order_total_amount', true)))) ?></strong></td>
                    <?php endif; ?>
                    <td data-label="Статус"><?= rb_order_admin_badge($status) ?><?php rb_render_order_status_form($id, $status, $view); ?></td>
                    <td><a class="button" href="<?= esc_url(get_edit_post_link($id)) ?>">Открыть</a></td>
                </tr>
            <?php endwhile; wp_reset_postdata(); else: ?><tr><td class="rb-order-empty" colspan="8">По выбранным условиям ничего не найдено.</td></tr><?php endif; ?>
            </tbody></table>
        </div>
        <?php if ($query->max_num_pages > 1): ?><div class="rb-order-pagination"><?= paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $filters['paged'], 'total' => $query->max_num_pages, 'type' => 'list']) ?></div><?php endif; ?>
    </div>
    <?php
}

function rb_render_orders_admin_page(): void { rb_render_order_admin_page('orders'); }
function rb_render_price_requests_admin_page(): void { rb_render_order_admin_page('price'); }

add_action('admin_post_rb_update_order_status', 'rb_handle_order_status_update');
function rb_handle_order_status_update(): void
{
    $order_id = absint($_POST['order_id'] ?? 0);
    if (!$order_id || get_post_type($order_id) !== 'rb_order' || !current_user_can('edit_post', $order_id)) wp_die('Недостаточно прав.');
    check_admin_referer('rb_update_order_status_' . $order_id);
    $status = sanitize_key(wp_unslash($_POST['order_status'] ?? ''));
    if (array_key_exists($status, rb_order_statuses())) {
        $previous = (string) get_post_meta($order_id, 'rb_order_status', true);
        update_post_meta($order_id, 'rb_order_status', $status);
        if ($status === 'canceled' && $previous !== 'canceled') rb_release_order_stock($order_id);
    }
    $view = sanitize_key(wp_unslash($_POST['view'] ?? 'orders')) === 'price' ? 'rb-price-requests' : 'rb-orders';
    $fallback = admin_url('admin.php?page=' . $view);
    $return_url = wp_validate_redirect((string) wp_get_referer(), $fallback);
    wp_safe_redirect(add_query_arg('updated', '1', $return_url));
    exit;
}

function rb_csv_safe($value): string
{
    $value = preg_replace('/[\r\n]+/', ' ', (string) $value);
    return preg_match('/^[=+\-@\t]/u', $value) ? "'" . $value : $value;
}

add_action('admin_post_rb_export_orders_csv', 'rb_export_orders_csv');
function rb_export_orders_csv(): void
{
    if (!current_user_can('edit_posts')) wp_die('Недостаточно прав.');
    check_admin_referer('rb_export_orders_csv');
    $view = sanitize_key(wp_unslash($_GET['view'] ?? 'orders')) === 'price' ? 'price' : 'orders';
    $filters = rb_order_admin_filters();
    $query = new WP_Query(rb_order_admin_query_args($view, $filters, true));
    $filename = ($view === 'price' ? 'price-requests-' : 'orders-') . gmdate('Y-m-d-His') . '.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'wb');
    fwrite($output, "\xEF\xBB\xBF");
    $headers = $view === 'price'
        ? ['ID', 'Дата', 'Статус', 'ФИО', 'Телефон', 'Email', 'Компания']
        : ['ID', 'Дата', 'Тип', 'Статус', 'Статус оплаты', 'Способ оплаты', 'ФИО', 'Телефон', 'Email', 'Компания', 'ИНН', 'Доставка', 'Адрес/ПВЗ', 'Товары', 'Сумма товаров', 'Скидка', 'Доставка ₽', 'Итого ₽'];
    fputcsv($output, $headers, ';', '"', '');
    foreach ($query->posts as $post) {
        $id = $post->ID;
        if ($view === 'price') {
            $row = [$id, get_the_date('d.m.Y H:i', $id), rb_order_statuses()[(string) get_post_meta($id, 'rb_order_status', true)] ?? '', get_post_meta($id, 'rb_customer_name', true), get_post_meta($id, 'rb_customer_phone', true), get_post_meta($id, 'rb_customer_email', true), get_post_meta($id, 'rb_company_name', true)];
        } else {
            $type = (string) get_post_meta($id, 'rb_order_type', true);
            $status = (string) get_post_meta($id, 'rb_order_status', true);
            $payment = (string) get_post_meta($id, 'rb_payment_status', true);
            $row = [$id, get_the_date('d.m.Y H:i', $id), rb_order_admin_type_label($type), rb_order_statuses()[$status] ?? $status, rb_payment_statuses()[$payment] ?? $payment, get_post_meta($id, 'rb_payment_method', true), get_post_meta($id, 'rb_customer_name', true), get_post_meta($id, 'rb_customer_phone', true), get_post_meta($id, 'rb_customer_email', true), get_post_meta($id, 'rb_company_name', true), get_post_meta($id, 'rb_company_inn', true), get_post_meta($id, 'rb_delivery_method', true), get_post_meta($id, 'rb_pickup_point', true), get_post_meta($id, 'rb_order_items', true), get_post_meta($id, 'rb_order_subtotal_amount', true), get_post_meta($id, 'rb_discount_total_amount', true), get_post_meta($id, 'rb_delivery_cost_amount', true), get_post_meta($id, 'rb_order_total_amount', true)];
        }
        fputcsv($output, array_map('rb_csv_safe', $row), ';', '"', '');
    }
    fclose($output);
    exit;
}
