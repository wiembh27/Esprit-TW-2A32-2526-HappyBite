<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Controllers/UserSettingsService.php';

/** Brand / product names — never translated. */
function fo_i18n_protected_tokens(): array
{
    return [
        'HappyBite',
        'Challenge of the day',
        'Altbite',
        'CaloryEye',
        'Chefbot',
        'ChefBot',
        'Face ID',
        'Demandez-moi',
        'PayPal',
    ];
}

function fo_lang(): string
{
    $langs = user_settings_supported_languages();
    $lang = strtolower((string) ($_SESSION['fo_lang'] ?? 'fr'));

    return isset($langs[$lang]) ? $lang : 'fr';
}

function fo_mode(): string
{
    $mode = strtolower((string) ($_SESSION['fo_mode'] ?? 'light'));

    return $mode === 'dark' ? 'dark' : 'light';
}

function fo_init_i18n_for_request(): void
{
    if (isset($_SESSION['fo_lang'], $_SESSION['fo_mode'])) {
        return;
    }
    $loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    if (!$loggedIn) {
        user_settings_apply_to_session(null);
        return;
    }
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) {
        user_settings_apply_to_session(null);
        return;
    }
    try {
        user_settings_load_for_user(Database::getConnection(), $uid);
    } catch (Throwable $e) {
        user_settings_apply_to_session(null);
    }
}

/** @return array<string, string> */
function fo_i18n_dictionary(?string $lang = null): array
{
    $lang = $lang ?? fo_lang();
    $path = __DIR__ . '/../lang/' . $lang . '.php';
    if (!is_readable($path)) {
        $path = __DIR__ . '/../lang/fr.php';
    }
    /** @var array<string, string> $dict */
    $dict = require $path;

    return is_array($dict) ? $dict : [];
}

function fo_t(string $key, ?string $lang = null): string
{
    static $cache = [];
    $lang = $lang ?? fo_lang();
    if (!isset($cache[$lang])) {
        $cache[$lang] = fo_i18n_dictionary($lang);
    }
    if (isset($cache[$lang][$key])) {
        return (string) $cache[$lang][$key];
    }
    if ($lang !== 'fr') {
        if (!isset($cache['fr'])) {
            $cache['fr'] = fo_i18n_dictionary('fr');
        }
        if (isset($cache['fr'][$key])) {
            return (string) $cache['fr'][$key];
        }
    }

    return $key;
}

function fo_e(string $key): string
{
    return htmlspecialchars(fo_t($key), ENT_QUOTES, 'UTF-8');
}

/**
 * Translate database / user-generated text when UI language is English.
 */
function fo_db(?string $text): string
{
    $text = trim((string) $text);
    if ($text === '' || fo_lang() === 'fr') {
        return $text;
    }
    foreach (fo_i18n_protected_tokens() as $token) {
        if (stripos($text, $token) !== false) {
            return $text;
        }
    }

    if (!isset($_SESSION['fo_db_translate_cache']) || !is_array($_SESSION['fo_db_translate_cache'])) {
        $_SESSION['fo_db_translate_cache'] = [];
    }
    $cacheKey = md5($text);
    if (isset($_SESSION['fo_db_translate_cache'][$cacheKey])) {
        return (string) $_SESSION['fo_db_translate_cache'][$cacheKey];
    }

    try {
        require_once __DIR__ . '/../../Controllers/GeminiController.php';
        $gemini = new GeminiController();
        $translated = $gemini->translateText($text, 'en');
        if ($translated !== null && trim($translated) !== '') {
            $_SESSION['fo_db_translate_cache'][$cacheKey] = trim($translated);
            return (string) $_SESSION['fo_db_translate_cache'][$cacheKey];
        }
    } catch (Throwable $e) {
        /* keep original */
    }

    $_SESSION['fo_db_translate_cache'][$cacheKey] = $text;

    return $text;
}

function fo_db_e(?string $text): string
{
    return htmlspecialchars(fo_db($text), ENT_QUOTES, 'UTF-8');
}

function fo_html_lang_attr(): string
{
    return fo_lang();
}

function fo_html_dir_attr(): string
{
    return 'ltr';
}

function fo_html_mode_attr(): string
{
    return fo_mode() === 'dark' ? 'dark' : 'light';
}

function fo_theme_render_head(string $assetPrefix = ''): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!function_exists('fo_mode')) {
        require_once __DIR__ . '/fo_i18n.php';
    }
    if (!isset($_SESSION['fo_mode']) && function_exists('fo_init_i18n_for_request')) {
        fo_init_i18n_for_request();
    }

    $prefix = $assetPrefix === '' ? '' : rtrim($assetPrefix, '/') . '/';
    $mode = fo_html_mode_attr();
    $css = $prefix . 'css/hb-dark-mode.css';
    $modeJson = json_encode($mode, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    echo '<script>(function(){var m=' . ($modeJson ?: '"light"') . ';document.documentElement.setAttribute("data-hb-mode",m);document.documentElement.setAttribute("data-bs-theme",m==="dark"?"dark":"light");})();</script>' . "\n";
    echo '<link rel="stylesheet" href="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

function fo_theme_render_script(string $assetPrefix = ''): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $prefix = $assetPrefix === '' ? '' : rtrim($assetPrefix, '/') . '/';
    $js = $prefix . 'js/hb-theme.js';
    echo '<script src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
}

