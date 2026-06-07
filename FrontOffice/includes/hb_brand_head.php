<?php

/**
 * Favicon (tiny_logo) + custom cursor assets.
 *
 * @param string $assetPrefix Path prefix to FrontOffice root, e.g. '' or '../'
 * @param string|null $faviconOverride Full href for favicon (e.g. BackOffice/images/tiny_logo.png)
 */
function hb_brand_render_head(string $assetPrefix = '', ?string $faviconOverride = null): void
{
    static $headDone = false;
    if ($headDone) {
        return;
    }
    $headDone = true;

    $prefix = $assetPrefix === '' ? '' : rtrim($assetPrefix, '/') . '/';
    $favicon = $faviconOverride ?? ($prefix . 'images/tiny_logo.png');
    $css = $prefix . 'css/hb-cursor.css';

    echo '<link rel="icon" type="image/png" href="' . htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<link rel="shortcut icon" type="image/png" href="' . htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">' . "\n";

    if (!function_exists('fo_theme_render_head')) {
        require_once __DIR__ . '/fo_i18n.php';
    }
    if (function_exists('fo_init_i18n_for_request')) {
        fo_init_i18n_for_request();
    }
    if (function_exists('fo_theme_render_head')) {
        fo_theme_render_head($prefix);
    }
    if (function_exists('fo_theme_render_script')) {
        fo_theme_render_script($prefix);
    }
}

/**
 * @param string $assetPrefix Path prefix to FrontOffice root for hb-cursor.js
 */
function hb_brand_render_footer(string $assetPrefix = ''): void
{
    static $footerDone = false;
    if ($footerDone) {
        return;
    }
    $footerDone = true;

    $prefix = $assetPrefix === '' ? '' : rtrim($assetPrefix, '/') . '/';
    $js = $prefix . 'js/hb-cursor.js';
    echo '<script src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
}

/** Asset prefix from current script path (FrontOffice root vs auth/). */
function hb_brand_asset_prefix_from_request(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#/FrontOffice/auth/#i', $script) || preg_match('#/auth/(login|register)\.php#i', $script)) {
        return '../';
    }

    return '';
}
