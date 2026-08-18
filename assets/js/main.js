const menuButton = document.querySelector('.menu-toggle');
const menuPanel = document.querySelector('.menu-panel');
const preloader = document.querySelector('[data-preloader]');
const preloaderBar = document.querySelector('[data-preloader-bar]');
const preloaderPercent = document.querySelector('[data-preloader-percent]');
const customSelects = document.querySelectorAll('[data-custom-select]');
const authModal = document.querySelector('[data-auth-modal]');
const authModalOpen = document.querySelector('[data-auth-modal-open]');
const authModalClose = document.querySelectorAll('[data-auth-modal-close]');

if (preloader && preloaderBar && preloaderPercent) {
    const storageKey = 'rb_preloader_seen';
    const hasSeenPreloader = () => {
        try {
            return window.localStorage.getItem(storageKey) === '1';
        } catch (error) {
            return true;
        }
    };

    const markPreloaderSeen = () => {
        try {
            window.localStorage.setItem(storageKey, '1');
        } catch (error) {
            document.documentElement.classList.add('has-seen-preloader');
        }
    };

    if (hasSeenPreloader()) {
        preloader.classList.add('is-hidden');
        document.documentElement.classList.add('has-seen-preloader');
        document.documentElement.classList.remove('is-loading');
    } else {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let progress = 0;

        const setProgress = (value) => {
            progress = Math.min(100, Math.max(progress, value));
            preloaderBar.style.width = `${progress}%`;
            preloaderPercent.textContent = `${Math.round(progress)}%`;
        };

        const hidePreloader = () => {
            setProgress(100);
            markPreloaderSeen();
            window.setTimeout(() => {
                preloader.classList.add('is-hidden');
                document.documentElement.classList.remove('is-loading');
            }, reduceMotion ? 80 : 360);
        };

        document.documentElement.classList.add('is-loading');
        setProgress(7);

        const timer = window.setInterval(() => {
            if (document.readyState === 'complete') {
                window.clearInterval(timer);
                hidePreloader();
                return;
            }

            const nextStep = progress < 55 ? 9 : progress < 82 ? 4 : 1;
            setProgress(Math.min(94, progress + nextStep));
        }, reduceMotion ? 40 : 120);

        window.addEventListener('load', () => {
            window.clearInterval(timer);
            hidePreloader();
        });

        window.setTimeout(() => {
            if (!preloader.classList.contains('is-hidden')) {
                hidePreloader();
            }
        }, 5000);
    }
}

