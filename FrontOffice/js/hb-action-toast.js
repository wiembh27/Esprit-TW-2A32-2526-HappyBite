(function () {
    'use strict';

    var hideTimer = null;
    var audio = null;
    var soundUrl = window.HB_ACTION_TOAST_SOUND_URL || 'sounds/orange%20notification.wav';

    function getAudio() {
        if (!audio) {
            audio = new Audio(soundUrl);
            audio.preload = 'auto';
        }
        return audio;
    }

    function playActionToastSound() {
        try {
            var a = getAudio();
            a.currentTime = 0;
            var p = a.play();
            if (p && typeof p.catch === 'function') {
                p.catch(function () { /* blocked until user gesture */ });
            }
        } catch (e) {
            /* ignore */
        }
    }

    window.hbPlayActionToastSound = playActionToastSound;

    window.hbShowActionToast = function (text, durationMs) {
        var el = document.getElementById('hb-action-toast');
        if (!el) {
            return;
        }
        el.textContent = text;
        el.classList.add('is-visible');
        playActionToastSound();
        if (hideTimer) {
            clearTimeout(hideTimer);
        }
        hideTimer = setTimeout(function () {
            el.classList.remove('is-visible');
        }, durationMs || 3000);
    };

    document.addEventListener('click', function primeOnce() {
        getAudio();
        document.removeEventListener('click', primeOnce);
    }, { once: true, capture: true });
})();
