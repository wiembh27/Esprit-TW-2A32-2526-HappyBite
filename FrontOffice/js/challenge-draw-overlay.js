(function () {
    'use strict';

    var COOLDOWN_MS = 24 * 60 * 60 * 1000;

    function storageKey(userId) {
        return 'hb_challenge_draw_seen_' + String(userId || '0');
    }

    function shouldShowOverlay(userId) {
        try {
            var raw = localStorage.getItem(storageKey(userId));
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

    function markSeen(userId) {
        try {
            localStorage.setItem(storageKey(userId), String(Date.now()));
        } catch (e) { /* ignore */ }
    }

    function fetchChallenge(url) {
        var formData = new FormData();
        formData.append('action', 'draw_challenge_jour');

        return fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); });
    }

    window.hbInitChallengeDrawOverlay = function (config) {
        if (!config || !config.enabled) {
            return;
        }

        var overlay = document.getElementById('hbChallengeDrawOverlay');
        if (!overlay) {
            return;
        }

        if (!config.forceShow && !shouldShowOverlay(config.userId)) {
            overlay.classList.add('is-hidden');
            return;
        }

        overlay.classList.remove('is-hidden');

        var machine = document.getElementById('hbChallengeMachine');
        var area = document.getElementById('hbChallengeBallsArea');
        var winnerSlot = document.getElementById('hbChallengeWinnerSlot');
        var btn = document.getElementById('hbChallengeDrawBtn');
        var knob = document.getElementById('hbChallengeKnob');
        var reveal = document.getElementById('hbChallengeReveal');
        var revealTitle = document.getElementById('hbChallengeRevealTitle');
        var revealDesc = document.getElementById('hbChallengeRevealDesc');

        if (!machine || !area || !winnerSlot || !btn || !knob) {
            return;
        }

        var pastels = [
            ['#F8A4C8', '#F48FB1'],
            ['#9DD4F0', '#7EC8E3'],
            ['#6B9AE8', '#4A78D4'],
            ['#B8F0D8', '#8EE4C0'],
            ['#8EDFC8', '#5ECFA8'],
            ['#DCC4F5', '#C4A8EC'],
            ['#FF6B6B', '#D40506'],
            ['#FAFAFA', '#E8E8E8'],
            ['#FFF59D', '#FDD835'],
            ['#FFCC80', '#FFA726'],
            ['#F48FB1', '#EC407A'],
            ['#80DEEA', '#26C6DA'],
            ['#CE93D8', '#AB47BC'],
            ['#A5D6A7', '#66BB6A'],
            ['#FFAB91', '#FF7043']
        ];

        var ballCount = 38;
        var knobRotation = 0;
        var balls = [];
        var animId = null;
        var busy = false;
        var gravityActive = true;
        var gravity = 0.34;
        var globeR = 0;
        var globeCx = 0;
        var globeCy = 0;
        var audioCtx = null;
        var rumbleNodes = null;
        var lastClack = 0;
        var pendingChallenge = null;

        function getAudio() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }

        function startRumble() {
            var ctx = getAudio();
            stopRumble();
            var bufferSize = ctx.sampleRate * 2;
            var noiseBuffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
            var data = noiseBuffer.getChannelData(0);
            for (var i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }
            var noise = ctx.createBufferSource();
            noise.buffer = noiseBuffer;
            noise.loop = true;
            var filter = ctx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.frequency.value = 180;
            filter.Q.value = 0.6;
            var gain = ctx.createGain();
            gain.gain.value = 0.07;
            noise.connect(filter);
            filter.connect(gain);
            gain.connect(ctx.destination);
            noise.start();
            rumbleNodes = { noise: noise, gain: gain, filter: filter };
        }

        function stopRumble() {
            if (!rumbleNodes) {
                return;
            }
            var nodes = rumbleNodes;
            rumbleNodes = null;
            try {
                nodes.gain.gain.cancelScheduledValues(0);
                nodes.gain.gain.setValueAtTime(0, audioCtx ? audioCtx.currentTime : 0);
                nodes.noise.stop(0);
                nodes.noise.disconnect();
                nodes.gain.disconnect();
                nodes.filter.disconnect();
            } catch (e) { /* ignore */ }
        }

        function spinKnob() {
            knobRotation += 180;
            knob.style.transition = 'transform 0.5s ease-in-out';
            knob.style.transform = 'rotate(' + knobRotation + 'deg)';
        }

        function playBallClack() {
            if (!busy) {
                return;
            }
            var now = performance.now();
            if (now - lastClack < 120) {
                return;
            }
            lastClack = now;
            var ctx = getAudio();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 520 + Math.random() * 380;
            gain.gain.setValueAtTime(0.09, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.045);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.05);
        }

        function playBallEject() {
            var ctx = getAudio();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(420, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(120, ctx.currentTime + 0.35);
            gain.gain.setValueAtTime(0.14, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.38);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.4);
            var thud = ctx.createOscillator();
            var thudGain = ctx.createGain();
            thud.type = 'sine';
            thud.frequency.setValueAtTime(90, ctx.currentTime + 0.28);
            thud.frequency.exponentialRampToValueAtTime(55, ctx.currentTime + 0.55);
            thudGain.gain.setValueAtTime(0, ctx.currentTime);
            thudGain.gain.setValueAtTime(0.18, ctx.currentTime + 0.28);
            thudGain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
            thud.connect(thudGain);
            thudGain.connect(ctx.destination);
            thud.start(ctx.currentTime + 0.28);
            thud.stop(ctx.currentTime + 0.62);
        }

        function playBallReveal() {
            var ctx = getAudio();
            var notes = [523.25, 659.25, 783.99];
            notes.forEach(function (freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                var t = ctx.currentTime + i * 0.07;
                gain.gain.setValueAtTime(0, t);
                gain.gain.linearRampToValueAtTime(0.11, t + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, t + 0.45);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(t);
                osc.stop(t + 0.48);
            });
        }

        function ballGradient(pair) {
            return 'radial-gradient(circle at 32% 28%, rgba(255,255,255,0.95) 0%, '
                + pair[0] + ' 40%, ' + pair[1] + ' 100%)';
        }

        function updateGlobeMetrics() {
            var w = area.offsetWidth;
            var h = area.offsetHeight;
            globeCx = w / 2;
            globeCy = h / 2;
            globeR = Math.min(w, h) / 2 - 4;
        }

        function randomSpawnPos(size) {
            var r = size / 2;
            var upper = Math.random() < 0.35;
            for (var attempt = 0; attempt < 80; attempt++) {
                var angle = Math.random() * Math.PI * 2;
                var dist = Math.random() * (globeR - r - 4);
                var cx = globeCx + Math.cos(angle) * dist;
                var cy = upper
                    ? globeCy - Math.abs(Math.sin(angle)) * dist * 0.45 - globeR * 0.05
                    : globeCy + Math.sin(angle) * dist * 0.55 + globeR * 0.12;
                var dx = cx - globeCx;
                var dy = cy - globeCy;
                if (Math.sqrt(dx * dx + dy * dy) <= globeR - r - 2) {
                    return { x: cx - r, y: cy - r };
                }
            }
            return {
                x: globeCx - r + (Math.random() - 0.5) * globeR * 0.6,
                y: globeCy + globeR * 0.2 - r + Math.random() * globeR * 0.4
            };
        }

        function placeBall(b) {
            b.el.style.left = b.x + 'px';
            b.el.style.top = b.y + 'px';
        }

        function constrainCircle(b) {
            var cx = b.x + b.r;
            var cy = b.y + b.r;
            var dx = cx - globeCx;
            var dy = cy - globeCy;
            var dist = Math.sqrt(dx * dx + dy * dy) || 0.01;
            var maxDist = globeR - b.r;
            if (dist > maxDist) {
                var nx = dx / dist;
                var ny = dy / dist;
                cx = globeCx + nx * maxDist;
                cy = globeCy + ny * maxDist;
                b.x = cx - b.r;
                b.y = cy - b.r;
                b.vx *= -0.65;
                b.vy *= -0.65;
            }
        }

        function createBalls() {
            area.innerHTML = '';
            balls = [];
            updateGlobeMetrics();
            for (var i = 0; i < ballCount; i++) {
                var el = document.createElement('div');
                el.className = 'ball';
                var size = 22 + Math.random() * 8;
                var pair = pastels[Math.floor(Math.random() * pastels.length)];
                el.style.width = size + 'px';
                el.style.height = size + 'px';
                el.style.background = ballGradient(pair);
                var pos = randomSpawnPos(size);
                var b = {
                    el: el,
                    x: pos.x,
                    y: pos.y,
                    vx: (Math.random() - 0.5) * 1.5,
                    vy: (Math.random() - 0.5) * 1.5,
                    r: size / 2,
                    grad: el.style.background
                };
                balls.push(b);
                area.appendChild(el);
                placeBall(b);
            }
        }

        function ensureSimulation() {
            if (!animId) {
                animId = requestAnimationFrame(simulate);
            }
        }

        function simulate() {
            if (!gravityActive) {
                animId = null;
                return;
            }
            updateGlobeMetrics();
            var w = area.offsetWidth;
            var h = area.offsetHeight;
            for (var i = 0; i < balls.length; i++) {
                var b = balls[i];
                if (b.el.style.visibility === 'hidden') {
                    continue;
                }
                b.vy += gravity;
                if (busy) {
                    b.vx += (Math.random() - 0.5) * 3.4;
                    b.vy += (Math.random() - 0.5) * 2.8;
                }
                b.vx *= 0.985;
                b.vy *= 0.985;
                b.x += b.vx;
                b.y += b.vy;
                if (b.x < 0) { b.x = 0; b.vx *= -0.55; }
                if (b.y < 0) { b.y = 0; b.vy *= -0.55; }
                if (b.x > w - b.r * 2) { b.x = w - b.r * 2; b.vx *= -0.55; }
                if (b.y > h - b.r * 2) { b.y = h - b.r * 2; b.vy *= -0.55; }
                constrainCircle(b);
                for (var j = i + 1; j < balls.length; j++) {
                    var o = balls[j];
                    if (o.el.style.visibility === 'hidden') {
                        continue;
                    }
                    var dx = (o.x + o.r) - (b.x + b.r);
                    var dy = (o.y + o.r) - (b.y + b.r);
                    var dist = Math.sqrt(dx * dx + dy * dy) || 0.01;
                    var minD = b.r + o.r;
                    if (dist < minD) {
                        if (busy) {
                            playBallClack();
                        }
                        var overlap = (minD - dist) * 0.5;
                        var nx = dx / dist;
                        var ny = dy / dist;
                        b.x -= nx * overlap;
                        b.y -= ny * overlap;
                        o.x += nx * overlap;
                        o.y += ny * overlap;
                        constrainCircle(b);
                        constrainCircle(o);
                        var swap = 0.55;
                        var tvx = b.vx;
                        var tvy = b.vy;
                        b.vx = o.vx * swap + b.vx * (1 - swap);
                        b.vy = o.vy * swap + b.vy * (1 - swap);
                        o.vx = tvx * swap + o.vx * (1 - swap);
                        o.vy = tvy * swap + o.vy * (1 - swap);
                    }
                }
                placeBall(b);
            }
            animId = requestAnimationFrame(simulate);
        }

        function showRevealCard(challenge) {
            if (!reveal || !revealTitle || !revealDesc) {
                return;
            }
            revealTitle.textContent = challenge.titre || '';
            revealDesc.textContent = challenge.description || '';
            reveal.classList.add('is-visible');
            btn.style.display = 'none';
        }

        function finishOverlay() {
            markSeen(config.userId);
            overlay.classList.add('is-fading');
            setTimeout(function () {
                overlay.classList.add('is-hidden');
                overlay.classList.remove('is-fading');
                window.location.reload();
            }, 420);
        }

        function runDrawAnimation(onBallReady) {
            if (busy) {
                return;
            }
            busy = true;
            btn.disabled = true;
            winnerSlot.className = 'winner-slot';
            winnerSlot.innerHTML = '';
            machine.classList.add('shaking');
            spinKnob();
            getAudio();
            startRumble();

            setTimeout(function () {
                machine.classList.remove('shaking');
                stopRumble();

                var pick = balls[Math.floor(Math.random() * balls.length)];
                pick.el.style.visibility = 'hidden';

                var out = document.createElement('div');
                out.className = 'ball';
                out.style.background = pick.grad;
                winnerSlot.appendChild(out);
                winnerSlot.classList.add('is-out');
                winnerSlot.setAttribute('aria-hidden', 'false');
                playBallEject();

                setTimeout(function () {
                    winnerSlot.classList.add('is-open');
                    playBallReveal();
                    busy = false;
                    if (typeof onBallReady === 'function') {
                        onBallReady();
                    }
                }, 1000);
            }, 3000);
        }

        function onDrawClick() {
            if (busy) {
                return;
            }
            btn.disabled = true;

            fetchChallenge(config.drawUrl || window.location.href)
                .then(function (data) {
                    if (!data || !data.success || !data.challenge) {
                        pendingChallenge = {
                            titre: config.errorTitle || 'Erreur',
                            description: (data && data.message) ? data.message : (config.errorGeneric || '')
                        };
                    } else {
                        pendingChallenge = data.challenge;
                    }
                    runDrawAnimation(function () {
                        showRevealCard(pendingChallenge);
                        setTimeout(finishOverlay, 3000);
                    });
                })
                .catch(function () {
                    btn.disabled = false;
                    if (typeof window.hbShowActionToast === 'function') {
                        window.hbShowActionToast(config.errorNetwork || 'Erreur réseau.', 3500);
                    }
                });
        }

        btn.addEventListener('click', onDrawClick);
        createBalls();
        ensureSimulation();

        window.addEventListener('resize', function () {
            if (!busy) {
                createBalls();
                ensureSimulation();
            }
        });

        document.addEventListener('click', function primeOnce() {
            getAudio();
        }, { once: true, capture: true });
    };
})();
