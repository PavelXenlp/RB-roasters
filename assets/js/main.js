const menuButton = document.querySelector('.menu-toggle');
const menuPanel = document.querySelector('.menu-panel');

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