if (menuButton && menuPanel) {
    const closeMenu = () => {
        menuButton.setAttribute('aria-expanded', 'false');
        menuPanel.classList.remove('is-open');
        menuPanel.setAttribute('aria-hidden', 'true');
    };

    const openMenu = () => {
        menuButton.setAttribute('aria-expanded', 'true');
        menuPanel.classList.add('is-open');
        menuPanel.setAttribute('aria-hidden', 'false');
    };

    menuButton.addEventListener('click', () => {
        const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    document.addEventListener('click', (event) => {
        if (menuPanel.classList.contains('is-open') && !event.target.closest('.site-header')) {
            closeMenu();
        }
    });

    window.addEventListener('scroll', closeMenu, { passive: true });

    menuPanel.addEventListener('click', (event) => {
        if (event.target.matches('a')) {
            closeMenu();
        }
    });
}

if (customSelects.length) {
    const closeCustomSelect = (select) => {
        const button = select.querySelector('[data-custom-select-button]');

        select.classList.remove('is-open');
        button?.setAttribute('aria-expanded', 'false');
    };

    const closeAllCustomSelects = (except = null) => {
        customSelects.forEach((select) => {
            if (select !== except) {
                closeCustomSelect(select);
            }
        });
    };

    customSelects.forEach((select) => {
        const input = select.querySelector('[data-custom-select-input]');
        const button = select.querySelector('[data-custom-select-button]');
        const label = button?.querySelector('span');
        const options = select.querySelectorAll('[data-value]');

        if (!input || !button) {
            return;
        }

        button.addEventListener('click', (event) => {
            event.stopPropagation();

            const shouldOpen = !select.classList.contains('is-open');
            closeAllCustomSelects(select);
            select.classList.toggle('is-open', shouldOpen);
            button.setAttribute('aria-expanded', String(shouldOpen));
        });

        options.forEach((option) => {
            option.addEventListener('click', (event) => {
                event.stopPropagation();

                input.value = option.dataset.value || '';

                if (label) {
                    label.textContent = option.textContent.trim();
                }

                options.forEach((item) => {
                    item.classList.remove('is-selected');
                    item.setAttribute('aria-selected', 'false');
                });

                option.classList.add('is-selected');
                option.setAttribute('aria-selected', 'true');
                input.dispatchEvent(new Event('change', { bubbles: true }));
                closeCustomSelect(select);
            });
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-custom-select]')) {
            closeAllCustomSelects();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllCustomSelects();
        }
    });
}

document.querySelectorAll('[data-catalog-search]').forEach((searchForm) => {
    const input = searchForm.querySelector('[data-catalog-search-input]');
    const resetButton = searchForm.querySelector('[data-catalog-search-reset]');
    const popover = searchForm.querySelector('[data-catalog-search-popover]');
    const recentSection = searchForm.querySelector('[data-catalog-search-recent]');
    const recentList = searchForm.querySelector('[data-catalog-search-recent-list]');
    const clearHistoryButton = searchForm.querySelector('[data-catalog-search-clear-history]');
    const suggestionsSection = searchForm.querySelector('[data-catalog-search-suggestions]');
    const suggestionsList = searchForm.querySelector('[data-catalog-search-suggestion-list]');
    const status = searchForm.querySelector('[data-catalog-search-status]');
    const storageKey = 'rb_catalog_recent_searches';
    let searchTimer = 0;
    let activeRequest = null;

    if (!input || !popover || !recentSection || !recentList || !suggestionsSection || !suggestionsList || !status) {
        return;
    }

    const getRecentSearches = () => {
        try {
            const saved = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
            return Array.isArray(saved) ? saved.filter((item) => typeof item === 'string' && item.trim()).slice(0, 3) : [];
        } catch (error) {
            return [];
        }
    };

    const saveRecentSearch = (query) => {
        const normalized = query.trim();
        if (!normalized) {
            return;
        }

        const recent = getRecentSearches().filter((item) => item.toLocaleLowerCase('ru') !== normalized.toLocaleLowerCase('ru'));
        recent.unshift(normalized);
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(recent.slice(0, 3)));
        } catch (error) {
            // Search remains usable when browser storage is unavailable.
        }
    };

    const openPopover = () => {
        popover.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const closePopover = () => {
        popover.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    };

    const updateResetButton = () => {
        if (resetButton) {
            resetButton.hidden = input.value.length === 0;
        }
    };

    const renderRecentSearches = () => {
        const recent = getRecentSearches();
        recentList.replaceChildren();
        recentSection.hidden = recent.length === 0;

        recent.forEach((query) => {
            const button = document.createElement('button');
            const label = document.createElement('span');
            button.type = 'button';
            button.className = 'catalog-search__recent-item';
            button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>';
            label.textContent = query;
            button.append(label);
            button.addEventListener('click', () => {
                input.value = query;
                saveRecentSearch(query);
                searchForm.requestSubmit();
            });
            recentList.append(button);
        });

        return recent.length > 0;
    };

    const renderSuggestions = (products, query) => {
        suggestionsList.replaceChildren();
        suggestionsSection.hidden = products.length === 0;

        products.forEach((product) => {
            const link = document.createElement('a');
            const image = document.createElement('img');
            const copy = document.createElement('span');
            const title = document.createElement('strong');
            const category = document.createElement('small');

            link.className = 'catalog-search__suggestion';
            link.href = product.url;
            image.src = product.image;
            image.alt = '';
            image.loading = 'lazy';
            title.textContent = product.title;
            category.textContent = product.category || 'Товар';
            copy.append(title, category);
            link.append(image, copy);
            link.addEventListener('click', () => saveRecentSearch(query));
            suggestionsList.append(link);
        });
    };

    const loadSuggestions = async (query) => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        status.textContent = 'Ищем товары...';
        recentSection.hidden = true;
        suggestionsSection.hidden = true;
        openPopover();

        try {
            const endpoint = new URL(searchForm.dataset.searchEndpoint, window.location.origin);
            endpoint.searchParams.set('q', query);
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                signal: activeRequest.signal,
            });
            const products = await response.json().catch(() => []);
            if (!response.ok) {
                throw new Error('Не удалось загрузить подсказки.');
            }

            renderSuggestions(Array.isArray(products) ? products : [], query);
            status.textContent = products.length ? '' : 'По этому запросу товаров не найдено.';
        } catch (error) {
            if (error.name !== 'AbortError') {
                status.textContent = error.message;
                suggestionsSection.hidden = true;
            }
        }
    };

    input.addEventListener('focus', () => {
        const query = input.value.trim();
        if (query.length >= 2) {
            loadSuggestions(query);
        } else if (renderRecentSearches()) {
            status.textContent = '';
            suggestionsSection.hidden = true;
            openPopover();
        }
    });

    input.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        updateResetButton();
        status.textContent = '';
        const query = input.value.trim();

        if (query.length < 2) {
            activeRequest?.abort();
            suggestionsSection.hidden = true;
            if (renderRecentSearches()) {
                openPopover();
            } else {
                closePopover();
            }
            return;
        }

        searchTimer = window.setTimeout(() => loadSuggestions(query), 280);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePopover();
        }
        if (event.key === 'ArrowDown' && !popover.hidden) {
            const firstItem = popover.querySelector('a, .catalog-search__recent-item');
            if (firstItem) {
                event.preventDefault();
                firstItem.focus();
            }
        }
    });

    popover.addEventListener('keydown', (event) => {
        if (!['ArrowDown', 'ArrowUp'].includes(event.key)) {
            return;
        }
        const items = [...popover.querySelectorAll('a, .catalog-search__recent-item')];
        const currentIndex = items.indexOf(document.activeElement);
        if (currentIndex < 0) {
            return;
        }
        event.preventDefault();
        const offset = event.key === 'ArrowDown' ? 1 : -1;
        items[(currentIndex + offset + items.length) % items.length]?.focus();
    });

    resetButton?.addEventListener('click', () => {
        activeRequest?.abort();
        input.value = '';
        updateResetButton();
        status.textContent = '';
        suggestionsSection.hidden = true;
        if (renderRecentSearches()) {
            openPopover();
        } else {
            closePopover();
        }
        input.focus();
    });

    clearHistoryButton?.addEventListener('click', () => {
        try {
            window.localStorage.removeItem(storageKey);
        } catch (error) {
            // Nothing else is required when browser storage is unavailable.
        }
        renderRecentSearches();
        if (input.value.trim().length < 2) {
            closePopover();
        }
    });

    searchForm.addEventListener('submit', (event) => {
        const query = input.value.trim();
        if (!query) {
            event.preventDefault();
            input.focus();
            return;
        }
        saveRecentSearch(query);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-catalog-search]')) {
            closePopover();
        }
    });

    updateResetButton();
    if (input.value.trim()) {
        saveRecentSearch(input.value);
    }
});

