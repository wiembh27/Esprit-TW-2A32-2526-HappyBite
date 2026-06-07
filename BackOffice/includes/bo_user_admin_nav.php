<?php

declare(strict_types=1);

/**
 * Onglets même style que list-com-liv : parent doit être
 * <div class="liste-com-liv-topbar"><div class="mode-buttons"> … </div></div>
 *
 * @param 'utilisateur'|'dashboard' $active
 */
function bo_user_admin_nav(string $active): void
{
    $isList = $active === 'utilisateur';
    $isDash = $active === 'dashboard';

    $classU = $isList ? 'btn-commande-primary is-active btn-vue-toggle' : 'btn-commande-outline btn-vue-toggle';
    $classD = $isDash ? 'btn-commande-primary is-active btn-vue-toggle' : 'btn-commande-outline btn-vue-toggle';
    ?>
        <a href="admin.php" class="<?php echo htmlspecialchars($classU, ENT_QUOTES, 'UTF-8'); ?>">Utilisateur</a>
        <a href="users.php" class="<?php echo htmlspecialchars($classD, ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a>
    <?php
}
