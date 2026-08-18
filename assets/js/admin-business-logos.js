document.querySelectorAll('[data-business-logos-box]').forEach((box) => {
    const input = box.querySelector('[data-business-logos-input]');
    const list = box.querySelector('[data-business-logos-list]');
    const addButton = box.querySelector('[data-add-business-logos]');

    if (!input || !list || !addButton || !window.wp?.media) {
        return;
    }

    const syncInput = () => {
        input.value = Array.from(list.querySelectorAll('[data-logo-id]'))
            .map((item) => item.dataset.logoId)
            .filter(Boolean)
            .join(',');
    };

    const addPreview = (attachment) => {
        if (!attachment.id || list.querySelector(`[data-logo-id="${attachment.id}"]`)) {
            return;
        }

        const item = document.createElement('div');
        const image = document.createElement('img');
        const removeButton = document.createElement('button');
        const preview = attachment.sizes?.thumbnail?.url || attachment.sizes?.medium?.url || attachment.url;

        item.className = 'rb-business-logos__item';
        item.dataset.logoId = String(attachment.id);
        image.src = preview;
        image.alt = attachment.alt || attachment.title || '';
        removeButton.type = 'button';
        removeButton.dataset.removeLogo = '';
        removeButton.setAttribute('aria-label', 'Удалить логотип');
        removeButton.title = 'Удалить';
        removeButton.textContent = '×';

        item.append(image, removeButton);
        list.append(item);
    };

    let mediaFrame;
    addButton.addEventListener('click', () => {
        if (!mediaFrame) {
            mediaFrame = window.wp.media({
                title: 'Выберите логотипы партнеров',
                button: { text: 'Добавить логотипы' },
                library: { type: 'image' },
                multiple: true,
            });

            mediaFrame.on('select', () => {
                mediaFrame.state().get('selection').toJSON().forEach(addPreview);
                syncInput();
            });
        }

        mediaFrame.open();
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-logo]');
        if (!removeButton) {
            return;
        }

        removeButton.closest('[data-logo-id]')?.remove();
        syncInput();
    });
});
