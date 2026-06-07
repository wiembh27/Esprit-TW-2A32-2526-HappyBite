<?php require_once __DIR__ . '/fo_i18n.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    .hb-track-modal {
        position: fixed;
        inset: 0;
        z-index: 12500;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        box-sizing: border-box;
    }
    .hb-track-modal.is-open {
        display: flex;
    }
    .hb-track-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(12, 28, 20, 0.55);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    .hb-track-modal__dialog {
        position: relative;
        z-index: 1;
        width: min(920px, 100%);
        max-height: min(90vh, 720px);
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.28);
        overflow: hidden;
        border: 1px solid #d4e6d6;
    }
    .hb-track-modal__close {
        position: absolute;
        top: 10px;
        right: 12px;
        z-index: 1300;
        width: 36px;
        height: 36px;
        border: 1px solid rgba(221, 230, 222, 0.75);
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.35);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        color: #1b3a1f;
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }
    .hb-track-modal__close:hover {
        background: rgba(255, 255, 255, 0.5);
    }
    .hb-track-modal__body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
    }
    .hb-track-modal__body .track-shell {
        height: min(72vh, 560px);
        min-height: 320px;
        border-radius: 0;
        border: none;
        box-shadow: none;
    }
    .hb-track-modal__msg {
        margin: 0;
        padding: 2rem 1.5rem;
        text-align: center;
        font-weight: 600;
        color: #2c3f32;
        line-height: 1.5;
    }
    .hb-track-modal__loading {
        padding: 3rem 1.5rem;
        text-align: center;
        color: #2e7d32;
        font-weight: 600;
    }
</style>
<?php require __DIR__ . '/track_map_styles.php'; ?>
<div id="hb-track-modal" class="hb-track-modal" aria-hidden="true" hidden>
    <div class="hb-track-modal__backdrop" data-hb-track-close tabindex="-1"></div>
    <div class="hb-track-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="hb-track-modal-title">
        <button type="button" class="hb-track-modal__close" data-hb-track-close aria-label="<?php echo fo_e('track.close'); ?>">&times;</button>
        <div id="hb-track-modal-body" class="hb-track-modal__body"></div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/track-map-modal.js"></script>
