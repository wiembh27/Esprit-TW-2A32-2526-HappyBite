(function () {
    'use strict';

    var cfg = window.HB_I18N || {};
    var strings = cfg.strings || {};

    function applyDocumentLang() {
        var lang = cfg.lang || 'fr';
        var dir = cfg.dir || 'ltr';
        document.documentElement.setAttribute('lang', lang);
        document.documentElement.setAttribute('dir', dir);
    }

    function applyDocumentMode() {
        var mode = cfg.mode === 'dark' ? 'dark' : 'light';
        if (typeof window.hbApplyTheme === 'function') {
            window.hbApplyTheme(mode);
        } else {
            document.documentElement.setAttribute('data-hb-mode', mode);
            document.documentElement.setAttribute('data-bs-theme', mode === 'dark' ? 'dark' : 'light');
        }
    }

    function t(key) {
        if (strings && strings[key]) {
            return strings[key];
        }
        return key;
    }

    function applyNodes() {
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            if (!key) {
                return;
            }
            var val = t(key);
            if (el.hasAttribute('data-i18n-placeholder')) {
                el.setAttribute('placeholder', val);
            } else if (el.hasAttribute('data-i18n-html')) {
                el.innerHTML = val;
            } else if (el.hasAttribute('data-i18n-aria')) {
                el.setAttribute('aria-label', val);
            } else {
                el.textContent = val;
            }
        });
        document.querySelectorAll('[data-i18n-title]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-title');
            if (key) {
                el.setAttribute('title', t(key));
            }
        });
    }

    window.hbI18nT = t;
    window.hbI18nApply = function () {
        applyDocumentLang();
        applyDocumentMode();
        applyNodes();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.hbI18nApply);
    } else {
        window.hbI18nApply();
    }
})();
