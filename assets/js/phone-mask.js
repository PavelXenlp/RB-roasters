(() => {
    const selector = '[data-phone-mask]';
    const errorMessage = 'Введите российский номер в формате +7 (999) 123-45-67';

    const normalize = (value) => {
        const rawValue = String(value || '');
        let digits = rawValue.replace(/\D/g, '');

        if (/^\s*8/.test(rawValue)) {
            digits = `7${digits.slice(1)}`;
        } else if (digits.length === 10) {
            digits = `7${digits}`;
        } else if (digits && !digits.startsWith('7')) {
            digits = `7${digits}`;
        }

        // When +7 is already visible, users often type the country code once more.
        // A Russian national number cannot start with 7, so safely collapse it.
        if (digits.startsWith('77')) {
            digits = digits.slice(1);
        }

        return digits.slice(0, 11);
    };

    const format = (value) => {
        if (!String(value || '').replace(/\D/g, '')) {
            return '';
        }

        const national = normalize(value).slice(1);
        let result = '+7';

        if (national.length > 0) result += ` (${national.slice(0, 3)}`;
        if (national.length >= 3) result += ')';
        if (national.length > 3) result += ` ${national.slice(3, 6)}`;
        if (national.length > 6) result += `-${national.slice(6, 8)}`;
        if (national.length > 8) result += `-${national.slice(8, 10)}`;

        return result;
    };

    const validate = (input) => {
        const value = input.value.trim();
        const valid = value === '' || /^7[3489]\d{9}$/.test(normalize(value));
        input.setCustomValidity(valid ? '' : errorMessage);
    };

    const bind = (input) => {
        if (input.dataset.phoneMaskReady === 'true') return;
        input.dataset.phoneMaskReady = 'true';

        if (input.value) input.value = format(input.value);
        validate(input);

        input.addEventListener('input', () => {
            input.value = format(input.value);
            validate(input);
        });
        input.addEventListener('blur', () => validate(input));
        input.addEventListener('invalid', () => validate(input));
    };

    const init = (root = document) => root.querySelectorAll(selector).forEach(bind);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init());
    } else {
        init();
    }
})();
