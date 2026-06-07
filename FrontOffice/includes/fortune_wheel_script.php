<?php
/** @var bool $canUseWheel */
/** @var list<array<string,mixed>> $wheelSegments */
/** @var int $ROUE_COST */
$fortuneWheelBtnSelector = $fortuneWheelBtnSelector ?? '.btn-fortune';
?>
<script>
window.HB_FORTUNE_WHEEL = <?= json_encode([
    'canPlay' => $canUseWheel,
    'segments' => $wheelSegments,
    'cost' => $ROUE_COST,
    'previewNote' => sprintf(fo_t('cart.fortune_preview_note'), (int) $ROUE_COST),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

(function () {
    const cfg = window.HB_FORTUNE_WHEEL || {};
    const segments = Array.isArray(cfg.segments) ? cfg.segments : [];
    const roueModal = document.getElementById('roueModal');
    const wheelMachine = document.getElementById('fortuneWheelMachine');
    const wheelSvg = document.getElementById('fortuneWheelSvg');
    const wheelResult = document.getElementById('wheelResult');
    const btnDrawWheel = document.getElementById('btnDrawWheel');
    const pointsValue = document.getElementById('pointsValue');
    const fortuneBtn = document.querySelector(<?= json_encode($fortuneWheelBtnSelector, JSON_UNESCAPED_UNICODE) ?>);

    const SEGMENT_COUNT = 8;
    const SEGMENT_ANGLE = 360 / SEGMENT_COUNT;
    const WHEEL_COLORS = ['#1565C0', '#D40506', '#FFFFFF', '#43A047', '#26C6DA', '#FFEB3B', '#FF9800', '#8E24AA'];
    const SEGMENT_PATHS = [
        'M100,100 L100,4 A96,96 0 0,1 167.8,32.2 Z',
        'M100,100 L167.8,32.2 A96,96 0 0,1 196,100 Z',
        'M100,100 L196,100 A96,96 0 0,1 167.8,167.8 Z',
        'M100,100 L167.8,167.8 A96,96 0 0,1 100,196 Z',
        'M100,100 L100,196 A96,96 0 0,1 32.2,167.8 Z',
        'M100,100 L32.2,167.8 A96,96 0 0,1 4,100 Z',
        'M100,100 L4,100 A96,96 0 0,1 32.2,32.2 Z',
        'M100,100 L32.2,32.2 A96,96 0 0,1 100,4 Z',
    ];
    const STAR_PATH = 'M0,-12 L2.75,-3.5 L11.5,-3.5 L4.25,1.5 L7,10 L0,4.5 L-7,10 L-4.25,1.5 L-11.5,-3.5 L-2.75,-3.5 Z';
    const FRICTION = 0.992;
    const MIN_STOP = 0.08;
    const LAND_FRAMES = 8;

    let wheelAngle = 0;
    let wheelVelocity = 0;
    let wheelBusy = false;
    let spinning = false;
    let stopping = false;
    let spinRaf = null;
    let lastTickSegment = -1;
    let pendingWin = null;
    let stopTargetAngle = null;
    let stopPhase = 'friction';
    let prizeCloseTimer = null;
    let audioCtx = null;
    let spinOsc = null;
    let spinGain = null;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function attrEsc(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function wheelImageSrc(path) {
        if (!path) {
            return '';
        }
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        if (path.indexOf('/uploads/') === 0) {
            return '..' + path;
        }
        if (path.indexOf('uploads/') === 0) {
            return '../' + path;
        }
        return '../uploads/' + String(path).replace(/^\/+/, '');
    }

    function polar(cx, cy, r, deg) {
        const rad = (deg - 90) * Math.PI / 180;
        return {
            x: cx + r * Math.cos(rad),
            y: cy + r * Math.sin(rad),
        };
    }

    function segmentMid(index) {
        return polar(100, 100, 58, index * SEGMENT_ANGLE + SEGMENT_ANGLE / 2);
    }

    function segmentIndexAtAngle(angle) {
        const normalized = ((angle % 360) + 360) % 360;
        const localAtPointer = (360 - normalized) % 360;

        return Math.floor(localAtPointer / SEGMENT_ANGLE) % SEGMENT_COUNT;
    }

    function targetAngleForWin(winIndex, currentAngle) {
        const normalized = ((currentAngle % 360) + 360) % 360;
        let targetNorm = (360 - (winIndex * SEGMENT_ANGLE) - (SEGMENT_ANGLE / 2) + 360) % 360;

        if (targetNorm <= normalized) {
            targetNorm += 360;
        }

        return currentAngle - normalized + targetNorm;
    }

    function goldStudsSvg() {
        return '<g aria-hidden="true">'
            + '<circle cx="133.1" cy="20.6" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="179.4" cy="67.1" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="179.4" cy="132.9" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="132.9" cy="179.4" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="67.1" cy="179.4" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="20.6" cy="132.9" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="20.6" cy="67.1" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="66.9" cy="20.6" r="3" fill="url(#goldGrad)" stroke="#FF8F00" stroke-width="0.4"/>'
            + '<circle cx="100" cy="5" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="100" cy="1.2" r="2.4" fill="#FFE082"/>'
            + '<circle cx="167.2" cy="32.8" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="171" cy="29" r="2.4" fill="#FFE082"/>'
            + '<circle cx="195" cy="100" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="198.8" cy="100" r="2.4" fill="#FFE082"/>'
            + '<circle cx="167.2" cy="167.2" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="171" cy="171" r="2.4" fill="#FFE082"/>'
            + '<circle cx="100" cy="195" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="100" cy="198.8" r="2.4" fill="#FFE082"/>'
            + '<circle cx="32.8" cy="167.2" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="29" cy="171" r="2.4" fill="#FFE082"/>'
            + '<circle cx="5" cy="100" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="1.2" cy="100" r="2.4" fill="#FFE082"/>'
            + '<circle cx="32.8" cy="32.8" r="4.2" fill="url(#goldGrad)" stroke="#E65100" stroke-width="0.5"/>'
            + '<circle cx="29" cy="29" r="2.4" fill="#FFE082"/>'
            + '</g>';
    }

    function getAudio() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    function startSpinHum() {
        const ctx = getAudio();
        stopSpinHum();
        spinOsc = ctx.createOscillator();
        spinGain = ctx.createGain();
        spinOsc.type = 'sawtooth';
        spinOsc.frequency.value = 52;
        spinGain.gain.value = 0;
        spinGain.gain.setTargetAtTime(0.028, ctx.currentTime, 0.12);
        const filter = ctx.createBiquadFilter();
        filter.type = 'lowpass';
        filter.frequency.value = 220;
        spinOsc.connect(filter);
        filter.connect(spinGain);
        spinGain.connect(ctx.destination);
        spinOsc.start();
    }

    function stopSpinHum() {
        if (!spinOsc || !spinGain) {
            return;
        }
        const osc = spinOsc;
        const gain = spinGain;
        spinOsc = null;
        spinGain = null;
        if (audioCtx) {
            gain.gain.setTargetAtTime(0, audioCtx.currentTime, 0.06);
        }
        window.setTimeout(function () {
            try {
                osc.stop();
            } catch (e) { /* ignore */ }
        }, 100);
    }

    function updateSpinHum() {
        if (!spinOsc || !spinGain) {
            return;
        }
        spinOsc.frequency.setTargetAtTime(48 + wheelVelocity * 2.2, audioCtx.currentTime, 0.05);
        spinGain.gain.setTargetAtTime(0.018 + wheelVelocity * 0.0012, audioCtx.currentTime, 0.05);
    }

    function playWheelTick(strength) {
        const ctx = getAudio();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'square';
        osc.frequency.value = 880 + strength * 120;
        gain.gain.setValueAtTime(0.04 + strength * 0.03, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.035);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.04);
    }

    function playWheelStop() {
        const ctx = getAudio();
        const t = ctx.currentTime;

        function note(freq, start, dur, vol, type) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type || 'triangle';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0, start);
            gain.gain.linearRampToValueAtTime(vol, start + 0.018);
            gain.gain.exponentialRampToValueAtTime(0.001, start + dur);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(start);
            osc.stop(start + dur + 0.05);
        }

        note(523.25, t, 0.1, 0.16, 'triangle');
        note(659.25, t + 0.11, 0.12, 0.18, 'triangle');
        note(783.99, t + 0.24, 0.14, 0.2, 'triangle');
        [523.25, 659.25, 783.99, 1046.5].forEach(function (freq) {
            note(freq, t + 0.38, 0.75, 0.1, 'sine');
        });
        note(1318.51, t + 0.42, 0.55, 0.07, 'sine');
        note(1567.98, t + 0.5, 0.35, 0.05, 'sine');
    }

    function renderFortuneWheel() {
        if (!wheelSvg) {
            return;
        }

        let svg = ''
            + '<defs>'
            + '<clipPath id="wheelClip"><circle cx="100" cy="100" r="96"/></clipPath>'
            + '<radialGradient id="fortuneCoinRimGrad" cx="50%" cy="50%" r="50%">'
            + '<stop offset="0%" stop-color="#2c7e34"/>'
            + '<stop offset="72%" stop-color="#358f42"/>'
            + '<stop offset="86%" stop-color="#5ed86f"/>'
            + '<stop offset="100%" stop-color="#6ee680"/>'
            + '</radialGradient>'
            + '<radialGradient id="fortuneCoinFaceGrad" cx="38%" cy="32%" r="62%">'
            + '<stop offset="0%" stop-color="#7ee88f"/>'
            + '<stop offset="28%" stop-color="#6ed97f"/>'
            + '<stop offset="58%" stop-color="#43b556"/>'
            + '<stop offset="84%" stop-color="#2c7e34"/>'
            + '<stop offset="100%" stop-color="#247a32"/>'
            + '</radialGradient>'
            + '<radialGradient id="goldGrad" cx="35%" cy="30%" r="70%">'
            + '<stop offset="0%" stop-color="#FFF8E1"/>'
            + '<stop offset="45%" stop-color="#FFD54F"/>'
            + '<stop offset="100%" stop-color="#FF8F00"/>'
            + '</radialGradient>'
            + '</defs>'
            + '<g clip-path="url(#wheelClip)">';

        for (let i = 0; i < SEGMENT_COUNT; i++) {
            svg += '<path d="' + SEGMENT_PATHS[i] + '" fill="' + WHEEL_COLORS[i] + '"/>';
        }

        svg += '</g>';

        for (let i = 0; i < SEGMENT_COUNT; i++) {
            const seg = segments[i] || {};
            const mid = segmentMid(i);
            const rot = i * SEGMENT_ANGLE + SEGMENT_ANGLE / 2;

            if (seg.type === 'points') {
                const pts = Number(seg.points || 0);
                svg += '<g transform="translate(' + mid.x + ',' + mid.y + ') rotate(' + rot + ')">'
                    + '<circle class="fortune-seg-coin-rim" cx="0" cy="0" r="15.5"/>'
                    + '<circle class="fortune-seg-coin-face" cx="0" cy="0" r="14"/>'
                    + '<ellipse class="fortune-seg-coin-shine" cx="-2.5" cy="-4.5" rx="5.5" ry="3.8" fill="rgba(255,255,255,0.3)"/>'
                    + '<path class="fortune-seg-star" d="' + STAR_PATH + '" transform="scale(0.58)"/>'
                    + '<text class="fortune-seg-points-text" x="0" y="26" text-anchor="middle" transform="rotate(90 0 26)">' + pts + '</text>'
                    + '</g>';
                continue;
            }

            const imgSrc = wheelImageSrc(seg.image);
            if (imgSrc) {
                svg += '<g transform="translate(' + mid.x + ',' + mid.y + ') rotate(' + rot + ')">'
                    + '<defs><clipPath id="segClip' + i + '"><circle cx="0" cy="0" r="20"/></clipPath></defs>'
                    + '<image class="fortune-seg-image" href="' + attrEsc(imgSrc) + '" x="-20" y="-20" width="40" height="40" clip-path="url(#segClip' + i + ')" preserveAspectRatio="xMidYMid slice"/>'
                    + '</g>';
                continue;
            }

            const label = seg.type === 'recette' ? '🍽' : '🥗';
            svg += '<text x="' + mid.x + '" y="' + (mid.y + 4) + '" text-anchor="middle" font-size="18">' + label + '</text>';
        }

        svg += goldStudsSvg();
        svg += '<circle cx="100" cy="100" r="96" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="3"/>';
        svg += '<circle cx="100" cy="100" r="90" fill="none" stroke="rgba(0,0,0,0.08)" stroke-width="1"/>';
        wheelSvg.innerHTML = svg;
        wheelSvg.style.transform = 'rotate(' + wheelAngle + 'deg)';
    }

    function showWheelResult(html, isError) {
        if (!wheelResult) {
            return;
        }

        wheelResult.style.display = 'block';
        wheelResult.classList.toggle('is-error', !!isError);
        wheelResult.innerHTML = html;
    }

    function resetWheelState() {
        wheelAngle = 0;
        wheelVelocity = 0;
        spinning = false;
        stopping = false;
        wheelBusy = false;
        lastTickSegment = -1;
        pendingWin = null;
        stopTargetAngle = null;
        stopPhase = 'friction';

        if (prizeCloseTimer) {
            window.clearTimeout(prizeCloseTimer);
            prizeCloseTimer = null;
        }

        if (wheelSvg) {
            wheelSvg.style.transform = 'rotate(0deg)';
        }
        if (wheelMachine) {
            wheelMachine.classList.remove('is-spinning');
        }
        if (btnDrawWheel) {
            btnDrawWheel.textContent = 'DRAW';
            btnDrawWheel.classList.remove('is-stop');
            btnDrawWheel.disabled = false;
        }
        if (wheelResult) {
            wheelResult.style.display = 'none';
            wheelResult.innerHTML = '';
            wheelResult.classList.remove('is-error');
        }

        stopSpinHum();

        if (spinRaf) {
            cancelAnimationFrame(spinRaf);
            spinRaf = null;
        }
    }

    function closeRoue() {
        if (!roueModal) {
            return;
        }

        roueModal.classList.remove('open');
        roueModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('hb-wheel-open');
        resetWheelState();
    }

    function prizeLabel(seg) {
        if (!seg) {
            return 'Récompense HappyBite';
        }
        if (seg.type === 'points') {
            return '+' + Number(seg.points || 0) + ' points santé';
        }
        return String(seg.label || 'Récompense HappyBite');
    }

    function finishWithPrize(winPayload) {
        const landedIndex = segmentIndexAtAngle(wheelAngle);
        const landedSeg = segments[landedIndex] || {};
        const displayLabel = winPayload.label || prizeLabel(landedSeg);
        const isPreview = !!(winPayload.data && winPayload.data.preview);

        let resultHtml = '<strong>Congrats! You won</strong><br><strong>' + escapeHtml(displayLabel) + '</strong>';
        if (isPreview && cfg.previewNote) {
            resultHtml += '<br><small>' + escapeHtml(cfg.previewNote) + '</small>';
        }

        showWheelResult(resultHtml, false);

        if (!isPreview && winPayload.data && typeof winPayload.data.points_apres !== 'undefined' && pointsValue) {
            pointsValue.textContent = winPayload.data.points_apres;
        }

        wheelBusy = false;
        stopping = false;
        spinning = false;

        if (btnDrawWheel) {
            btnDrawWheel.disabled = isPreview ? false : true;
            btnDrawWheel.textContent = 'DRAW';
            btnDrawWheel.classList.remove('is-stop');
        }
        if (fortuneBtn) {
            fortuneBtn.disabled = false;
        }

        if (prizeCloseTimer) {
            window.clearTimeout(prizeCloseTimer);
        }

        prizeCloseTimer = window.setTimeout(function () {
            prizeCloseTimer = null;
            closeRoue();

            if (!isPreview) {
                window.location.reload();
            }
        }, 3000);
    }

    function completeWheelPrize() {
        if (stopTargetAngle !== null) {
            wheelAngle = stopTargetAngle;
        }

        wheelVelocity = 0;
        stopping = false;
        spinning = false;
        stopPhase = 'friction';
        stopSpinHum();
        playWheelStop();

        if (wheelMachine) {
            wheelMachine.classList.remove('is-spinning');
        }

        const winPayload = pendingWin;
        pendingWin = null;
        stopTargetAngle = null;

        if (wheelSvg) {
            wheelSvg.style.transform = 'rotate(' + wheelAngle + 'deg)';
        }

        finishWithPrize(winPayload);
        spinRaf = null;
    }

    function wheelTick() {
        if (spinning && !stopping) {
            wheelVelocity = Math.min(wheelVelocity + 0.35, 28);
            wheelAngle += wheelVelocity;
        } else if (stopping) {
            if (stopPhase === 'friction') {
                wheelVelocity *= FRICTION;
                wheelAngle += wheelVelocity;

                if (wheelVelocity < MIN_STOP) {
                    wheelVelocity = 0;
                    stopPhase = (pendingWin && stopTargetAngle !== null) ? 'landing' : 'waiting';
                }
            } else if (stopPhase === 'waiting') {
                wheelVelocity = 0;

                if (pendingWin && stopTargetAngle !== null) {
                    stopPhase = 'landing';
                }
            } else if (stopPhase === 'landing' && pendingWin && stopTargetAngle !== null) {
                const distToTarget = stopTargetAngle - wheelAngle;

                if (distToTarget <= 0.05) {
                    completeWheelPrize();
                    return;
                }

                const landStep = Math.min(
                    Math.max(distToTarget / LAND_FRAMES, 0.3),
                    distToTarget
                );
                wheelVelocity = landStep;
                wheelAngle += landStep;
            }
        } else {
            spinRaf = null;
            return;
        }

        if (!spinning && !stopping) {
            spinRaf = null;
            return;
        }
        const segment = segmentIndexAtAngle(wheelAngle);

        if (segment !== lastTickSegment) {
            lastTickSegment = segment;
            playWheelTick(Math.min(wheelVelocity / 28, 1));
        }

        updateSpinHum();

        if (wheelSvg) {
            wheelSvg.style.transform = 'rotate(' + wheelAngle + 'deg)';
        }

        spinRaf = requestAnimationFrame(wheelTick);
    }

    function startContinuousSpin() {
        if (spinning || wheelBusy || segments.length !== SEGMENT_COUNT) {
            return;
        }

        getAudio();
        spinning = true;
        stopping = false;
        stopPhase = 'friction';
        wheelBusy = true;
        pendingWin = null;
        stopTargetAngle = null;

        if (wheelVelocity < 12) {
            wheelVelocity = 12;
        }

        lastTickSegment = segmentIndexAtAngle(wheelAngle);

        if (wheelMachine) {
            wheelMachine.classList.add('is-spinning');
        }
        if (btnDrawWheel) {
            btnDrawWheel.textContent = 'STOP';
            btnDrawWheel.classList.add('is-stop');
        }
        if (fortuneBtn) {
            fortuneBtn.disabled = true;
        }

        startSpinHum();

        if (!spinRaf) {
            spinRaf = requestAnimationFrame(wheelTick);
        }
    }

    function queuePendingWin(winIndex, label, data) {
        pendingWin = {
            index: winIndex,
            label: label,
            data: data || null,
        };
        stopTargetAngle = targetAngleForWin(winIndex, wheelAngle);

        if (!spinning && wheelVelocity === 0) {
            wheelAngle = stopTargetAngle;

            if (wheelSvg) {
                wheelSvg.style.transform = 'rotate(' + wheelAngle + 'deg)';
            }

            const winPayload = pendingWin;
            pendingWin = null;
            stopTargetAngle = null;
            playWheelStop();

            if (wheelMachine) {
                wheelMachine.classList.remove('is-spinning');
            }

            finishWithPrize(winPayload);
        }
    }

    function requestWheelStop() {
        if (!spinning || stopping) {
            return;
        }

        stopping = true;
        stopPhase = 'friction';

        if (btnDrawWheel) {
            btnDrawWheel.disabled = true;
            btnDrawWheel.textContent = 'Stopping…';
            window.setTimeout(function () {
                if (btnDrawWheel) {
                    btnDrawWheel.disabled = false;
                    btnDrawWheel.textContent = 'STOP';
                }
            }, 400);
        }

        if (!cfg.canPlay) {
            const winIndex = Math.floor(Math.random() * SEGMENT_COUNT);
            const seg = segments[winIndex] || {};
            queuePendingWin(winIndex, prizeLabel(seg), { preview: true });
            return;
        }

        fetch('fortune_wheel_spin.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.text().then(function (text) {
                    let data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Réponse non JSON');
                    }

                    if (!response.ok || !data || !data.success) {
                        throw new Error((data && data.message) ? data.message : 'Impossible de lancer la roue.');
                    }

                    return data;
                });
            })
            .then(function (data) {
                const winIndex = typeof data.segment_index === 'number'
                    ? data.segment_index
                    : parseInt(data.segment_index, 10) || 0;

                const label = (data.gain && data.gain.label)
                    ? data.gain.label
                    : ((data.produit && data.produit.nomProduit) ? data.produit.nomProduit : 'Récompense');

                queuePendingWin(winIndex, label, data);
            })
            .catch(function (error) {
                stopping = false;
                spinning = false;
                wheelBusy = false;
                stopSpinHum();

                if (spinRaf) {
                    cancelAnimationFrame(spinRaf);
                    spinRaf = null;
                }

                if (wheelMachine) {
                    wheelMachine.classList.remove('is-spinning');
                }

                showWheelResult(
                    '<strong>Erreur :</strong><br>' + escapeHtml(error.message || 'Tirage impossible.'),
                    true
                );

                if (btnDrawWheel) {
                    btnDrawWheel.disabled = false;
                    btnDrawWheel.textContent = 'DRAW';
                    btnDrawWheel.classList.remove('is-stop');
                }
                if (fortuneBtn && cfg.canPlay) {
                    fortuneBtn.disabled = false;
                }

                window.setTimeout(closeRoue, 3000);
            });
    }

    function onDrawWheelClick() {
        if (!spinning) {
            startContinuousSpin();
            return;
        }
        if (!stopping) {
            requestWheelStop();
        }
    }

    window.openRoue = function () {
        if (!roueModal || wheelBusy) {
            return;
        }

        var drawOverlay = document.getElementById('hbChallengeDrawOverlay');
        if (drawOverlay) {
            drawOverlay.classList.add('is-hidden');
        }

        resetWheelState();
        renderFortuneWheel();
        roueModal.classList.add('open');
        roueModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('hb-wheel-open');

        window.setTimeout(function () {
            startContinuousSpin();
        }, 400);
    };

    window.closeRoue = closeRoue;

    if (btnDrawWheel) {
        btnDrawWheel.addEventListener('click', onDrawWheelClick);
    }

    document.addEventListener('click', function primeAudioOnce() {
        getAudio();
    }, { once: true, capture: true });

    renderFortuneWheel();
})();
</script>
