<?php
/**
 * Single product template.
 *
 * @package ROASTBERRY_THEME
 */

get_header();

while (have_posts()): the_post();
    $meta = rb_get_product_meta(get_the_ID());
    $image = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: rb_asset_url('img/1.webp');
    $grinds = $meta['rb_grind_options'] ? array_map('trim', explode(',', $meta['rb_grind_options'])) : ['В зернах', 'Фильтр', 'Турка', 'Френч-пресс', 'Эспрессо', 'Гейзерная кофеварка', 'AeroPress', 'Воронка'];
    ?>
    <section class="product-page">
        <div class="product-gallery">
            <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>">
        </div>
        <div class="product-info">
            <span class="eyebrow"><?= get_the_term_list(get_the_ID(), 'rb_product_category', '', ', ') ?: 'Кофе' ?></span>
            <h1><?= esc_html(get_the_title()) ?></h1>
            <p class="lead"><?= esc_html($meta['rb_descriptors'] ?: get_the_excerpt()) ?></p>
            <dl class="specs">
                <div><dt>Способ обработки</dt><dd><?= esc_html($meta['rb_process']) ?></dd></div>
                <div><dt>Степень обжарки</dt><dd><?= esc_html($meta['rb_roast']) ?></dd></div>
                <div><dt>Страна</dt><dd><?= esc_html($meta['rb_country']) ?></dd></div>
                <div><dt>Регион</dt><dd><?= esc_html($meta['rb_region']) ?></dd></div>
                <div><dt>Высота</dt><dd><?= esc_html($meta['rb_height']) ?></dd></div>
                <div><dt>Разновидность</dt><dd><?= esc_html($meta['rb_variety']) ?></dd></div>
            </dl>
            <div class="copy-block">
                <?php the_content(); ?>
            </div>
            <form class="order-form" action="<?= esc_url(route_url('cart')) ?>" method="post">
                <input type="hidden" name="rb_action" value="add_to_cart">
                <input type="hidden" name="product_id" value="<?= esc_attr(get_the_ID()) ?>">
                <?php wp_nonce_field('rb_add_to_cart', 'rb_cart_nonce'); ?>
                <label>Размер
                    <?php rb_custom_select('size', [
                        '200' => '200 г - ' . $meta['rb_price_200'],
                        '1000' => '1 кг - ' . $meta['rb_price_1000'],
                    ], '200', 'Размер'); ?>
                </label>
                <label>Помол
                    <?php rb_custom_select('grind', array_combine($grinds, $grinds), $grinds[0] ?? 'В зернах', 'Помол'); ?>
                </label>
                <label>Количество
                    <input type="number" min="1" value="1" name="quantity">
                </label>
                <button class="button" type="submit">В корзину</button>
            </form>
        </div>
    </section>
<?php endwhile; ?>

<?php get_footer(); ?>
