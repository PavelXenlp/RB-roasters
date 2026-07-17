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
