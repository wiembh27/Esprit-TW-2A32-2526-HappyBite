<?php

declare(strict_types=1);

/**
 * Overlay panel for inline catalog forms (profile-style centered modal).
 *
 * @param 'add'|'edit'|'detail' $mode
 */
function fo_catalog_inline_shell_open(string $mode, ?string $titleOverride = null): void
{
    $titles = [
        'add' => 'Ajouter un produit',
        'edit' => 'Modifier le produit',
        'detail' => 'Détails du produit',
    ];
    $title = $titleOverride ?? ($titles[$mode] ?? 'Produit');
    ?>
<div id="fo-catalog-inline-overlay" class="fo-catalog-inline-overlay" role="dialog" aria-modal="true" aria-labelledby="fo-catalog-inline-title">
    <div class="fo-catalog-inline-overlay__panel">
        <div class="fo-catalog-inline-overlay__head">
            <h2 id="fo-catalog-inline-title" class="fo-catalog-inline-overlay__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
            <button type="button" class="fo-catalog-inline-overlay__close" id="fo-catalog-inline-close" aria-label="Fermer">&times;</button>
        </div>
        <div class="fo-catalog-inline-overlay__body">
    <?php
}

function fo_catalog_inline_shell_close(string $closeUrl): void
{
    ?>
        </div>
    </div>
</div>
<style>
body.fo-catalog-inline-open {
    overflow: hidden;
}
.btn-hb-details {
    font-weight: 700 !important;
    font-size: 0.95rem;
    padding: 0.5rem 1.35rem !important;
    min-width: 7.5rem;
    letter-spacing: 0.01em;
}
.fo-catalog-inline-overlay {
    position: fixed;
    inset: 0;
    z-index: 99980;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    overflow-y: auto;
    background: rgba(15, 26, 20, 0.45);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-sizing: border-box;
}
.fo-catalog-inline-overlay__panel {
    width: 100%;
    max-width: 920px;
    margin: auto;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e8ecf0;
    box-shadow: 0 20px 50px rgba(19, 30, 23, 0.2);
    font-family: "Poppins", system-ui, sans-serif;
    animation: fo-catalog-inline-pop 0.28s ease;
}
@keyframes fo-catalog-inline-pop {
    from { opacity: 0; transform: scale(0.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.fo-catalog-inline-overlay__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e8f0eb;
}
.fo-catalog-inline-overlay__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #2C7E34;
}
.fo-catalog-inline-overlay__close {
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
.fo-catalog-inline-overlay__close:hover {
    background: #d5ebe0;
}
.fo-catalog-inline-overlay__body {
    padding: 1rem 1.25rem 1.5rem;
    max-height: min(78vh, 900px);
    overflow-y: auto;
}
.fo-catalog-inline-form .commande-wrap,
.fo-catalog-inline-form main.commande-wrap {
    padding-top: 0 !important;
}
.fo-catalog-inline-form .container.py-5 {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
.fo-catalog-inline-form .text-center.mb-4:first-child,
.fo-catalog-inline-form .d-flex.justify-content-center.gap-3.mb-4 {
    display: none !important;
}
</style>
<script>
(function () {
    var overlay = document.getElementById('fo-catalog-inline-overlay');
    var closeBtn = document.getElementById('fo-catalog-inline-close');
    var closeUrl = <?php echo json_encode($closeUrl, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.body.classList.add('fo-catalog-inline-open');
    function closePanel() {
        document.body.classList.remove('fo-catalog-inline-open');
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