document.querySelectorAll('[data-quantity-change]').forEach((button) => {
    button.addEventListener('click', () => {
        const control = button.closest('.quantity-control');
        const input = control?.querySelector('input[type="number"]');
        if (!input) {
            return;
        }

        const minimum = Number(input.min || 1);
        const maximum = input.max ? Number(input.max) : Number.POSITIVE_INFINITY;
        const current = Number(input.value || minimum);
        const next = Math.min(maximum, Math.max(minimum, current + Number(button.dataset.quantityChange || 0)));
        input.value = String(next);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

const headerCart = document.querySelector('[data-header-cart]');
const renderMiniCart = (cart) => {
    if (!cart) return;
    const badge = document.querySelector('[data-header-cart-count]');
    const count = Number(cart.count || 0);
    if (badge) {
        badge.textContent = String(count);
        badge.hidden = count < 1;
    }

    const countLabel = headerCart?.querySelector('[data-mini-cart-count]');
    const totalLabel = headerCart?.querySelector('[data-mini-cart-total]');
    const itemsContainer = headerCart?.querySelector('[data-mini-cart-items]');
    if (countLabel) countLabel.textContent = `${count} шт.`;
    if (totalLabel) totalLabel.textContent = cart.total_formatted || '0 ₽';
    if (!itemsContainer) return;

    const fragment = document.createDocumentFragment();
    (cart.items || []).forEach((item) => {
        const link = document.createElement('a');
        link.className = 'mini-cart__item';
        link.href = item.url || cart.cart_url || '#';

        const image = document.createElement('img');
        image.src = item.image || '';
        image.alt = '';

        const description = document.createElement('span');
        const title = document.createElement('strong');
        const details = document.createElement('small');
        title.textContent = item.title || 'Товар';
        details.textContent = `${item.details || ''} · ${item.quantity || 1} шт.`;
        description.append(title, details);

        const price = document.createElement('b');
        price.textContent = item.line_total_formatted || '';
        link.append(image, description, price);
        fragment.append(link);
    });

    if (!fragment.childNodes.length) {
        const empty = document.createElement('p');
        empty.className = 'mini-cart__empty';
        empty.textContent = 'Корзина пока пуста';
        fragment.append(empty);
    }
    itemsContainer.replaceChildren(fragment);
};

let cartToastTimer = 0;
const showCartToast = (message, product = '', isError = false) => {
    let toast = document.querySelector('[data-cart-toast]');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'cart-toast';
        toast.dataset.cartToast = '';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.innerHTML = '<span class="cart-toast__icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span><span><strong data-cart-toast-message></strong><small data-cart-toast-product></small></span><a data-cart-toast-link>Открыть корзину</a>';
        document.body.append(toast);
    }
    toast.classList.toggle('is-error', isError);
    toast.querySelector('[data-cart-toast-message]').textContent = message;
    toast.querySelector('[data-cart-toast-product]').textContent = product;
    const link = toast.querySelector('[data-cart-toast-link]');
    link.href = document.querySelector('[data-header-cart] .icon-btn')?.href || '/cart/';
    window.clearTimeout(cartToastTimer);
    window.requestAnimationFrame(() => toast.classList.add('is-visible'));
    cartToastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), isError ? 4200 : 3000);
};

document.querySelectorAll('[data-add-to-cart-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        formData.set('rb_cart_ajax', '1');
        if (submit) {
            submit.disabled = true;
            submit.setAttribute('aria-busy', 'true');
        }

        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.success) {
                throw new Error(payload?.data?.message || 'Не удалось добавить товар в корзину.');
            }
            renderMiniCart(payload.data);
            showCartToast(payload.data.message || 'Товар добавлен в корзину', payload.data.added_title || '');
            headerCart?.classList.add('is-peek');
            window.setTimeout(() => headerCart?.classList.remove('is-peek'), 2200);
        } catch (error) {
            showCartToast(error.message || 'Не удалось добавить товар в корзину.', '', true);
        } finally {
            if (submit) {
                submit.disabled = false;
                submit.removeAttribute('aria-busy');
            }
        }
    });
});

