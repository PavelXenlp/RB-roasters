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
