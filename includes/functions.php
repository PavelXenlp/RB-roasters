<?php
function section_title(string $eyebrow, string $title, ?string $text = null): void
{
    ?>
    <div class="section-title">
        <span><?= htmlspecialchars($eyebrow) ?></span>
        <h2><?= htmlspecialchars($title) ?></h2>
        <?php if ($text): ?><p><?= htmlspecialchars($text) ?></p><?php endif; ?>
    </div>
    <?php
}

function product_card(array $product): void
{
    ?>
    <article class="product-card">
        <a href="<?= route_url('product') ?>" class="product-card__image">
            <img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['title']) ?>">
        </a>
        <div class="product-card__body">
            <a href="<?= route_url('product') ?>" class="product-card__title"><?= htmlspecialchars($product['title']) ?></a>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <span><?= htmlspecialchars($product['process']) ?> способ обработки</span>
            <div class="price-row">
                <del><?= htmlspecialchars($product['old_200']) ?></del>
                <strong><?= htmlspecialchars($product['price_200']) ?></strong>
                <small>за 200 г</small>
            </div>
            <a class="button button--small" href="<?= route_url('product') ?>">Подробнее</a>
        </div>
    </article>
    <?php
}

function rb_product_card_from_post(int $post_id): void
{
    $meta = function_exists('rb_get_product_meta') ? rb_get_product_meta($post_id) : [];
    $image = get_the_post_thumbnail_url($post_id, 'medium_large') ?: rb_asset_url('img/1.webp');
    $descriptors = $meta['rb_descriptors'] ?? get_the_excerpt($post_id);
    $process = $meta['rb_process'] ?? '';
    ?>
    <article class="product-card">
        <a href="<?= esc_url(get_permalink($post_id)) ?>" class="product-card__image">
            <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title($post_id)) ?>">
        </a>
        <div class="product-card__body">
            <a href="<?= esc_url(get_permalink($post_id)) ?>" class="product-card__title"><?= esc_html(get_the_title($post_id)) ?></a>
            <p><?= esc_html($descriptors) ?></p>
            <?php if ($process): ?><span><?= esc_html($process) ?> способ обработки</span><?php endif; ?>
            <div class="price-row">
                <?php if (!empty($meta['rb_old_price_200'])): ?><del><?= esc_html($meta['rb_old_price_200']) ?></del><?php endif; ?>
                <?php if (!empty($meta['rb_price_200'])): ?><strong><?= esc_html($meta['rb_price_200']) ?></strong><?php endif; ?>
                <small>за 200 г</small>
            </div>
            <a class="button button--small" href="<?= esc_url(get_permalink($post_id)) ?>">Подробнее</a>
        </div>
    </article>
    <?php
}

function rb_article_card_from_post(int $post_id): void
{
    $image = get_the_post_thumbnail_url($post_id, 'medium_large') ?: rb_asset_url('img/IMG_1159_1.jpg');
    ?>
    <article class="news-card">
        <a href="<?= esc_url(get_permalink($post_id)) ?>">
            <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title($post_id)) ?>">
        </a>
        <div>
            <span><?= esc_html(get_the_date('j F', $post_id)) ?></span>
            <h3><a href="<?= esc_url(get_permalink($post_id)) ?>"><?= esc_html(get_the_title($post_id)) ?></a></h3>
            <p><?= esc_html(get_the_excerpt($post_id)) ?></p>
        </div>
    </article>
    <?php
}

function rb_training_card_from_post(int $post_id): void
{
    $image = get_the_post_thumbnail_url($post_id, 'medium_large') ?: rb_asset_url('img/IMG_1352_1.jpg');
    $duration = get_post_meta($post_id, 'rb_training_duration', true);
    $price = get_post_meta($post_id, 'rb_training_price', true);
    $points = array_filter(array_map('trim', explode("\n", (string) get_post_meta($post_id, 'rb_training_points', true))));
    $link = get_post_meta($post_id, 'rb_training_link', true) ?: (rb_contacts()['trainer'] ?? '#');
    ?>
    <article class="course-card">
        <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title($post_id)) ?>">
        <div>
            <h3><?= esc_html(get_the_title($post_id)) ?></h3>
            <?php if ($duration || $price): ?>
                <p><b><?= esc_html($duration) ?></b><?php if ($duration && $price): ?><br><?php endif; ?><?= esc_html($price) ?></p>
            <?php endif; ?>
            <?php if ($points): ?>
                <ul><?php foreach ($points as $item): ?><li><?= esc_html($item) ?></li><?php endforeach; ?></ul>
            <?php else: ?>
                <p><?= esc_html(get_the_excerpt($post_id)) ?></p>
            <?php endif; ?>
            <a class="button button--small" href="<?= esc_url($link) ?>">Узнать подробности</a>
        </div>
    </article>
    <?php
}

function rb_custom_select(string $name, array $options, ?string $selected = null, string $label = ''): void
{
    $selected = $selected ?? (string) array_key_first($options);
    $selected_label = $options[$selected] ?? reset($options);
    ?>
    <div class="custom-select" data-custom-select>
        <input type="hidden" name="<?= esc_attr($name) ?>" value="<?= esc_attr($selected) ?>" data-custom-select-input>
        <button class="custom-select__button" type="button" aria-haspopup="listbox" aria-expanded="false" data-custom-select-button>
            <span><?= esc_html($selected_label) ?></span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div class="custom-select__dropdown" role="listbox" aria-label="<?= esc_attr($label ?: $name) ?>" data-custom-select-dropdown>
            <?php foreach ($options as $value => $option_label): ?>
                <button class="custom-select__option<?= (string) $value === (string) $selected ? ' is-selected' : '' ?>" type="button" role="option" aria-selected="<?= (string) $value === (string) $selected ? 'true' : 'false' ?>" data-value="<?= esc_attr($value) ?>">
                    <?= esc_html($option_label) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function first_letter(string $text): string
{
    if (preg_match('/^./u', $text, $match)) {
        return $match[0];
    }

    return substr($text, 0, 1);
}

function brew_icon(string $name): string
{
    $icons = [
        'espresso' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h12v3a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-3Z"/><path d="M16 11h2a2 2 0 0 1 0 4h-2"/><path d="M6 21h10"/><path d="M8 3c-.8.8-.8 1.7 0 2.5s.8 1.7 0 2.5"/><path d="M12 3c-.8.8-.8 1.7 0 2.5s.8 1.7 0 2.5"/></svg>',
        'cezve' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h7l-1 10a3 3 0 0 1-3 3 3 3 0 0 1-3-3L7 8Z"/><path d="M7 8 5 4h12l-2 4"/><path d="M15 11h3a3 3 0 0 0 3-3"/><path d="M10 2v2"/><path d="M12 11v6"/></svg>',
        'moka' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8l-1 6H9L8 3Z"/><path d="M9 9h6l2 10H7L9 9Z"/><path d="M7 19h10"/><path d="M17 11h2a2 2 0 0 1 0 4h-1"/><path d="M10 13h4"/></svg>',
        'dripper' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14l-5 9v4h-4v-4L5 5Z"/><path d="M8 9h8"/><path d="M9 21h6"/><path d="M12 14v4"/></svg>',
        'aeropress' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8v4H8z"/><path d="M9 7h6v10a3 3 0 0 1-6 0V7Z"/><path d="M7 21h10"/><path d="M6 11h12"/><path d="M12 7v10"/></svg>',
    ];

    return $icons[$name] ?? $icons['espresso'];
}