document.querySelectorAll('[data-cart-form]').forEach((form) => {
    const inputs = Array.from(form.querySelectorAll('.quantity-control input[type="number"]'));
    const subtotal = form.querySelector('[data-cart-subtotal]');
    const checkoutSubtotal = document.querySelector('[data-checkout-subtotal]');
    const itemsCount = document.querySelector('[data-cart-items-count]');
    const headerCount = document.querySelector('[data-header-cart-count]');
    const saveStatus = form.querySelector('[data-cart-save-status]');
    let saveTimer = 0;
    let statusTimer = 0;
    let isSaving = false;
    let saveQueued = false;

    const formatPrice = (value) => `${new Intl.NumberFormat('ru-RU').format(Math.round(value))} ₽`;
    const quantityLabel = (count) => {
        const modulo = count % 100;
        if (count % 10 === 1 && modulo !== 11) return 'товар';
        if (count % 10 >= 2 && count % 10 <= 4 && (modulo < 12 || modulo > 14)) return 'товара';
        return 'товаров';
    };

    const quantityValue = (input) => Math.max(1, Number.parseInt(input.value, 10) || 1);
    const renderCartTotals = () => {
        let total = 0;
        let count = 0;

        inputs.forEach((input) => {
            const item = input.closest('[data-cart-item]');
            const quantity = quantityValue(input);
            const unitPrice = Number(item?.dataset.unitPrice || 0);
            const lineTotal = item?.querySelector('[data-cart-line-total]');
            total += unitPrice * quantity;
            count += quantity;
            if (lineTotal) lineTotal.textContent = formatPrice(unitPrice * quantity);
        });

        if (subtotal) subtotal.textContent = formatPrice(total);
        if (checkoutSubtotal) checkoutSubtotal.textContent = formatPrice(total);
        if (itemsCount) itemsCount.textContent = `${count} ${quantityLabel(count)}`;
        if (headerCount) {
            headerCount.textContent = String(count);
            headerCount.hidden = count < 1;
        }
        document.dispatchEvent(new CustomEvent('rb:cart-updated', { detail: { total, count } }));
    };

    const setSaveStatus = (message, isError = false) => {
        if (!saveStatus) return;
        window.clearTimeout(statusTimer);
        saveStatus.textContent = message;
        saveStatus.classList.toggle('is-error', isError);
        if (message && !isError) {
            statusTimer = window.setTimeout(() => {
                saveStatus.textContent = '';
            }, 1600);
        }
    };

    const saveCart = async () => {
        if (isSaving) {
            saveQueued = true;
            return;
        }

        isSaving = true;
        saveQueued = false;
        form.classList.add('is-saving');
        setSaveStatus('Сохраняем...');

        const formData = new FormData(form);
        formData.set('rb_cart_ajax', '1');

        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload?.data?.message || 'Не удалось обновить корзину.');
            }
            Object.entries(payload.data.items || {}).forEach(([key, serverItem]) => {
                const item = form.querySelector(`[data-cart-item][data-cart-key="${CSS.escape(key)}"]`);
                const input = item?.querySelector('.quantity-control input[type="number"]');
                if (!item || !input) return;
                input.value = String(serverItem.quantity);
                item.dataset.unitPrice = String(serverItem.unit_price);
            });
            renderCartTotals();
            renderMiniCart(payload.data.mini_cart);
            setSaveStatus(payload.data.notice || 'Сохранено', Boolean(payload.data.notice));
        } catch (error) {
            setSaveStatus(error.message || 'Не удалось обновить корзину.', true);
        } finally {
            isSaving = false;
            form.classList.remove('is-saving');
            if (saveQueued) saveCart();
        }
    };

    const scheduleSave = () => {
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(saveCart, 350);
    };

    inputs.forEach((input) => {
        input.addEventListener('input', () => {
            if (input.value === '' || Number(input.value) < 1) return;
            renderCartTotals();
            scheduleSave();
        });
        input.addEventListener('change', () => {
            input.value = String(quantityValue(input));
            renderCartTotals();
            scheduleSave();
        });
    });
});

