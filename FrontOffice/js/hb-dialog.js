(function () {
    'use strict';

    var overlay;
    var titleEl;
    var messageEl;
    var inputEl;
    var btnOk;
    var btnCancel;
    var resolver = null;
    var mode = 'alert';

    function dlgT(key, fallback) {
        if (typeof window.hbI18nT === 'function') {
            var val = window.hbI18nT(key);
            if (val && val !== key) {
                return val;
            }
        }
        return fallback || key;
    }

    function cacheEls() {
        overlay = document.getElementById('hb-dialog-overlay');
        titleEl = document.getElementById('hb-dialog-title');
        messageEl = document.getElementById('hb-dialog-message');
        inputEl = document.getElementById('hb-dialog-input');
        btnOk = document.getElementById('hb-dialog-ok');
        btnCancel = document.getElementById('hb-dialog-cancel');
        return !!(overlay && titleEl && messageEl && inputEl && btnOk && btnCancel);
    }

    function closeDialog(result) {
        if (!overlay) {
            return;
        }
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        inputEl.hidden = true;
        inputEl.value = '';
        var fn = resolver;
        resolver = null;
        if (typeof fn === 'function') {
            fn(result);
        }
    }

    function openDialog(opts) {
        if (!cacheEls()) {
            if (opts.mode === 'confirm') {
                return Promise.resolve(window.confirm(opts.message || ''));
            }
            if (opts.mode === 'prompt') {
                return Promise.resolve(window.prompt(opts.message || '', opts.defaultValue || ''));
            }
            if (typeof window.hbShowActionToast === 'function') {
                window.hbShowActionToast(opts.message || '', 3500);
            } else {
                window.alert(opts.message || '');
            }
            return Promise.resolve(undefined);
        }

        mode = opts.mode || 'alert';
        titleEl.textContent = opts.title || (mode === 'confirm'
            ? dlgT('dialog.confirm_title', 'Confirmation')
            : (mode === 'prompt' ? dlgT('dialog.prompt_title', 'Saisie') : dlgT('dialog.info_title', 'Information')));
        messageEl.textContent = opts.message || '';
        messageEl.hidden = !opts.message;

        if (mode === 'prompt') {
            inputEl.hidden = false;
            inputEl.placeholder = opts.placeholder || '';
            inputEl.value = opts.defaultValue || '';
        } else {
            inputEl.hidden = true;
            inputEl.value = '';
        }

        btnCancel.hidden = mode === 'alert';
        btnOk.textContent = opts.okLabel || (mode === 'confirm'
            ? dlgT('dialog.confirm_ok', 'Confirmer')
            : (mode === 'prompt' ? dlgT('dialog.validate', 'Valider') : dlgT('dialog.ok', 'OK')));
        btnCancel.textContent = opts.cancelLabel || dlgT('dialog.cancel', 'Annuler');

        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');

        if (mode === 'prompt') {
            setTimeout(function () {
                inputEl.focus();
            }, 50);
        }

        return new Promise(function (resolve) {
            resolver = resolve;
        });
    }

    function init() {
        if (!cacheEls()) {
            return;
        }
        btnOk.addEventListener('click', function () {
            if (mode === 'prompt') {
                var v = inputEl.value.trim();
                closeDialog(v === '' ? null : v);
                return;
            }
            closeDialog(mode === 'confirm' ? true : true);
        });
        btnCancel.addEventListener('click', function () {
            closeDialog(mode === 'prompt' ? null : false);
        });
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay && mode !== 'alert') {
                closeDialog(mode === 'prompt' ? null : false);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (!overlay || overlay.hidden) {
                return;
            }
            if (e.key === 'Escape' && mode !== 'alert') {
                closeDialog(mode === 'prompt' ? null : false);
            }
            if (e.key === 'Enter' && mode === 'prompt' && document.activeElement === inputEl) {
                e.preventDefault();
                var val = inputEl.value.trim();
                closeDialog(val === '' ? null : val);
            }
        });
    }

    window.hbAlert = function (message, durationMs) {
        if (typeof window.hbShowActionToast === 'function') {
            window.hbShowActionToast(message, durationMs || 3500);
            return Promise.resolve();
        }
        return openDialog({ mode: 'alert', message: message, title: dlgT('dialog.info_title', 'Information') });
    };

    window.hbConfirm = function (message, options) {
        options = options || {};
        return openDialog({
            mode: 'confirm',
            message: message,
            title: options.title || dlgT('dialog.confirm_title', 'Confirmation'),
            okLabel: options.okLabel,
            cancelLabel: options.cancelLabel
        });
    };

    window.hbPrompt = function (message, options) {
        options = options || {};
        return openDialog({
            mode: 'prompt',
            message: message,
            title: options.title || dlgT('dialog.prompt_title', 'Saisie'),
            placeholder: options.placeholder || '',
            defaultValue: options.defaultValue || '',
            okLabel: options.okLabel || dlgT('dialog.validate', 'Valider')
        });
    };

    window.hbConfirmFormSubmit = function (form, message) {
        if (!form) {
            return false;
        }
        if (form.dataset.hbConfirming === '1') {
            return true;
        }
        if (typeof window.hbConfirm !== 'function') {
            return window.confirm(message);
        }
        window.hbConfirm(message).then(function (ok) {
            if (ok) {
                form.dataset.hbConfirming = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        });
        return false;
    };

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-hb-confirm]');
        if (!link) {
            return;
        }
        e.preventDefault();
        var msg = link.getAttribute('data-hb-confirm') || dlgT('dialog.confirm_default', 'Confirmer ?');
        window.hbConfirm(msg).then(function (ok) {
            if (ok) {
                window.location.href = link.href;
            }
        });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
