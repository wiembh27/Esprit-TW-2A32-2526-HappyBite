<?php

declare(strict_types=1);

require_once __DIR__ . '/fo_i18n.php';
fo_init_i18n_for_request();

$foDetailModalTitle = $foDetailModalTitle ?? fo_t('products.details');
$foDetailModalClose = $foDetailModalClose ?? fo_t('health.inline.close');

?>
<div id="fo-catalog-detail-modal" class="fo-catalog-detail-modal fo-catalog-detail-modal--hidden" role="dialog" aria-modal="true" aria-labelledby="fo-catalog-detail-modal-title" aria-hidden="true">
    <div class="fo-catalog-detail-modal__panel" role="document">
        <div class="fo-catalog-detail-modal__head">
            <h2 id="fo-catalog-detail-modal-title" class="fo-catalog-detail-modal__title"><?php echo htmlspecialchars($foDetailModalTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
            <button type="button" class="fo-catalog-detail-modal__close" id="fo-catalog-detail-modal-close" aria-label="<?php echo htmlspecialchars($foDetailModalClose, ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
        </div>
        <div id="fo-catalog-detail-modal-body" class="fo-catalog-detail-modal__body">
            <p class="text-muted mb-0 fo-catalog-detail-modal__loading"><?php echo fo_e('common.loading'); ?></p>
        </div>
    </div>
</div>
<style>
.fo-catalog-detail-modal--hidden {
    display: none !important;
}
body.fo-catalog-detail-open {
    overflow: hidden;
}
.btn-hb-details {
    font-weight: 700 !important;
    font-size: 0.95rem;
    padding: 0.5rem 1.35rem !important;
    letter-spacing: 0.01em;
}
.fo-catalog-detail-modal {
    position: fixed;
    inset: 0;
    z-index: 99985;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: rgba(15, 42, 28, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    box-sizing: border-box;
    overflow-y: auto;
}
.fo-catalog-detail-modal__panel {
    width: 100%;
    max-width: 920px;
    max-height: min(90vh, 920px);
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #d5ebe0;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.22);
    font-family: "Poppins", system-ui, sans-serif;
    animation: fo-catalog-detail-pop 0.28s ease;
}
@keyframes fo-catalog-detail-pop {
    from { opacity: 0; transform: scale(0.96) translateY(6px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.fo-catalog-detail-modal__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e8f0eb;
    flex-shrink: 0;
}
.fo-catalog-detail-modal__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #2C7E34;
}
.fo-catalog-detail-modal__close {
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
.fo-catalog-detail-modal__close:hover {
    background: #d5ebe0;
}
.fo-catalog-detail-modal__body {
    padding: 1rem 1.25rem 1.5rem;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
.fo-catalog-detail-modal__loading {
    text-align: center;
    padding: 2rem 0;
}
</style>
<script>
(function () {
    var modal = document.getElementById('fo-catalog-detail-modal');
    var bodyEl = document.getElementById('fo-catalog-detail-modal-body');
    var titleEl = document.getElementById('fo-catalog-detail-modal-title');
    var closeBtn = document.getElementById('fo-catalog-detail-modal-close');
    var loadingHtml = <?php echo json_encode('<p class="text-muted mb-0 fo-catalog-detail-modal__loading">' . fo_e('common.loading') . '</p>', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var errorHtml = <?php echo json_encode('<p class="text-danger mb-0">' . fo_e('common.error') . '</p>', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var defaultTitle = <?php echo json_encode($foDetailModalTitle, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function openModal(title) {
        if (!modal) return;
        if (titleEl && title) titleEl.textContent = title;
        modal.classList.remove('fo-catalog-detail-modal--hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('fo-catalog-detail-open');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('fo-catalog-detail-modal--hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('fo-catalog-detail-open');
        if (bodyEl) bodyEl.innerHTML = loadingHtml;
        if (titleEl) titleEl.textContent = defaultTitle;
    }

    function loadDetail(url) {
        if (!bodyEl) return;
        bodyEl.innerHTML = loadingHtml;
        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (!r.ok) throw new Error('load');
                return r.text();
            })
            .then(function (html) {
                bodyEl.innerHTML = html;
            })
            .catch(function () {
                bodyEl.innerHTML = errorHtml;
            });
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-fo-catalog-detail');
        if (trigger) {
            e.preventDefault();
            var url = trigger.getAttribute('data-detail-url');
            if (!url) return;
            var title = trigger.getAttribute('data-detail-title') || defaultTitle;
            openModal(title);
            loadDetail(url);
            return;
        }
        if (e.target.closest('.js-fo-catalog-detail-close')) {
            e.preventDefault();
            closeModal();
            return;
        }
        if (modal && e.target === modal) {
            closeModal();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('fo-catalog-detail-modal--hidden')) {
            closeModal();
        }
    });
})();
</script>
