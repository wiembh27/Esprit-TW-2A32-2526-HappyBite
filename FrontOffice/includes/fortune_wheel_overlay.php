<?php

declare(strict_types=1);

$fortuneWheelCanPlay = (bool) ($fortuneWheelCanPlay ?? false);
$fortuneWheelSegments = is_array($fortuneWheelSegments ?? null) ? $fortuneWheelSegments : [];
$fortuneWheelCost = (int) ($fortuneWheelCost ?? 100);
$fortuneWheelTriggerSelector = (string) ($fortuneWheelTriggerSelector ?? '.btn-fortune');

?>
<style>
.wheel-modal,
        .wheel-modal * {
            box-sizing: border-box;
        }

        .wheel-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10050;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            background: rgba(15, 42, 28, 0.55);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        .wheel-modal.open {
            display: flex;
        }

        .wheel-overlay-panel {
            background: transparent;
            border-radius: 0;
            padding: 0;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: none;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.35rem;
        }

        .wheel-overlay-panel h2,
        .wheel-overlay-subtitle {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .fortune-wheel-machine {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
            filter: drop-shadow(0 28px 48px rgba(212, 5, 6, 0.28));
        }

        .fortune-wheel-machine::before {
            content: "";
            position: absolute;
            top: 6%;
            left: 50%;
            transform: translateX(-50%);
            width: min(88vw, 300px);
            height: min(88vw, 300px);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212, 5, 6, 0.18) 0%, transparent 68%);
            pointer-events: none;
            z-index: 0;
        }

        .fortune-wheel-stage {
            position: relative;
            width: min(88vw, 300px);
            height: min(88vw, 300px);
            aspect-ratio: 1 / 1;
            flex-shrink: 0;
            z-index: 2;
        }

        .fortune-wheel-pointer {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 6;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.18));
        }

        .fortune-wheel-pointer::before {
            content: "";
            display: block;
            width: 18px;
            height: 10px;
            margin: 0 auto;
            border-radius: 3px 3px 0 0;
            background: linear-gradient(180deg, #FFE082 0%, #FFB300 55%, #FF8F00 100%);
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.65);
        }

        .fortune-wheel-pointer::after {
            content: "";
            display: block;
            width: 0;
            height: 0;
            margin: 0 auto;
            border-left: 11px solid transparent;
            border-right: 11px solid transparent;
            border-top: 22px solid #FFB300;
            filter: drop-shadow(0 1px 0 #FF8F00);
        }

        .fortune-wheel-frame {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            padding: 14px;
            overflow: hidden;
            background:
                radial-gradient(circle at 28% 22%, rgba(255, 255, 255, 0.9) 0%, transparent 42%),
                linear-gradient(145deg, #f04849 0%, #D40506 50%, #8A0304 100%);
            border: 3px solid rgba(255, 180, 180, 0.55);
            box-shadow:
                inset 0 0 40px rgba(255, 180, 180, 0.25),
                inset -8px -16px 32px rgba(100, 0, 0, 0.18),
                0 12px 36px rgba(138, 3, 4, 0.22);
        }

        .fortune-wheel-frame::after {
            content: "";
            position: absolute;
            inset: 7px;
            border-radius: 50%;
            pointer-events: none;
            box-shadow: inset 0 0 0 2px rgba(255, 213, 79, 0.35);
        }

        .fortune-wheel-svg {
            width: 100%;
            height: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            transform: rotate(0deg);
            transform-origin: 50% 50%;
            will-change: transform;
            display: block;
            flex-shrink: 0;
        }

        .fortune-wheel-hub {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 22%;
            height: 22%;
            aspect-ratio: 1 / 1;
            margin: -11% 0 0 -11%;
            border-radius: 50%;
            background: radial-gradient(circle at 32% 28%, #FFF8E1 0%, #FFD54F 28%, #FFB300 58%, #FF8F00 100%);
            box-shadow:
                0 5px 16px rgba(0, 0, 0, 0.22),
                inset 0 3px 8px rgba(255, 255, 255, 0.8),
                inset 0 -4px 10px rgba(230, 81, 0, 0.35);
            z-index: 4;
            border: 2px solid #FFE082;
        }

        .fortune-wheel-hub::before {
            content: "";
            position: absolute;
            top: 14%;
            left: 50%;
            width: 46%;
            height: 46%;
            margin-left: -23%;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 30%, #FFFDE7 0%, #FFD54F 45%, #FF8F00 100%);
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.7);
        }

        .fortune-wheel-hub::after {
            content: "";
            position: absolute;
            top: 22%;
            left: 26%;
            width: 28%;
            height: 22%;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.72);
        }

        .fortune-wheel-stand {
            width: 56px;
            height: 20px;
            margin-top: -4px;
            background: linear-gradient(180deg, #D40506 0%, #A80405 100%);
            border-radius: 0 0 8px 8px;
            box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.15);
            z-index: 2;
        }

        .fortune-wheel-platform {
            width: 200px;
            height: 26px;
            margin-top: -2px;
            border-radius: 50%;
            background: linear-gradient(180deg, #D40506 0%, #8A0304 100%);
            box-shadow: 0 8px 16px rgba(138, 3, 4, 0.25), inset 0 2px 4px rgba(255, 180, 180, 0.25);
            z-index: 2;
        }

        .wheel-result {
            display: none;
            margin-top: 0;
            padding: 0.85rem 1rem;
            border-radius: 16px;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            font-weight: 700;
            line-height: 1.55;
            font-size: 0.92rem;
            max-width: min(92vw, 320px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        }

        .wheel-result.is-error {
            background: #fff1f2;
            color: #9f1239;
            border-color: #fecdd3;
        }

        .wheel-overlay-panel .btn-draw {
            min-width: 200px;
            padding: 0.95rem 2.8rem;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            background: linear-gradient(180deg, #43a047 0%, #2C7E34 55%, #1b5e20 100%);
            color: #fff;
            box-shadow: 0 8px 24px rgba(44, 126, 52, 0.35);
            transition: transform 0.15s, box-shadow 0.15s;
            font-family: inherit;
        }

        .wheel-overlay-panel .btn-draw:hover:not(:disabled) {
            transform: translateY(-2px);
        }

        .wheel-overlay-panel .btn-draw:active:not(:disabled) {
            transform: translateY(1px);
        }

        .wheel-overlay-panel .btn-draw.is-stop {
            background: linear-gradient(180deg, #ff7043 0%, #e65100 55%, #bf360c 100%);
            box-shadow: 0 8px 24px rgba(230, 81, 0, 0.35);
        }

        .wheel-overlay-panel .btn-draw:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .fortune-seg-image {
            pointer-events: none;
        }

        .fortune-seg-coin-rim {
            fill: url(#fortuneCoinRimGrad);
            stroke: #6ee680;
            stroke-width: 0.75;
        }

        .fortune-seg-coin-face {
            fill: url(#fortuneCoinFaceGrad);
        }

        .fortune-seg-coin-shine {
            pointer-events: none;
        }

        .fortune-seg-points-text {
            font-family: "Poppins", sans-serif;
            font-size: 13px;
            font-weight: 800;
            fill: #1b5e20;
        }

        .fortune-seg-star {
            fill: #ffffff;
            stroke: none;
        }
</style>

<div class="wheel-modal" id="roueModal" aria-hidden="true">
        <div class="wheel-overlay-panel" role="dialog" aria-modal="true" aria-labelledby="fortuneWheelTitle">
            <h2 id="fortuneWheelTitle"><?php echo fo_e('cart.fortune_title'); ?></h2>
            <p class="wheel-overlay-subtitle">
                <?php echo htmlspecialchars(sprintf(fo_t('cart.fortune_btn_cost'), (string) (int) $ROUE_COST), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <div class="fortune-wheel-machine" id="fortuneWheelMachine">
                <div class="fortune-wheel-stage">
                    <div class="fortune-wheel-pointer" aria-hidden="true"></div>
                    <div class="fortune-wheel-frame">
                        <svg class="fortune-wheel-svg" id="fortuneWheelSvg" viewBox="0 0 200 200" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Roue de la fortune"></svg>
                    </div>
                    <div class="fortune-wheel-hub" aria-hidden="true"></div>
                </div>

<script>
window.HB_FORTUNE_WHEEL = <?= json_encode([
    'canPlay' => $fortuneWheelCanPlay,
    'segments' => $fortuneWheelSegments,
    'cost' => $fortuneWheelCost,
    'previewNote' => sprintf(fo_t('cart.fortune_preview_note'), (int) $fortuneWheelCost),
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
    const fortuneBtn = document.querySelector(<?= json_encode($fortuneWheelTriggerSelector, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);

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

        resetWheelState();
        renderFortuneWheel();
        roueModal.classList.add('open');
        roueModal.setAttribute('aria-hidden', 'false');

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