document.querySelectorAll('[data-delivery-choice]').forEach((choice) => {
    const methods = choice.querySelectorAll('input[name="rb_delivery_method"]');
    const panels = choice.querySelectorAll('[data-delivery-panel]');
    const checkout = choice.closest('.checkout-panel');
    const deliveryPrice = checkout?.querySelector('[data-delivery-price]');
    const orderTotal = checkout?.querySelector('[data-order-total]');
    const discountRow = checkout?.querySelector('[data-discount-row]');
    const discountPrice = checkout?.querySelector('[data-discount-price]');
    const cdekPicker = choice.querySelector('[data-cdek-checkout]');
    const promocodeInput = checkout?.querySelector('input[name="rb_promocode"]');
    const promocodeStatus = checkout?.querySelector('[data-promocode-status]');
    let cartTotal = Number(choice.dataset.cartTotal || cdekPicker?.dataset.cartTotal || 0);
    const loyaltyPercent = Number(choice.dataset.loyaltyPercent || 0);
    let quotedDiscount = null;
    let discountTimer = 0;

    const formatPrice = (value) => `${new Intl.NumberFormat('ru-RU').format(Math.round(value))} ₽`;
    const updateTotal = (shippingCost = 0) => {
        const selectedMethod = choice.querySelector('input[name="rb_delivery_method"]:checked')?.value;
        const pickupPercent = selectedMethod === 'pickup_production' ? 5 : 0;
        const discount = quotedDiscount === null
            ? Math.round(cartTotal * (loyaltyPercent + pickupPercent) / 100)
            : quotedDiscount;
        if (deliveryPrice) {
            deliveryPrice.textContent = shippingCost > 0 ? formatPrice(shippingCost) : 'Бесплатно';
        }
        if (discountRow) discountRow.hidden = discount <= 0;
        if (discountPrice) discountPrice.textContent = `−${formatPrice(discount)}`;
        if (orderTotal) {
            orderTotal.textContent = formatPrice(cartTotal - discount + shippingCost);
        }
    };

    const requestDiscountQuote = async () => {
        if (!choice.dataset.discountPath) return;
        const selectedMethod = choice.querySelector('input[name="rb_delivery_method"]:checked')?.value || 'pickup_cafe';
        try {
            const response = await fetch(choice.dataset.discountPath, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-RB-Nonce': choice.dataset.discountNonce || '',
                },
                body: JSON.stringify({ delivery_method: selectedMethod, promocode: promocodeInput?.value || '' }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Промокод не применен');
            quotedDiscount = Number(payload.discount_total || 0);
            if (promocodeStatus) promocodeStatus.textContent = payload.message || '';
        } catch (error) {
            quotedDiscount = null;
            if (promocodeStatus) promocodeStatus.textContent = error.message || 'проверьте код';
        }
        const shippingCost = selectedMethod === 'cdek'
            ? Number(cdekPicker?.querySelector('[data-cdek-field="cost"]')?.value || 0)
            : 0;
        updateTotal(shippingCost);
    };

    const scheduleDiscountQuote = () => {
        window.clearTimeout(discountTimer);
        quotedDiscount = null;
        discountTimer = window.setTimeout(requestDiscountQuote, 350);
    };

    const showMethod = (method) => {
        panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.deliveryPanel === method));
        const selectedCost = method === 'cdek'
            ? Number(cdekPicker?.querySelector('[data-cdek-field="cost"]')?.value || 0)
            : 0;
        updateTotal(selectedCost);
    };

    methods.forEach((method) => {
        method.addEventListener('change', () => {
            quotedDiscount = null;
            showMethod(method.value);
            requestDiscountQuote();
            if (method.value === 'cdek') {
                window.requestAnimationFrame(() => cityInput?.focus());
            }
        });
        if (method.checked) {
            showMethod(method.value);
        }
    });

    document.addEventListener('rb:cart-updated', (event) => {
        cartTotal = Number(event.detail?.total || 0);
        choice.dataset.cartTotal = String(cartTotal);
        if (cdekPicker) cdekPicker.dataset.cartTotal = String(cartTotal);
        const selectedMethod = choice.querySelector('input[name="rb_delivery_method"]:checked')?.value;
        const selectedCost = selectedMethod === 'cdek'
            ? Number(cdekPicker?.querySelector('[data-cdek-field="cost"]')?.value || 0)
            : 0;
        updateTotal(selectedCost);
        requestDiscountQuote();
    });

    promocodeInput?.addEventListener('input', scheduleDiscountQuote);

    if (!cdekPicker) {
        return;
    }

    const changeButton = cdekPicker.querySelector('[data-cdek-change]');
    const selection = cdekPicker.querySelector('[data-cdek-selection]');
    const officeName = cdekPicker.querySelector('[data-cdek-office]');
    const details = cdekPicker.querySelector('[data-cdek-details]');
    const field = (name) => cdekPicker.querySelector(`[data-cdek-field="${name}"]`);
    const fallback = cdekPicker.querySelector('[data-cdek-fallback]');
    const fallbackStatus = cdekPicker.querySelector('[data-cdek-fallback-status]');
    const cityInput = cdekPicker.querySelector('[data-cdek-city-input]');
    const cityResults = cdekPicker.querySelector('[data-cdek-city-results]');
    const officeSelect = cdekPicker.querySelector('[data-cdek-office-select]');
    const officeButton = cdekPicker.querySelector('[data-cdek-office-button]');
    const officeButtonLabel = officeButton?.querySelector('span');
    const officeList = cdekPicker.querySelector('[data-cdek-office-list]');
    let citySearchTimer = 0;

    const cdekUrl = (action, params = {}) => {
        const url = new URL(cdekPicker.dataset.servicePath, window.location.origin);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
        return url;
    };

    const fetchCdek = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload.message || 'Сервис СДЭК временно недоступен.');
        }
        return payload;
    };

    const applyDeliverySelection = (office, tariff) => {
        field('office_code').value = office.code || '';
        field('city_code').value = office.city_code || '';
        field('tariff_code').value = tariff.tariff_code || '';
        field('tariff_name').value = tariff.tariff_name || 'СДЭК до ПВЗ';
        field('cost').value = Math.round(Number(tariff.delivery_sum || 0));

        officeName.textContent = office.name || `ПВЗ ${office.code || ''}`;
        details.textContent = [office.city, office.address, tariff.tariff_name].filter(Boolean).join(', ');
        selection.hidden = false;
        fallback.hidden = true;
        updateTotal(Number(field('cost').value));
    };

    const showOfficePicker = (message = '') => {
        fallback.hidden = false;
        if (message) {
            fallbackStatus.textContent = message;
            fallbackStatus.classList.add('is-error');
        }
        window.setTimeout(() => cityInput?.focus(), 50);
    };

    const resetDeliverySelection = () => {
        ['office_code', 'city_code', 'tariff_code', 'tariff_name', 'cost'].forEach((name) => {
            field(name).value = name === 'cost' ? '0' : '';
        });
        selection.hidden = true;
        updateTotal(0);
    };

    document.addEventListener('rb:cart-updated', () => {
        const selectedMethod = choice.querySelector('input[name="rb_delivery_method"]:checked')?.value;
        if (selectedMethod === 'cdek' && field('office_code').value) {
            resetDeliverySelection();
            showOfficePicker('Количество товаров изменилось. Выберите пункт повторно для пересчета доставки.');
        }
    });

    const hideOfficeList = () => {
        officeList.hidden = true;
        officeSelect.classList.remove('is-open');
        officeButton?.setAttribute('aria-expanded', 'false');
    };

    const chooseFallbackOffice = async (city, office) => {
        hideOfficeList();
        officeButtonLabel.textContent = office.location?.address_full || office.location?.address || office.name || office.code;
        fallbackStatus.classList.remove('is-error');
        fallbackStatus.textContent = 'Рассчитываем стоимость доставки...';
        const destinationCityCode = Number(office.location?.city_code || city.code);

        try {
            const tariffs = await fetchCdek(cdekPicker.dataset.servicePath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'calculate',
                    to_location: { code: destinationCityCode },
                }),
            });
            const tariffItems = Array.isArray(tariffs)
                ? tariffs
                : (tariffs.tariff_code ? [tariffs] : (tariffs.tariff_codes || tariffs.tariffs || tariffs.data || []));
            const officeTariffs = (Array.isArray(tariffItems) ? tariffItems : [])
                .filter((item) => Number.isFinite(Number(item.delivery_sum)))
                .sort((first, second) => Number(first.delivery_sum) - Number(second.delivery_sum));
            const tariff = officeTariffs.find((item) => Number(item.tariff_code) === 136)
                || officeTariffs.find((item) => Number(item.tariff_code) === 138)
                || officeTariffs.find((item) => /посылка.*склад-склад/i.test(String(item.tariff_name)))
                || officeTariffs.find((item) => /посылка/i.test(String(item.tariff_name)))
                || officeTariffs[0];

            if (!tariff) {
                throw new Error('Для выбранного города не найден доступный тариф до ПВЗ.');
            }

            applyDeliverySelection({
                code: office.code,
                city_code: destinationCityCode,
                city: office.location?.city || city.city,
                name: office.name,
                address: office.location?.address_full || office.location?.address || '',
            }, tariff);
        } catch (error) {
            fallbackStatus.textContent = error.message;
            fallbackStatus.classList.add('is-error');
        }
    };

    const loadFallbackOffices = async (city) => {
        officeButton.disabled = true;
        officeButtonLabel.textContent = 'Загружаем пункты выдачи...';
        officeList.replaceChildren();
        fallbackStatus.classList.remove('is-error');
        fallbackStatus.textContent = '';

        try {
            const offices = await fetchCdek(cdekUrl('offices', {
                city_code: city.code,
                type: 'PVZ',
                is_handout: 'true',
                size: 500,
            }));
            if (!Array.isArray(offices) || !offices.length) {
                throw new Error('В выбранном городе не найдены пункты выдачи СДЭК.');
            }

            offices
                .slice()
                .sort((first, second) => String(first.location?.address || '').localeCompare(String(second.location?.address || ''), 'ru'))
                .forEach((office) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = office.location?.address_full || office.location?.address || office.name || office.code;
                    button.addEventListener('click', () => chooseFallbackOffice(city, office));
                    officeList.append(button);
                });

            officeButton.disabled = false;
            officeButtonLabel.textContent = `Выберите пункт выдачи (${offices.length})`;
        } catch (error) {
            officeButtonLabel.textContent = 'Пункты выдачи не найдены';
            fallbackStatus.textContent = error.message;
            fallbackStatus.classList.add('is-error');
        }
    };

    const searchFallbackCities = async (query) => {
        cityResults.hidden = false;
        cityResults.replaceChildren();
        fallbackStatus.classList.remove('is-error');
        fallbackStatus.textContent = 'Ищем города...';

        try {
            const cities = await fetchCdek(cdekUrl('cities', { city: query }));
            if (!Array.isArray(cities) || !cities.length) {
                throw new Error('Город не найден. Уточните название.');
            }

            cities.forEach((city) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = [city.city, city.region, city.sub_region].filter(Boolean).join(', ');
                button.addEventListener('click', () => {
                    cityInput.value = button.textContent;
                    cityResults.hidden = true;
                    fallbackStatus.textContent = '';
                    loadFallbackOffices(city);
                });
                cityResults.append(button);
            });
            fallbackStatus.textContent = '';
        } catch (error) {
            fallbackStatus.textContent = error.message;
            fallbackStatus.classList.add('is-error');
        }
    };

    changeButton?.addEventListener('click', () => {
        resetDeliverySelection();
        showOfficePicker();
    });
    officeButton?.addEventListener('click', () => {
        if (officeButton.disabled) {
            return;
        }
        const shouldOpen = officeList.hidden;
        officeList.hidden = !shouldOpen;
        officeSelect.classList.toggle('is-open', shouldOpen);
        officeButton.setAttribute('aria-expanded', String(shouldOpen));
    });
    cityInput?.addEventListener('input', () => {
        window.clearTimeout(citySearchTimer);
        hideOfficeList();
        officeButton.disabled = true;
        officeButtonLabel.textContent = 'Сначала выберите город';
        const query = cityInput.value.trim();
        if (query.length < 2) {
            cityResults.hidden = true;
            fallbackStatus.textContent = '';
            return;
        }
        citySearchTimer = window.setTimeout(() => searchFallbackCities(query), 350);
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-cdek-office-select]')) {
            hideOfficeList();
        }
        if (!event.target.closest('.cdek-city-search') && !event.target.closest('[data-cdek-city-results]')) {
            cityResults.hidden = true;
        }
    });

    checkout?.addEventListener('submit', (event) => {
        const selectedMethod = checkout.querySelector('input[name="rb_delivery_method"]:checked')?.value;
        if (selectedMethod === 'cdek' && !field('office_code').value) {
            event.preventDefault();
            showOfficePicker('Перед оформлением выберите пункт выдачи СДЭК.');
        }
    });
});

