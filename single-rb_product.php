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
    ?>
    <section class="product-page">
        <div class="product-gallery">
            <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>">
        </div>
        <div class="product-info">
            <span class="eyebrow"><?= esc_html(get_the_term_list(get_the_ID(), 'rb_product_category', '', ', ') ?: 'Кофе') ?></span>
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
                <label>Размер
                    <select name="rb_order_items">
                        <option><?= esc_html(get_the_title()) ?>, 200 г - <?= esc_html($meta['rb_price_200']) ?></option>
                        <option><?= esc_html(get_the_title()) ?>, 1 кг - <?= esc_html($meta['rb_price_1000']) ?></option>
                    </select>
                </label>
                <label>Помол
                    <select name="grind">
                        <?php
                        $grinds = $meta['rb_grind_options'] ? array_map('trim', explode(',', $meta['rb_grind_options'])) : ['В зернах', 'Фильтр', 'Турка', 'Френч-пресс', 'Эспрессо', 'Гейзерная кофеварка', 'AeroPress', 'Воронка'];
                        foreach ($grinds as $grind):
                            ?>
                            <option><?= esc_html($grind) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Количество
                    <input type="number" min="1" value="1" name="quantity">
                </label>
                <a class="button" href="<?= esc_url(route_url('cart')) ?>">В корзину</a>
            </form>
        </div>
    </section>
<?php endwhile; ?>

<?php get_footer(); ?>
