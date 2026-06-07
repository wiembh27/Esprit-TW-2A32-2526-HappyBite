<?php

declare(strict_types=1);

function fo_catalog_inline_active(): bool
{
    return defined('FO_CATALOG_INLINE') && FO_CATALOG_INLINE;
}

function fo_inline_preserve_list_query(): array
{
    $out = [];
    foreach (['search', 'id_categorie'] as $key) {
        if (isset($_GET[$key]) && (string) $_GET[$key] !== '') {
            $out[$key] = (string) $_GET[$key];
        }
    }

    return $out;
}

function fo_inline_preserve_produit_browse_query(): array
{
    $out = [];
    foreach (['action', 'motCle', 'id_categorie'] as $key) {
        if (isset($_GET[$key]) && (string) $_GET[$key] !== '') {
            $out[$key] = (string) $_GET[$key];
        }
    }

    return $out;
}

function fo_inline_preserve_recette_browse_query(): array
{
    $out = [];
    foreach (['action', 'motCle'] as $key) {
        if (isset($_GET[$key]) && (string) $_GET[$key] !== '') {
            $out[$key] = (string) $_GET[$key];
        }
    }

    return $out;
}

function fo_inline_crud_list_url(string $listPage, string $mode, int $id = 0, array $extra = []): string
{
    $params = $extra;
    if ($mode !== '') {
        $params['fo'] = $mode;
        if ($id > 0) {
            $params['fo_id'] = (string) $id;
        }
    }

    $query = http_build_query($params);

    return $listPage . ($query !== '' ? '?' . $query : '');
}

function fo_catalog_save_redirect(string $listPage, array $extra = []): void
{
    header('Location: ' . fo_inline_crud_list_url($listPage, '', 0, array_merge(fo_inline_preserve_list_query(), $extra)));
    exit;
}

function fo_inline_crud_redirect_if_standalone(string $listPage, string $mode, int $id = 0): void
{
    if (fo_catalog_inline_active() || $_SERVER['REQUEST_METHOD'] === 'POST') {
        return;
    }

    header('Location: ' . fo_inline_crud_list_url($listPage, $mode, $id, fo_inline_preserve_list_query()));
    exit;
}
