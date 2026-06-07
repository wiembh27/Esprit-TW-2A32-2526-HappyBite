<?php

declare(strict_types=1);

/**
 * Orange action toast (profile password/email style) + sound on show.
 */
function hb_action_toast_render(string $assetPrefix = ''): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    require_once __DIR__ . '/fo_i18n.php';

    $prefix = $assetPrefix === '' ? '' : rtrim($assetPrefix, '/') . '/';
    $css = $prefix . 'css/hb-action-toast.css';
    $dialogCss = $prefix . 'css/hb-dialog.css';
    $js = $prefix . 'js/hb-action-toast.js';
    $dialogJs = $prefix . 'js/hb-dialog.js';
    $sound = $prefix . 'sounds/orange notification.wav';

    echo '<link rel="stylesheet" href="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . htmlspecialchars($dialogCss, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<div id="hb-action-toast" class="hb-action-toast" role="status" aria-live="polite"></div>' . "\n";
    echo '<div id="hb-dialog-overlay" class="hb-dialog" role="dialog" aria-modal="true" aria-labelledby="hb-dialog-title" hidden>'
        . '<div class="hb-dialog__panel">'
        . '<h2 id="hb-dialog-title" class="hb-dialog__title"></h2>'
        . '<p id="hb-dialog-message" class="hb-dialog__message"></p>'
        . '<input type="text" id="hb-dialog-input" class="hb-dialog__input" hidden autocomplete="off">'
        . '<div class="hb-dialog__actions">'
        . '<button type="button" id="hb-dialog-cancel" class="hb-dialog__btn hb-dialog__btn--ghost">' . fo_e('dialog.cancel') . '</button>'
        . '<button type="button" id="hb-dialog-ok" class="hb-dialog__btn hb-dialog__btn--primary">' . fo_e('dialog.ok') . '</button>'
        . '</div></div></div>' . "\n";
    echo '<script>window.HB_ACTION_TOAST_SOUND_URL = ' . json_encode($sound, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>' . "\n";
    echo '<script src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
    echo '<script src="' . htmlspecialchars($dialogJs, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}

/**
 * Resolve health-space ?notice= keys to localized messages.
 */
function hb_health_notice_message(string $notice): ?string
{
    $notice = preg_replace('/[^a-z_]/', '', $notice) ?? '';
    if ($notice === '') {
        return null;
    }

    $map = [
        'suivi_saved' => 'health.flash_suivi_saved',
        'suivi_updated' => 'health.flash_suivi_updated',
        'suivi_deleted' => 'health.flash_suivi_deleted',
        'suivi_db_error' => 'health.flash_suivi_error',
        'profile_created' => 'health.flash_profile_created',
        'profile_updated' => 'health.flash_profile_updated',
        'ia_plus' => 'health.flash_ia_plus',
        'ia_minus' => 'health.flash_ia_minus',
        'ia_done' => 'health.flash_ia_done',
        'ia_no_suivi' => 'health.flash_ia_no_suivi',
        'ia_profile' => 'health.flash_ia_profile',
        'ia_api_key' => 'health.flash_ia_api',
        'ia_ai_fail' => 'health.flash_ia_fail',
        'ia_error' => 'health.flash_ia_error',
    ];

    if (!isset($map[$notice])) {
        return null;
    }

    return fo_t($map[$notice]);
}

/**
 * Resolve supplier catalog ?notice= keys to localized messages.
 */
function hb_catalog_notice_message(string $notice): ?string
{
    $notice = preg_replace('/[^a-z_]/', '', $notice) ?? '';
    if ($notice === '') {
        return null;
    }

    $map = [
        'product_added' => 'supplier.flash_product_added',
        'product_updated' => 'supplier.flash_product_updated',
        'product_deleted' => 'supplier.flash_product_deleted',
    ];

    if (!isset($map[$notice])) {
        return null;
    }

    return fo_t($map[$notice]);
}

/**
 * Emit DOM script that shows the orange action toast (+ sound) on page load.
 *
 * @param list<string> $stripQueryKeys URL params to remove after showing (e.g. notice, success).
 */
function hb_action_toast_script(
    ?string $message,
    int $durationMs = 3500,
    bool $stripUrlParams = false,
    array $urlParamsToStrip = ['notice']
): void {
    if ($message === null || trim($message) === '') {
        return;
    }

    $payload = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $keysJson = json_encode(array_values($urlParamsToStrip), JSON_UNESCAPED_UNICODE);

    echo '<script>document.addEventListener("DOMContentLoaded",function(){';
    echo 'if(typeof window.hbShowActionToast==="function"){window.hbShowActionToast(' . $payload . ',' . (int) $durationMs . ');}';
    if ($stripUrlParams) {
        echo 'try{var url=new URL(window.location.href);var keys=' . $keysJson . ';var changed=false;';
        echo 'keys.forEach(function(k){if(url.searchParams.has(k)){url.searchParams.delete(k);changed=true;}});';
        echo 'if(changed){var next=url.pathname+(url.searchParams.toString()?"?"+url.searchParams.toString():"")+url.hash;';
        echo 'window.history.replaceState({},"",next);}}catch(e){}';
    }
    echo '});</script>' . "\n";
}

/** Orange notification sound only (e.g. Profile pwd-toast). */
function hb_action_toast_sound_render(string $assetPrefix = ''): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $prefix = $assetPrefix === '' ? '' : rtrim($assetPrefix, '/') . '/';
    $js = $prefix . 'js/hb-action-toast.js';
    $sound = $prefix . 'sounds/orange notification.wav';

    echo '<script>window.HB_ACTION_TOAST_SOUND_URL = ' . json_encode($sound, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>' . "\n";
    echo '<script src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}
