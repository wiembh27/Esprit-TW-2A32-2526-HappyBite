<?php

declare(strict_types=1);

function fo_catalog_fragment_request(): bool
{
    return isset($_GET['fragment']) && (string) $_GET['fragment'] === '1';
}

function fo_catalog_fragment_bootstrap(): bool
{
    if (fo_catalog_fragment_request()) {
        if (!defined('FO_CATALOG_INLINE')) {
            define('FO_CATALOG_INLINE', true);
        }

        return true;
    }

    return defined('FO_CATALOG_INLINE') && FO_CATALOG_INLINE;
}
