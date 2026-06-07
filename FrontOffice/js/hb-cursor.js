(function () {
    'use strict';

    if (!window.matchMedia('(pointer: fine)').matches) {
        return;
    }

    /* BackOffice shell: one cursor on parent; iframe only hides native pointer */
    if (window.self !== window.top) {
        return;
    }

    var clickable =
        'a, button, input[type="submit"], input[type="button"], input[type="reset"], ' +
        'label[for], select, summary, [role="button"], [role="link"], ' +
        '.btn, .nav-link, .fo-ai-widget__trigger, .caloryeye-analyse-btn, .frigo-ai-btn, ' +
        '[onclick], [data-hb-clickable]';

    var boFrame = document.querySelector('iframe.bo-main-frame');

    document.body.classList.add('hb-custom-cursor');

    var dot = document.createElement('div');
    dot.className = 'hb-cursor-dot';
    dot.setAttribute('aria-hidden', 'true');
    document.body.appendChild(dot);

    function showDot() {
        dot.style.opacity = '1';
    }

    function move(clientX, clientY) {
        showDot();
        dot.style.left = clientX + 'px';
        dot.style.top = clientY + 'px';
    }

    document.addEventListener('mousemove', function (e) {
        move(e.clientX, e.clientY);
    }, { passive: true });

    document.addEventListener('mouseover', function (e) {
        if (e.target && e.target.closest && e.target.closest(clickable)) {
            dot.classList.add('is-hover');
        } else {
            dot.classList.remove('is-hover');
        }
    }, { passive: true });

    document.addEventListener('mouseleave', function () {
        dot.classList.remove('is-hover');
    }, { passive: true });

    /* Hide only when the pointer leaves the tab — not when focus moves into the iframe */
    if (!boFrame) {
        window.addEventListener('blur', function () {
            dot.style.opacity = '0';
        });
        window.addEventListener('focus', function () {
            showDot();
        });
    } else {
        document.documentElement.addEventListener('mouseleave', function (e) {
            if (e.relatedTarget === null) {
                dot.style.opacity = '0';
            }
        });
        document.documentElement.addEventListener('mouseenter', function () {
            showDot();
        });
        boFrame.addEventListener('mouseenter', showDot);
    }

    function bridgeBoIframe(frame) {
        var bridgedDoc = null;

        function attach() {
            var win;
            try {
                win = frame.contentWindow;
            } catch (err) {
                return;
            }
            if (!win || !win.document || !win.document.body) {
                return;
            }

            if (bridgedDoc === win.document) {
                win.document.body.classList.add('hb-custom-cursor');
                showDot();
                return;
            }

            bridgedDoc = win.document;
            win.document.body.classList.add('hb-custom-cursor');
            showDot();

            win.addEventListener('mousemove', function (e) {
                var rect = frame.getBoundingClientRect();
                move(rect.left + e.clientX, rect.top + e.clientY);
            }, { passive: true });

            win.document.addEventListener('mouseover', function (e) {
                if (e.target && e.target.closest && e.target.closest(clickable)) {
                    dot.classList.add('is-hover');
                } else {
                    dot.classList.remove('is-hover');
                }
            }, { passive: true });

            win.document.addEventListener('mouseleave', function () {
                dot.classList.remove('is-hover');
            }, { passive: true });
        }

        frame.addEventListener('load', attach);
        if (frame.contentDocument && frame.contentDocument.readyState !== 'loading') {
            attach();
        }
    }

    if (boFrame) {
        bridgeBoIframe(boFrame);
    }
})();
