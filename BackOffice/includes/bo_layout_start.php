<?php
declare(strict_types=1);

/**
 * @param string $sidebarActive ''|'produit'|'comliv'|'communaute'|'post'|'utilisateur'|'sante'
 * @param string $topActive     (unused - kept for compatibility)
 */
function bo_layout_start(string $sidebarActive, string $topActive = ''): void
{
    ?>
<div class="bo-main bo-main-embedded">
    <div class="bo-content bo-content-embedded">
    <?php
}

function bo_layout_end(): void
{
    ?>
    </div>
</div>
<footer>
    © 2026 HappyBite
</footer>
    <?php
    require_once __DIR__ . '/hb_brand_head.php';
    bo_brand_render_footer();
}
