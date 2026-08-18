(function () {
    'use strict';

    var banner = document.querySelector('[data-cookie-consent]');
    if (!banner) return;

    var version = banner.getAttribute('data-cookie-version') || '1';
    var cookieName = 'rb_cookie_consent';
    var expectedPrefix = encodeURIComponent(version) + ':';

    function readConsent() {
        var cookies = document.cookie ? document.cookie.split('; ') : [];
        for (var index = 0; index < cookies.length; index += 1) {
            var parts = cookies[index].split('=');
            if (parts.shift() === cookieName) return parts.join('=');
        }
        return '';
    }

    function saveConsent(choice) {
        var secure = window.location.protocol === 'https:' ? '; Secure' : '';
        var value = expectedPrefix + choice;
        document.cookie = cookieName + '=' + value + '; Max-Age=15552000; Path=/; SameSite=Lax' + secure;
        document.documentElement.dataset.cookieConsent = choice;
        banner.classList.remove('is-visible');
        window.setTimeout(function () {
            banner.hidden = true;
        }, 280);
        window.dispatchEvent(new CustomEvent('rb:cookie-consent', { detail: { choice: choice, version: version } }));
        if (choice === 'all') loadAllEmbeds();
    }

    function loadEmbed(iframe) {
        if (!iframe || iframe.getAttribute('src')) return;
        var source = iframe.getAttribute('data-src');
        if (!source) return;
        iframe.setAttribute('src', source);
        var wrapper = iframe.closest('[data-cookie-embed-wrap]');
        if (wrapper) wrapper.classList.add('is-loaded');
    }

    function loadAllEmbeds() {
        document.querySelectorAll('iframe[data-cookie-embed]').forEach(loadEmbed);
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cookie-embed-load]');
        if (!button) return;
        var wrapper = button.closest('[data-cookie-embed-wrap]');
        loadEmbed(wrapper ? wrapper.querySelector('iframe[data-cookie-embed]') : null);
    });

    var saved = readConsent();
    if (saved.indexOf(expectedPrefix) === 0) {
        var savedChoice = saved.slice(expectedPrefix.length);
        document.documentElement.dataset.cookieConsent = savedChoice;
        if (savedChoice === 'all') loadAllEmbeds();
        return;
    }

    banner.hidden = false;
    window.requestAnimationFrame(function () {
        banner.classList.add('is-visible');
    });

    banner.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cookie-choice]');
        if (!button) return;
        saveConsent(button.getAttribute('data-cookie-choice') === 'all' ? 'all' : 'necessary');
    });
}());
