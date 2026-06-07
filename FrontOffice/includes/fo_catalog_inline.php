<?php

declare(strict_types=1);

require_once __DIR__ . '/fo_inline_crud.php';

function fo_catalog_inline_current_mode(): string
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== 'List-Produit-Fournisseur.php') {
        return '';
    }

    $fo = trim((string) ($_GET['fo'] ?? ''));
    if (!in_array($fo, ['add', 'edit'], true)) {
        return '';
    }
    if ($fo === 'edit') {
        $id = (int) ($_GET['fo_id'] ?? $_GET['id'] ?? 0);
        if ($id < 1) {
            return '';
        }
    }

    return $fo;
}

function fo_catalog_inline_render_panel(): void
{
    $mode = fo_catalog_inline_current_mode();
    if ($mode === '') {
        return;
    }

    require_once __DIR__ . '/fo_catalog_inline_shell.php';

    $preserve = fo_inline_preserve_list_query();
    $closeUrl = fo_inline_crud_list_url('List-Produit-Fournisseur.php', '', 0, $preserve);

    fo_catalog_inline_shell_open($mode);

    if (!defined('FO_CATALOG_INLINE')) {
        define('FO_CATALOG_INLINE', true);
    }

    $base = dirname(__DIR__);

    if ($mode === 'add') {
        include $base . '/Add-Produit-Fournisseur.php';
    } else {
        $_GET['id'] = (string) (int) ($_GET['fo_id'] ?? $_GET['id'] ?? 0);
        include $base . '/Edit-Produit-Fournisseur.php';
    }

    fo_catalog_inline_shell_close($closeUrl);
}