if (authModal && authModalOpen) {
    const closeAuthModal = () => {
        authModal.classList.remove('is-open');
        authModal.setAttribute('aria-hidden', 'true');
        authModalOpen.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('has-open-modal');
    };

    const openAuthModal = () => {
        authModal.classList.add('is-open');
        authModal.setAttribute('aria-hidden', 'false');
        authModalOpen.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('has-open-modal');
    };

    authModalOpen.addEventListener('click', (event) => {
        event.stopPropagation();
        openAuthModal();
    });

    authModalClose.forEach((button) => {
        button.addEventListener('click', closeAuthModal);
    });

    authModal.addEventListener('click', (event) => {
        if (event.target.matches('a')) {
            closeAuthModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && authModal.classList.contains('is-open')) {
            closeAuthModal();
        }
    });
}

document.querySelectorAll('[data-business-benefits]').forEach((slider) => {
    const track = slider.querySelector('[data-business-benefits-track]');
    const cards = Array.from(track?.children || []);
    const previousButton = slider.querySelector('[data-business-benefits-prev]');
    const nextButton = slider.querySelector('[data-business-benefits-next]');
    const status = slider.querySelector('[data-business-benefits-status]');

    if (!track || !cards.length || !previousButton || !nextButton || !status) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let frame = 0;
    const cardPosition = (card) => card.offsetLeft - cards[0].offsetLeft;

    const currentIndex = () => cards.reduce((closestIndex, card, index) => {
        const currentDistance = Math.abs(cardPosition(cards[closestIndex]) - track.scrollLeft);
        const nextDistance = Math.abs(cardPosition(card) - track.scrollLeft);
        return nextDistance < currentDistance ? index : closestIndex;
    }, 0);

    const updateControls = () => {
        const index = currentIndex();
        status.textContent = `${index + 1} / ${cards.length}`;
        previousButton.disabled = index === 0;
        nextButton.disabled = index === cards.length - 1;
    };

    const goToCard = (index) => {
        const target = cards[Math.max(0, Math.min(cards.length - 1, index))];
        track.scrollTo({
            left: cardPosition(target),
            behavior: reduceMotion ? 'auto' : 'smooth',
        });
    };

    previousButton.addEventListener('click', () => goToCard(currentIndex() - 1));
    nextButton.addEventListener('click', () => goToCard(currentIndex() + 1));

    track.addEventListener('scroll', () => {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(updateControls);
    }, { passive: true });

    window.addEventListener('resize', updateControls);
    updateControls();
});

