<style>
        /*
        |--------------------------------------------------------------------------
        | MODAL ROUE DE LA FORTUNE (8 segments)
        |--------------------------------------------------------------------------
        */

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

        body.hb-wheel-open footer {
            visibility: hidden;
            pointer-events: none;
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
