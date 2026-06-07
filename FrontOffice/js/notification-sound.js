(function () {
    'use strict';

    var soundUrl = window.HB_NOTIF_SOUND_URL || 'sounds/notification.wav';
    var pollUrl = window.HB_NOTIF_POLL_URL || 'api/notifications_poll.php';
    var pollMs = typeof window.HB_NOTIF_POLL_MS === 'number' ? window.HB_NOTIF_POLL_MS : 28000;

    var storageUnread = 'hb_notif_unread';
    var storageInit = 'hb_notif_sound_init';

    var audio = null;

    function getAudio() {
        if (!audio) {
            audio = new Audio(soundUrl);
            audio.preload = 'auto';
        }
        return audio;
    }

    function playNotificationSound() {
        try {
            var a = getAudio();
            a.currentTime = 0;
            var p = a.play();
            if (p && typeof p.catch === 'function') {
                p.catch(function () { /* autoplay blocked until user gesture */ });
            }
        } catch (e) {
        }
    }

    function playForNewCount(delta) {
        var n = Math.max(0, parseInt(String(delta), 10) || 0);
        if (n < 1) {
            return;
        }
        n = Math.min(n, 5);
        for (var i = 0; i < n; i++) {
            (function (idx) {
                setTimeout(playNotificationSound, idx * 450);
            })(i);
        }
    }

    function updateNavDot(unread) {
        var dot = document.getElementById('hb-nav-notif-dot');
        var trigger = document.querySelector('.nav-profile-trigger');
        if (dot) {
            if (unread > 0) {
                dot.hidden = false;
                dot.removeAttribute('hidden');
            } else {
                dot.hidden = true;
            }
        }
        if (trigger) {
            var label = unread > 0
                ? 'Compte — ' + unread + ' notification' + (unread > 1 ? 's' : '') + ' non lue' + (unread > 1 ? 's' : '')
                : 'Compte';
            trigger.setAttribute('aria-label', label);
        }
    }

    function applyUnreadCount(unread, playSound) {
        var count = Math.max(0, parseInt(String(unread), 10) || 0);
        var prev = parseInt(sessionStorage.getItem(storageUnread) || '0', 10);
        var initialized = sessionStorage.getItem(storageInit) === '1';

        if (playSound && initialized && count > prev) {
            playForNewCount(count - prev);
        }

        sessionStorage.setItem(storageUnread, String(count));
        if (!initialized) {
            sessionStorage.setItem(storageInit, '1');
        }

        updateNavDot(count);
        return count;
    }

    window.hbPlayNotificationSound = playNotificationSound;
    window.hbApplyNotificationUnread = applyUnreadCount;

    function pollNotifications() {
        fetch(pollUrl, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data && data.ok && typeof data.unread === 'number') {
                    applyUnreadCount(data.unread, true);
                }
            })
            .catch(function () { });
    }

    if (typeof window.HB_NOTIF_INITIAL_UNREAD === 'number') {
        applyUnreadCount(window.HB_NOTIF_INITIAL_UNREAD, true);
    }

    if (window.HB_NOTIF_POLL_ENABLED) {
        setInterval(pollNotifications, pollMs);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                pollNotifications();
            }
        });
    }

    document.addEventListener('click', function primeOnce() {
        getAudio();
        document.removeEventListener('click', primeOnce);
    }, { once: true, capture: true });
})();