function fo_delivery_status_label(string $status): string
{
    $map = [
        'En préparation' => 'delivery.status_preparation',
        'En cours' => 'delivery.status_in_progress',
        'Livrée' => 'delivery.status_delivered',
        'Annulée' => 'delivery.status_cancelled',
    ];

    return isset($map[$status]) ? fo_t($map[$status]) : $status;
}

function fo_notification_format_date(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }

    return fo_lang() === 'en'
        ? date('m/d/Y g:i A', $ts)
        : date('d/m/Y H:i', $ts);
}

function fo_format_remaining_seconds(int $seconds): string
{
    $seconds = max(0, $seconds);
    if ($seconds < 60) {
        return fo_t('track.time_less_minute');
    }
    if ($seconds < 3600) {
        return sprintf(fo_t('track.time_min'), (int) ceil($seconds / 60));
    }
    if ($seconds < 86400) {
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);

        return $m > 0
            ? sprintf(fo_t('track.time_h_min'), $h, $m)
            : sprintf(fo_t('track.time_h'), $h);
    }
    $d = (int) floor($seconds / 86400);
    $h = (int) floor(($seconds % 86400) / 3600);

    return $h > 0
        ? sprintf(fo_t('track.time_d_h'), $d, $h)
        : sprintf(fo_t('track.time_d'), $d);
}

/**
 * Localize delivery timeline strings for the current UI language.
 *
 * @param array<string, mixed> $timeline
 * @return array<string, mixed>
 */
function fo_delivery_localize_timeline(array $timeline): array
{
    $statut = (string) ($timeline['statut'] ?? '');
    $timeline['statut'] = fo_delivery_status_label($statut);

    $phase = (string) ($timeline['phase'] ?? '');
    $now = time();
    $createdMs = (int) ($timeline['created_ms'] ?? 0);
    $enCoursMs = (int) ($timeline['en_cours_ms'] ?? 0);
    $arrivalMs = (int) ($timeline['arrival_ms'] ?? 0);

    if ($phase === 'annulee') {
        $timeline['eta_line'] = fo_t('delivery.status_cancelled');
        $timeline['sub_line'] = fo_t('delivery.cancelled_sub');

        return $timeline;
    }

    if ($phase === 'livree' && $arrivalMs > 0) {
        $arrival = (new DateTimeImmutable('@' . (int) floor($arrivalMs / 1000)))->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $timeline['eta_line'] = fo_t('delivery.delivered_eta');
        $timeline['sub_line'] = sprintf(
            fo_t('delivery.delivered_sub'),
            $arrival->format(fo_lang() === 'en' ? 'm/d/Y g:i A' : 'd/m/Y à H:i')
        );

        return $timeline;
    }

    if ($phase === 'encours') {
        if ($arrivalMs <= 0) {
            $timeline['eta_line'] = fo_t('delivery.status_in_progress');
            $timeline['sub_line'] = fo_t('delivery.in_progress_sub_calc');

            return $timeline;
        }

        $arrival = (new DateTimeImmutable('@' . (int) floor($arrivalMs / 1000)))->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $remainSec = max(0, (int) floor(($arrivalMs - ($now * 1000)) / 1000));
        $timeline['eta_line'] = sprintf(
            fo_t('delivery.eta_estimated'),
            $arrival->format(fo_lang() === 'en' ? 'm/d/Y g:i A' : 'd/m/Y à H:i')
        );
        $timeline['sub_line'] = sprintf(fo_t('delivery.arrival_in'), fo_format_remaining_seconds($remainSec));

        return $timeline;
    }

    if ($phase === 'preparation') {
        $timeline['eta_line'] = fo_t('delivery.status_preparation');
        $prepRemainSec = $enCoursMs > 0 ? max(0, (int) floor(($enCoursMs - ($now * 1000)) / 1000)) : 0;
        if ($arrivalMs > 0) {
            $arrival = (new DateTimeImmutable('@' . (int) floor($arrivalMs / 1000)))->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $timeline['sub_line'] = sprintf(
                fo_t('delivery.estimated_delivery'),
                $arrival->format(fo_lang() === 'en' ? 'm/d/Y g:i A' : 'd/m/Y à H:i'),
                fo_format_remaining_seconds($prepRemainSec)
            );
        } else {
            $timeline['sub_line'] = sprintf(fo_t('delivery.shipment_in'), fo_format_remaining_seconds($prepRemainSec));
        }
    }

    return $timeline;
}

/** @return array<string, string> */
function fo_i18n_client_dictionary(): array
{
    return fo_i18n_dictionary(fo_lang());
}

function fo_i18n_render_bootstrap(string $assetPrefix = ''): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    fo_init_i18n_for_request();

    $prefix = $assetPrefix === '' ? '' : rtrim($assetPrefix, '/') . '/';
    $js = $prefix . 'js/hb-i18n.js';
    $dict = fo_i18n_client_dictionary();
    $payload = json_encode([
        'lang' => fo_lang(),
        'dir' => fo_html_dir_attr(),
        'mode' => fo_mode(),
        'settingsSaveUrl' => $prefix . 'api/settings_save.php',
        'strings' => $dict,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    echo '<script>window.HB_I18N=' . ($payload ?: '{}') . ';</script>' . "\n";
    echo '<script src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
}
