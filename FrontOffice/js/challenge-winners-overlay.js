(function () {
    'use strict';

    var COOLDOWN_MS = 24 * 60 * 60 * 1000;

    function drawCooldownElapsed(userId) {
        try {
            var raw = localStorage.getItem('hb_challenge_draw_seen_' + String(userId || '0'));
            if (!raw) {
                return true;
            }
            var ts = parseInt(raw, 10);
            if (!ts || isNaN(ts)) {
                return true;
            }
            return (Date.now() - ts) >= COOLDOWN_MS;
        } catch (e) {
            return true;
        }
    }

    function winnersSeenKey(userId, challengeId) {
        return 'hb_challenge_winners_seen_' + String(userId || '0') + '_' + String(challengeId || '0');
    }

    function shouldShowWinners(userId, challengeId) {
        if (!drawCooldownElapsed(userId)) {
            return false;
        }
        try {
            return localStorage.getItem(winnersSeenKey(userId, challengeId)) !== '1';
        } catch (e) {
            return true;
        }
    }

    function markWinnersSeen(userId, challengeId) {
        try {
            localStorage.setItem(winnersSeenKey(userId, challengeId), '1');
        } catch (e) { /* ignore */ }
    }

    function hideCompetingOverlays() {
        var drawOverlay = document.getElementById('hbChallengeDrawOverlay');
        if (drawOverlay) {
            drawOverlay.classList.add('is-hidden');
        }

        if (typeof window.closeRoue === 'function') {
            window.closeRoue();
        }
    }

    window.hbInitChallengeWinnersOverlay = function (data, onContinue, forceShow) {
        var overlay = document.getElementById('hbChallengeWinnersOverlay');
        var tbody = document.getElementById('hbChallengeWinnersBody');
        var tapBtn = document.getElementById('hbChallengeWinnersTap');
        var payload = data || {};

        if (!overlay || !tbody || !payload.challengeId) {
            if (typeof onContinue === 'function') {
                onContinue();
            }
            return false;
        }

        if (!forceShow && (!payload.showOnLoad || !shouldShowWinners(payload.userId, payload.challengeId))) {
            overlay.classList.add('is-hidden');
            overlay.style.display = '';
            overlay.setAttribute('aria-hidden', 'true');
            stopWinnerSounds();
            return false;
        }

        hideCompetingOverlays();

        overlay.classList.remove('is-hidden');
        overlay.style.display = 'flex';
        overlay.setAttribute('aria-hidden', 'false');

        var fallLayer = document.getElementById('hbChallengeWinnersFall');
        if (fallLayer) {
            fallLayer.classList.remove('is-hidden');
            fallLayer.setAttribute('aria-hidden', 'false');
        }
        tbody.innerHTML = '';

        var top5 = Array.isArray(payload.top5) ? payload.top5 : [];
        top5.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + escapeHtml(row.rank || '') + '</td><td>' + escapeHtml(row.name || '') + '</td>';
            tbody.appendChild(tr);
        });

        if (top5.length === 0 && (!payload.user || payload.user.participated !== true)) {
            var placeholder = document.createElement('tr');
            placeholder.innerHTML = '<td colspan="2" class="hb-challenge-winners-empty">' + escapeHtml(payload.emptyLabel || 'No ranked participants yet.') + '</td>';
            tbody.appendChild(placeholder);
        }

        if (payload.user && payload.user.participated === true) {
            var userTr = document.createElement('tr');
            userTr.className = 'user-rank';
            var rankCell = escapeHtml(payload.user.rank || '—') + ' <span class="hb-challenge-winners-you">YOU</span>';
            userTr.innerHTML = '<td>' + rankCell + '</td><td>' + escapeHtml(payload.user.name || '') + '</td>';
            tbody.appendChild(userTr);
        }

        startConfetti();
        startWinnerSounds(tbody, overlay, false);

        document.addEventListener('click', function primeWinnerAudioOnce() {
            getAudio();
        }, { once: true, capture: true });

        function closeAndContinue() {
            overlay.classList.add('is-hidden');
            overlay.style.display = '';
            overlay.setAttribute('aria-hidden', 'true');
            stopConfetti();
            stopWinnerSounds();
            if (fallLayer) {
                fallLayer.classList.add('is-hidden');
                fallLayer.setAttribute('aria-hidden', 'true');
            }
            if (!forceShow) {
                markWinnersSeen(payload.userId, payload.challengeId);
            }
            if (typeof onContinue === 'function') {
                onContinue();
            }
        }

        if (tapBtn) {
            tapBtn.onclick = closeAndContinue;
        }

        overlay.onclick = function (e) {
            if (e.target === overlay) {
                closeAndContinue();
            }
        };

        return true;
    };

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    var confettiTimer = null;
    var confettiLayer = null;

    function startConfetti() {
        confettiLayer = document.getElementById('hbChallengeWinnersFall');
        if (!confettiLayer) {
            return;
        }

        var confettiColors = ['#26c6da', '#66bb6a', '#ab47bc', '#ec407a', '#ffca28', '#42a5f5', '#ef5350', '#7e57c2'];

        function pickColor() {
            return confettiColors[Math.floor(Math.random() * confettiColors.length)];
        }

        function svgDot(color) {
            return '<svg viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="7" fill="' + color + '"/></svg>';
        }

        function svgTri(color) {
            return '<svg viewBox="0 0 16 14" xmlns="http://www.w3.org/2000/svg"><polygon points="8,0 16,14 0,14" fill="' + color + '"/></svg>';
        }

        function svgStrip(color) {
            return '<svg viewBox="0 0 10 22" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="0" width="8" height="22" rx="2" fill="' + color + '"/></svg>';
        }

        var pieceTypes = [
            { html: function () { return svgDot(pickColor()); }, w: 10, h: 10 },
            { html: function () { return svgTri(pickColor()); }, w: 14, h: 12 },
            { html: function () { return svgStrip(pickColor()); }, w: 8, h: 20 }
        ];

        function spawn() {
            if (!confettiLayer) {
                return;
            }
            var type = pieceTypes[Math.floor(Math.random() * pieceTypes.length)];
            var el = document.createElement('div');
            el.className = 'fall-piece';
            el.innerHTML = type.html();
            var scale = 0.7 + Math.random() * 0.9;
            el.style.width = (type.w * scale) + 'px';
            el.style.height = (type.h * scale) + 'px';
            el.style.left = (Math.random() * 100) + '%';
            el.style.opacity = (0.75 + Math.random() * 0.25).toString();
            var duration = 4 + Math.random() * 7;
            var drift = (Math.random() - 0.5) * 120;
            var spin = (Math.random() > 0.5 ? 1 : -1) * (360 + Math.random() * 720) + 'deg';
            el.style.setProperty('--drift', drift + 'px');
            el.style.setProperty('--spin', spin);
            el.style.animationDuration = duration + 's';
            confettiLayer.appendChild(el);
            setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, (duration + 1.2) * 1000);
        }

        for (var i = 0; i < 28; i++) {
            setTimeout(spawn, i * 120);
        }
        confettiTimer = setInterval(spawn, 320);
    }

    function stopConfetti() {
        if (confettiTimer) {
            clearInterval(confettiTimer);
            confettiTimer = null;
        }
        if (confettiLayer) {
            confettiLayer.innerHTML = '';
        }
    }

    var audioCtx = null;
    var ambientChimeTimer = null;

    function getAudio() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    function playWinnerChime() {
        try {
            var ctx = getAudio();
            var melody = [392, 493.88, 587.33, 783.99];
            melody.forEach(function (freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.value = freq;
                var t = ctx.currentTime + i * 0.22;
                gain.gain.setValueAtTime(0, t);
                gain.gain.linearRampToValueAtTime(0.045, t + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.001, t + 0.9);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(t);
                osc.stop(t + 0.95);
            });
        } catch (e) { /* ignore */ }
    }

    function playRankReveal(tbody) {
        if (!tbody) {
            return;
        }

        try {
            var ctx = getAudio();
            var rows = tbody.querySelectorAll('tr');

            rows.forEach(function (row, i) {
                window.setTimeout(function () {
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    var isUser = row.classList.contains('user-rank');
                    osc.type = 'sine';
                    osc.frequency.value = isUser ? 523.25 : 329.63 + i * 55;
                    var t = ctx.currentTime;
                    gain.gain.setValueAtTime(0, t);
                    gain.gain.linearRampToValueAtTime(isUser ? 0.08 : 0.05, t + 0.03);
                    gain.gain.exponentialRampToValueAtTime(0.001, t + (isUser ? 0.7 : 0.4));
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(t);
                    osc.stop(t + (isUser ? 0.72 : 0.42));
                }, 180 + i * 140);
            });
        } catch (e) { /* ignore */ }
    }

    function startWinnerSounds(tbody, overlayEl, deferUntilInteraction) {
        function run() {
            playWinnerChime();
            playRankReveal(tbody);

            if (ambientChimeTimer) {
                clearInterval(ambientChimeTimer);
            }

            ambientChimeTimer = window.setInterval(playWinnerChime, 5200);
        }

        if (deferUntilInteraction && overlayEl) {
            var panel = overlayEl.querySelector('.hb-challenge-winners-overlay__panel');
            var target = panel || overlayEl;

            function onFirstInteract() {
                target.removeEventListener('pointerdown', onFirstInteract);
                run();
            }

            target.addEventListener('pointerdown', onFirstInteract, { once: true });
            return;
        }

        run();
    }

    function stopWinnerSounds() {
        if (ambientChimeTimer) {
            clearInterval(ambientChimeTimer);
            ambientChimeTimer = null;
        }
    }

    window.hbChallengeDrawCooldownElapsed = drawCooldownElapsed;
})();
