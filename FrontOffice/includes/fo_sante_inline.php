<?php

declare(strict_types=1);

function fo_sante_inline_active(): bool
{
    return defined('FO_SANTE_INLINE') && FO_SANTE_INLINE;
}

function fo_sante_preserve_query(): array
{
    $out = [];
    foreach (['page', 'date', 'sort'] as $key) {
        if (isset($_GET[$key]) && (string) $_GET[$key] !== '') {
            $out[$key] = (string) $_GET[$key];
        }
    }

    return $out;
}

function fo_sante_list_url(string $mode, int $id = 0, array $extra = []): string
{
    $params = $extra;
    if ($mode !== '') {
        $params['fo'] = $mode;
        if ($id > 0) {
            $params['fo_id'] = (string) $id;
        }
    }

    $query = http_build_query($params);

    return 'user_health_space.php' . ($query !== '' ? '?' . $query : '');
}

function fo_sante_save_redirect(array $extra = []): void
{
    header('Location: ' . fo_sante_list_url('', 0, $extra));
    exit;
}

function fo_sante_redirect_if_standalone(string $mode, int $id = 0): void
{
    if (fo_sante_inline_active() || $_SERVER['REQUEST_METHOD'] === 'POST') {
        return;
    }

    header('Location: ' . fo_sante_list_url($mode, $id, fo_sante_preserve_query()));
    exit;
}

function fo_sante_inline_current_mode(): string
{
    $fo = trim((string) ($_GET['fo'] ?? ''));
    $allowed = ['create', 'edit', 'create_suivi', 'edit_suivi'];
    if (!in_array($fo, $allowed, true)) {
        return '';
    }
    if ($fo === 'edit_suivi') {
        $id = (int) ($_GET['fo_id'] ?? $_GET['id'] ?? 0);
        if ($id < 1) {
            return '';
        }
    }

    return $fo;
}

function fo_sante_inline_render_panel(bool $guestHealthSpace): void
{
    if ($guestHealthSpace) {
        return;
    }

    $mode = fo_sante_inline_current_mode();
    if ($mode === '') {
        return;
    }

    require_once __DIR__ . '/fo_sante_inline_shell.php';

    $preserve = fo_sante_preserve_query();
    $closeUrl = fo_sante_list_url('', 0, $preserve);

    fo_sante_inline_shell_open($mode);

    if (!defined('FO_SANTE_INLINE')) {
        define('FO_SANTE_INLINE', true);
    }

    $base = dirname(__DIR__);

    if ($mode === 'create') {
        include $base . '/create.php';
    } elseif ($mode === 'edit') {
        include $base . '/edit.php';
    } elseif ($mode === 'create_suivi') {
        include $base . '/createSuivi.php';
    } else {
        $_GET['id'] = (string) (int) ($_GET['fo_id'] ?? $_GET['id'] ?? 0);
        include $base . '/editSuivi.php';
    }

    fo_sante_inline_shell_close($closeUrl);
}
