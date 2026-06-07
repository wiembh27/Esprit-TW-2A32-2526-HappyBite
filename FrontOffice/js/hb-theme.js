(function () {
    'use strict';

    function cfg() {
        return window.HB_I18N || {};
    }

    function normalizeMode(mode) {
        return mode === 'dark' ? 'dark' : 'light';
    }

    function applyMode(mode) {
        mode = normalizeMode(mode);
        document.documentElement.setAttribute('data-hb-mode', mode);
        document.documentElement.setAttribute('data-bs-theme', mode === 'dark' ? 'dark' : 'light');
        if (window.HB_I18N) {
            window.HB_I18N.mode = mode;
        }
        var toggle = document.getElementById('theme-toggle-demo');
        if (toggle) {
            toggle.checked = mode === 'dark';
        }
    }

    function settingsSaveUrl() {
        var c = cfg();
        if (c.settingsSaveUrl) {
            return c.settingsSaveUrl;
        }
        if (window.location.pathname.indexOf('/auth/') !== -1) {
            return '../api/settings_save.php';
        }
        return 'api/settings_save.php';
    }

    function saveTheme(mode) {
        mode = normalizeMode(mode);
        var body = new URLSearchParams();
        body.set('mode', mode);
        body.set('language', cfg().lang || document.documentElement.getAttribute('lang') || 'fr');
        return fetch(settingsSaveUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.ok) {
                    applyMode(data.mode || mode);
                    return data;
                }
                throw new Error((data && data.error) || 'save_failed');
            });
    }

    function bindThemeToggle() {
        var toggle = document.getElementById('theme-toggle-demo');
        if (!toggle || toggle.dataset.hbThemeBound === '1') {
            return;
        }
        toggle.dataset.hbThemeBound = '1';
        toggle.addEventListener('change', function () {
            var next = toggle.checked ? 'dark' : 'light';
            var prev = next === 'dark' ? 'light' : 'dark';
            applyMode(next);
            saveTheme(next).catch(function () {
                applyMode(prev);
                toggle.checked = prev === 'dark';
                if (window.hbAlert) {
                    window.hbAlert(typeof window.hbI18nT === 'function'
                        ? window.hbI18nT('profile.theme_save_error')
                        : 'Could not save theme preference.');
                }
            });
        });
    }

    window.hbApplyTheme = applyMode;
    window.hbSaveTheme = saveTheme;

    applyMode(document.documentElement.getAttribute('data-hb-mode') || cfg().mode || 'light');

    function init() {
        applyMode(cfg().mode || document.documentElement.getAttribute('data-hb-mode') || 'light');
        bindThemeToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
