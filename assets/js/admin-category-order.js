jQuery(function ($) {
    const list = $('#the-list');
    if (!list.length || typeof rbCategoryOrder === 'undefined') {
        return;
    }

    const status = $('<p class="rb-category-order-status" role="status" aria-live="polite">Перетаскивайте строки за иконку, чтобы изменить порядок категорий.</p>');
    $('.tablenav.top').after(status);

    const fixHelperWidths = function (event, row) {
        row.children().each(function () {
            $(this).width($(this).width());
        });
        return row;
    };

    list.sortable({
        items: '> tr:not(.no-items)',
        axis: 'y',
        handle: '[data-rb-category-drag]',
        cancel: 'input, textarea, select, option, a',
        helper: fixHelperWidths,
        placeholder: 'rb-category-order-placeholder',
        forcePlaceholderSize: true,
        update() {
            const termIds = list.children('tr').map(function () {
                const match = this.id.match(/^tag-(\d+)$/);
                return match ? Number(match[1]) : null;
            }).get().filter(Boolean);

            status.removeClass('is-error is-success').text('Сохраняем порядок...');

            $.post(rbCategoryOrder.ajaxUrl, {
                action: 'rb_save_category_order',
                nonce: rbCategoryOrder.nonce,
                termIds,
            }).done((response) => {
                if (!response.success) {
                    status.addClass('is-error').text(response.data?.message || 'Не удалось сохранить порядок.');
                    return;
                }
                status.addClass('is-success').text('Порядок категорий сохранен.');
            }).fail(() => {
                status.addClass('is-error').text('Не удалось сохранить порядок. Обновите страницу и попробуйте снова.');
            });
        },
    });
});
