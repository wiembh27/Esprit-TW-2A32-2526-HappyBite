<?php

require_once __DIR__ . '/../../FrontOffice/includes/hb_brand_head.php';

function bo_brand_is_embedded(): bool
{
    $embed = $_GET['embed'] ?? null;
    if ($embed === null || $embed === '') {
        return false;
    }

    return (string) $embed !== '0';
}

function bo_brand_render_head(): void
{
    hb_brand_render_head('../FrontOffice/', 'images/tiny_logo.png');
}

function bo_brand_render_embed_helpers(): void
{
    static $done = false;
    if ($done || !bo_brand_is_embedded()) {
        return;
    }
    $done = true;
    ?>
<script>
(function () {
    'use strict';
    if (window.self === window.top) {
        return;
    }
    function patchLinks() {
        document.querySelectorAll('a[href]').forEach(function (a) {
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) {
                return;
            }
            if (a.target === '_top' || a.target === '_parent' || a.target === '_blank') {
                return;
            }
            try {
                var url = new URL(href, window.location.href);
                if (url.origin !== window.location.origin || url.searchParams.has('embed')) {
                    return;
                }
                url.searchParams.set('embed', '1');
                a.setAttribute('href', url.pathname + url.search + url.hash);
            } catch (e) { /* ignore malformed href */ }
        });
    }
    patchLinks();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', patchLinks);
    }
})();
</script>
<?php
}

function bo_brand_render_footer(): void
{
    if (bo_brand_is_embedded()) {
        bo_brand_render_embed_helpers();

        return;
    }

    hb_brand_render_footer('../FrontOffice/');
}
