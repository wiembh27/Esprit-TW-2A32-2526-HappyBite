<?php

declare(strict_types=1);

require_once __DIR__ . '/fo_i18n.php';

/**
 * Overlay for inline santé forms on user_health_space.php.
 *
 * @param 'create'|'edit'|'create_suivi'|'edit_suivi' $mode
 */
function fo_sante_inline_shell_open(string $mode): void
{
    $titles = [
        'create' => fo_t('health.inline.create'),
        'edit' => fo_t('health.inline.edit'),
        'create_suivi' => fo_t('health.inline.create_suivi'),
        'edit_suivi' => fo_t('health.inline.edit_suivi'),
    ];
    $title = $titles[$mode] ?? fo_t('nav.health');
    ?>
<div id="fo-sante-inline-overlay" class="fo-sante-inline-overlay" role="dialog" aria-modal="true" aria-labelledby="fo-sante-inline-title">
    <div class="fo-sante-inline-overlay__panel">
        <div class="fo-sante-inline-overlay__head">
            <h2 id="fo-sante-inline-title" class="fo-sante-inline-overlay__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
            <button type="button" class="fo-sante-inline-overlay__close" id="fo-sante-inline-close" aria-label="<?php echo fo_e('health.inline.close'); ?>">&times;</button>
        </div>
        <div class="fo-sante-inline-overlay__body">
    <?php
}

function fo_sante_inline_shell_close(string $closeUrl): void
{
    ?>
        </div>
    </div>
</div>
<style>
.fo-sante-inline-overlay {
    position: fixed;
    inset: 0;
    z-index: 99980;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 1.25rem;
    overflow-y: auto;
    background: rgba(15, 42, 28, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    box-sizing: border-box;
}
.fo-sante-inline-overlay__panel {
    width: 100%;
    max-width: 920px;
    margin: auto;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #d5ebe0;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.22);
    font-family: "Poppins", system-ui, sans-serif;
}
.fo-sante-inline-overlay__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e8f0eb;
}
.fo-sante-inline-overlay__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #2C7E34;
}
.fo-sante-inline-overlay__close {
    border: none;
    background: #f0faf4;
    color: #2C7E34;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
}
.fo-sante-inline-overlay__close:hover {
    background: #d5ebe0;
}
.fo-sante-inline-overlay__body {
    padding: 1rem 1.25rem 1.5rem;
    max-height: min(78vh, 900px);
    overflow-y: auto;
}
.fo-sante-inline-form .sante-form-wrap {
    max-width: none;
    margin: 0;
    padding: 0;
    min-height: 0;
    background: transparent;
}
.fo-sante-inline-form .sante-form-wrap > h1,
.fo-sante-inline-form .sante-back {
    display: none !important;
}
.fo-sante-inline-form .sante-form-card {
    background: #fff;
    border: 1px solid #e3ebe6;
    border-radius: 14px;
    padding: 1.25rem 1.1rem;
}
.fo-sante-inline-form .sante-form-card label {
    font-weight: 500;
    display: block;
    margin-bottom: 6px;
    color: #1a1a1a;
}
.fo-sante-inline-form .sante-form-card input[type="number"],
.fo-sante-inline-form .sante-form-card input[type="text"],
.fo-sante-inline-form .sante-form-card select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #e3ebe6;
    margin-bottom: 12px;
    font-family: inherit;
    box-sizing: border-box;
}
.fo-sante-inline-form .sante-form-card .radio-group label {
    font-weight: 400;
}
.fo-sante-inline-form .sante-form-card button[type="submit"] {
    width: 100%;
    margin-top: 10px;
    padding: 12px;
    border: none;
    border-radius: 12px;
    background: #2C7E34;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
}
.fo-sante-inline-form .sante-form-card .error {
    color: #b91c1c;
    font-size: 0.85rem;
    display: none;
    margin-top: 4px;
}
</style>
<script>
(function () {
    var overlay = document.getElementById('fo-sante-inline-overlay');
    var closeBtn = document.getElementById('fo-sante-inline-close');
    var closeUrl = <?php echo json_encode($closeUrl, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    function closePanel() {
        window.location.href = closeUrl;
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closePanel);
    }
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closePanel();
            }
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePanel();
        }
    });
})();
</script>
    <?php
}
