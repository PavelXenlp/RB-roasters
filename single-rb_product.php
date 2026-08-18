<?php
/**
 * Single product template.
 *
 * @package ROASTBERRY_THEME
 */

get_header();

while (have_posts()):
    the_post();
    $product_id = get_the_ID();
    $meta = rb_get_product_meta($product_id);
    $image = get_the_post_thumbnail_url($product_id, 'large') ?: rb_asset_url('img/1.webp');
    $grinds = $meta['rb_grind_options'] ? array_values(array_filter(array_map('trim', explode(',', $meta['rb_grind_options'])))) : ['В зернах', 'Фильтр', 'Турка', 'Френч-пресс', 'Эспрессо', 'Гейзерная кофеварка', 'AeroPress', 'Воронка'];
    $price_200 = rb_parse_price((string) $meta['rb_price_200']);
    $price_1000 = rb_parse_price((string) $meta['rb_price_1000']);
    $old_price_200 = rb_parse_price((string) $meta['rb_old_price_200']);
    $old_price_1000 = rb_parse_price((string) $meta['rb_old_price_1000']);
    $loyalty = rb_user_loyalty_data(get_current_user_id());
    $personal_price_200 = $price_200 > 0 ? (int) round($price_200 * (100 - (int) $loyalty['percent']) / 100) : 0;
    $personal_price_1000 = $price_1000 > 0 ? (int) round($price_1000 * (100 - (int) $loyalty['percent']) / 100) : 0;
    $stock_200 = rb_product_stock_by_size($product_id, '200');
    $stock_1000 = rb_product_stock_by_size($product_id, '1000');
    $has_stock_200 = $stock_200 === null || $stock_200 > 0;
    $has_stock_1000 = $stock_1000 === null || $stock_1000 > 0;
    $is_in_stock = $has_stock_200 || $has_stock_1000;
    $product_terms = get_the_terms($product_id, 'rb_product_category');
    $product_terms = !is_wp_error($product_terms) && $product_terms ? $product_terms : [];
    $primary_term = $product_terms ? reset($product_terms) : null;
    $description = trim((string) ($meta['rb_descriptors'] ?: get_the_excerpt($product_id)));
    $recommended_ids = rb_get_product_recommendations($product_id);
    $offers = [];

    if ($price_200 > 0) {
        $offers[] = [
            '@type' => 'Offer',
            'name' => 'Упаковка 200 г',
            'url' => get_permalink($product_id),
            'priceCurrency' => 'RUB',
            'price' => (string) $price_200,
            'availability' => $has_stock_200 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];
    }

    if ($price_1000 > 0) {
        $offers[] = [
            '@type' => 'Offer',
            'name' => 'Упаковка 1 кг',
            'url' => get_permalink($product_id),
            'priceCurrency' => 'RUB',
            'price' => (string) $price_1000,
            'availability' => $has_stock_1000 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];
    }

    $product_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => get_the_title($product_id),
        'image' => [$image],
        'description' => $description ?: wp_strip_all_tags(get_the_content()),
        'sku' => (string) (get_post_meta($product_id, 'rb_sku', true) ?: 'RB-' . $product_id),
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Roastberry Coffee Roasters',
        ],
        'category' => $product_terms ? implode(', ', wp_list_pluck($product_terms, 'name')) : 'Каталог кофе',
    ];
    if ($offers) {
        $product_schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
    }
    ?>

    <div class="product-breadcrumbs-wrap">
        <?php rb_catalog_breadcrumbs($primary_term instanceof WP_Term ? $primary_term : null, get_post($product_id)); ?>
    </div>

    <section class="product-page">
        <div class="product-gallery">
            <div class="product-gallery__main">
                <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>">
                <span class="product-stock product-stock--<?= $is_in_stock ? 'available' : 'unavailable' ?>">
                    <?= $is_in_stock ? 'В наличии' : 'Нет в наличии' ?>
                </span>
            </div>
        </div>
        <div class="product-info">
            <span class="eyebrow"><?= get_the_term_list($product_id, 'rb_product_category', '', ', ') ?: 'Кофе' ?></span>
            <h1><?= esc_html(get_the_title()) ?></h1>
            <?php if ($description): ?><p class="lead"><strong>Во вкусе:</strong> <?= esc_html($description) ?></p><?php endif; ?>

            <div class="product-prices" aria-label="Цены">
                <?php if ($price_200 > 0): ?>
                    <div class="product-price product-price--primary">
                        <span>200 г</span>
                        <div>
                            <?php if ($old_price_200 > $price_200): ?><del><?= esc_html(rb_format_price($old_price_200)) ?></del><?php elseif ($personal_price_200 < $price_200): ?><del><?= esc_html(rb_format_price($price_200)) ?></del><?php endif; ?>
                            <strong><?= esc_html(rb_format_price($personal_price_200)) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($price_1000 > 0): ?>
                    <div class="product-price">
                        <span>1 кг</span>
                        <div>
                            <?php if ($old_price_1000 > $price_1000): ?><del><?= esc_html(rb_format_price($old_price_1000)) ?></del><?php elseif ($personal_price_1000 < $price_1000): ?><del><?= esc_html(rb_format_price($price_1000)) ?></del><?php endif; ?>
                            <strong><?= esc_html(rb_format_price($personal_price_1000)) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($price_200 <= 0 && $price_1000 <= 0): ?>
                    <div class="product-price product-price--unavailable">
                        <span>Цена</span>
                        <strong>Уточняется</strong>
                    </div>
                <?php endif; ?>
            </div>

            <form class="order-form product-order" action="<?= esc_url(route_url('cart')) ?>" method="post" data-add-to-cart-form>
                <input type="hidden" name="rb_action" value="add_to_cart">
                <input type="hidden" name="product_id" value="<?= esc_attr($product_id) ?>">
                <?php wp_nonce_field('rb_add_to_cart', 'rb_cart_nonce'); ?>
                <div class="product-order__head">
                    <strong>Добавить в корзину</strong>
                    <span><?= $is_in_stock ? 'Выберите упаковку и помол' : 'Товар временно недоступен' ?></span>
                </div>
                <div class="product-order__options">
                    <label>Размер
                        <?php
                        $sizes = [];
                        if ($price_200 > 0 && $has_stock_200) {
                            $sizes['200'] = '200 г — ' . rb_format_price($personal_price_200);
                        }
                        if ($price_1000 > 0 && $has_stock_1000) {
                            $sizes['1000'] = '1 кг — ' . rb_format_price($personal_price_1000);
                        }
                        rb_custom_select('size', $sizes ?: ['200' => '200 г'], (string) array_key_first($sizes ?: ['200' => '200 г']), 'Размер');
                        ?>
                    </label>
                    <label>Помол
                        <?php rb_custom_select('grind', array_combine($grinds, $grinds), $grinds[0] ?? 'В зернах', 'Помол'); ?>
                    </label>
                </div>
                <div class="product-order__actions">
                    <div>
                        <span>Количество</span>
                        <div class="quantity-control">
                            <button type="button" data-quantity-change="-1" aria-label="Уменьшить количество"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/></svg></button>
                            <input type="number" min="1" value="1" name="quantity" aria-label="Количество товара">
                            <button type="button" data-quantity-change="1" aria-label="Увеличить количество"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></button>
                        </div>
                    </div>
                    <button class="button" type="submit" <?= $is_in_stock && $offers ? '' : 'disabled' ?>>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H6"/></svg>
                        В корзину
                    </button>
                </div>
            </form>

            <?php
            $specifications = [
                'Способ обработки' => $meta['rb_process'],
                'Степень обжарки' => $meta['rb_roast'],
                'Страна' => $meta['rb_country'],
                'Регион' => $meta['rb_region'],
                'Высота' => $meta['rb_height'],
                'Разновидность' => $meta['rb_variety'],
            ];
            $specifications = array_filter($specifications, static fn($value): bool => trim((string) $value) !== '');
            ?>
            <?php if ($specifications): ?>
                <dl class="specs">
                    <?php foreach ($specifications as $label => $value): ?>
                        <div><dt><?= esc_html($label) ?></dt><dd><?= esc_html($value) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>

            <?php if (trim(get_the_content())): ?>
                <div class="copy-block product-description">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($recommended_ids): ?>
        <?php
        $recommended_query = new WP_Query([
            'post_type' => 'rb_product',
            'post_status' => 'publish',
            'posts_per_page' => count($recommended_ids),
            'post__in' => $recommended_ids,
            'orderby' => 'post__in',
        ]);
        ?>
        <?php if ($recommended_query->have_posts()): ?>
            <section class="section product-recommendations">
                <div class="section-title">
                    <span>Дополните заказ</span>
                    <h2>С этим товаром рекомендуем</h2>
                </div>
                <div class="product-grid product-grid--recommended">
                    <?php while ($recommended_query->have_posts()): $recommended_query->the_post(); ?>
                        <?php rb_product_card_from_post(get_the_ID()); ?>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    <?php endif; ?>

    <script type="application/ld+json"><?= wp_json_encode($product_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endwhile; ?>

<?php get_footer(); ?>
