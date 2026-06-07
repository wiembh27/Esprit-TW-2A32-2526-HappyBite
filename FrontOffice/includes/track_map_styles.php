    <style>
        .commande-wrap--track-stack {
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .commande-panel.track-panel {
            width: min(100%, 920px);
            padding: 18px 20px 22px;
            scroll-margin-top: 88px;
        }
        .track-section-title {
            margin: 0 0 14px;
            font-size: 1.35rem;
            font-weight: 700;
            color: #1b5e20;
        }
        .commande-panel.track-empty {
            width: min(100%, 520px);
            text-align: center;
            color: #2c3f32;
            font-weight: 600;
        }
        .track-shell {
            position: relative;
            width: 100%;
            height: min(58vh, 520px);
            min-height: 340px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #d4e6d6;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
            background: #e8eef5;
        }
        #track-map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        #track-map.leaflet-container,
        .track-shell .leaflet-container {
            width: 100%;
            height: 100%;
            background: #e8eef5;
        }
        .track-legend {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 700;
            background: rgba(255, 255, 255, 0.30);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 226, 226, 0.75);
            border-radius: 12px;
            padding: 12px 14px;
            min-width: 210px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }
        .track-legend-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 6px 0;
            color: #2c2c2c;
            font-weight: 500;
            font-size: 0.88rem;
        }
        .track-legend-icon {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }
        .track-card {
            position: absolute;
            right: 14px;
            bottom: 14px;
            z-index: 700;
            background: rgba(255, 255, 255, 0.30);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(221, 230, 222, 0.75);
            border-radius: 12px;
            padding: 14px 16px;
            min-width: 270px;
            max-width: calc(100% - 28px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }
        .track-card h2 {
            margin: 0 0 8px;
            color: #2e7d32;
            font-size: 1.25rem;
        }
        .track-card-line {
            margin: 0 0 10px;
            color: #2d2d2d;
            font-weight: 500;
        }
        .track-card-line--eta {
            color: #1565c0;
            font-weight: 700;
        }
        .track-card-line--sub {
            font-size: 0.82rem;
            color: #5c6d66;
            font-weight: 500;
        }
        .track-progress {
            height: 8px;
            background: #e5e5e5;
            border-radius: 999px;
            overflow: hidden;
        }
        .track-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #43a047, #2e7d32);
            border-radius: 999px;
        }
        .track-progress-label {
            margin-top: 6px;
            text-align: right;
            font-weight: 700;
            font-size: 0.85rem;
            color: #2e7d32;
        }
        .track-notif {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 710;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2e7d32;
            color: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(46, 125, 50, 0.28);
            font-weight: 600;
            font-size: 0.88rem;
        }
        .track-notif img {
            width: 18px;
            height: 18px;
            object-fit: contain;
        }
        .track-geo-status {
            margin-top: 8px;
            font-size: 0.75rem;
            line-height: 1.35;
            color: #1b5e20;
            font-weight: 600;
        }
        .track-geo-status--err {
            color: #c62828;
        }
        .track-commande-toolbar {
            position: absolute;
            top: 10px;
            right: 56px;
            z-index: 1000;
            pointer-events: auto;
        }
        .track-commande-select {
            min-width: 196px;
            max-width: min(50vw, 320px);
            padding: 8px 12px;
            font-family: "Poppins", system-ui, sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1b3a1f;
            border: 1px solid rgba(210, 222, 214, 0.9);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.28);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
            cursor: pointer;
        }
        .track-commande-select:focus {
            outline: 2px solid rgba(46, 125, 50, 0.45);
            outline-offset: 2px;
        }
        @media (max-width: 600px) {
            .track-shell {
                height: min(52vh, 440px);
                min-height: 280px;
            }
            .track-card {
                left: 14px;
                right: 14px;
                min-width: 0;
            }
            .track-commande-toolbar {
                top: 10px;
                right: 52px;
                left: 12px;
                max-width: none;
            }
            .track-commande-select {
                width: 100%;
                max-width: none;
            }
        }
        /* Modal : sélecteur sous la croix, à droite (comme avant) */
        .hb-track-modal .track-commande-toolbar {
            top: 10px;
            right: 56px;
            left: auto;
            max-width: calc(100% - 130px);
        }
    </style>