document.querySelectorAll('[data-service-directions]').forEach((slider) => {
    const track = slider.querySelector('[data-service-directions-track]');
    const cards = Array.from(track?.children || []);
    const previousButton = slider.querySelector('[data-service-directions-prev]');
    const nextButton = slider.querySelector('[data-service-directions-next]');
    const status = slider.querySelector('[data-service-directions-status]');

    if (!track || !cards.length || !previousButton || !nextButton || !status) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let frame = 0;
    const cardPosition = (card) => card.offsetLeft - cards[0].offsetLeft;

    const visibleCards = () => {
        const cardWidth = cards[0].getBoundingClientRect().width;
        const gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
        return Math.max(1, Math.round((track.clientWidth + gap) / (cardWidth + gap)));
    };

    const lastIndex = () => Math.max(0, cards.length - visibleCards());

    const currentIndex = () => cards.slice(0, lastIndex() + 1).reduce((closestIndex, card, index) => {
        const currentDistance = Math.abs(cardPosition(cards[closestIndex]) - track.scrollLeft);
        const nextDistance = Math.abs(cardPosition(card) - track.scrollLeft);
        return nextDistance < currentDistance ? index : closestIndex;
    }, 0);

    const updateControls = () => {
        const index = currentIndex();
        const finalIndex = lastIndex();
        status.textContent = `${index + 1} / ${finalIndex + 1}`;
        previousButton.disabled = index === 0;
        nextButton.disabled = index === finalIndex;
    };

    const goToCard = (index) => {
        const targetIndex = Math.max(0, Math.min(lastIndex(), index));
        track.scrollTo({
            left: cardPosition(cards[targetIndex]),
            behavior: reduceMotion ? 'auto' : 'smooth',
        });
    };

    previousButton.addEventListener('click', () => goToCard(currentIndex() - 1));
    nextButton.addEventListener('click', () => goToCard(currentIndex() + 1));

    track.addEventListener('scroll', () => {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(updateControls);
    }, { passive: true });

    window.addEventListener('resize', updateControls);
    updateControls();
});
