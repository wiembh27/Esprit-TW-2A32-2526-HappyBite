<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';

require_once __DIR__ . '/../Model/User.php';

require_once __DIR__ . '/includes/bo_user_admin_nav.php';

use Model\User;

$userModel = new User();
$users = $userModel->findAll();
$stats = $userModel->getStats();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — Utilisateurs (liste)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo page-list-com-liv page-bo-users-admin">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('utilisateur');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1100px; width: 100%;">
        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <?php bo_user_admin_nav('utilisateur'); ?>
            </div>
        </div>

        <div class="liste-com-liv-title-row">
            <div>
                <h1 class="liste-com-liv-title">Liste des utilisateurs</h1>
                <p class="liste-com-liv-subtitle">
                    Liste des comptes
                    <?php if (!empty($_SESSION['bo_user_prenom'])) { ?>
                        — connecté : <strong><?php echo htmlspecialchars((string) $_SESSION['bo_user_prenom'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php } ?>
                </p>
            </div>
        </div>

        <div class="bo-stats-grid bo-home-stats-row bo-home-stats-row--4" style="margin-bottom: 16px;">
            <article class="bo-stat-card bo-home-stat bo-home-stat--c1">
                <div class="bo-home-stat-emoji" aria-hidden="true">👥</div>
                <h3>Total utilisateurs</h3>
                <p><?php echo (int) ($stats['total'] ?? 0); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c4">
                <div class="bo-home-stat-emoji" aria-hidden="true">🛡️</div>
                <h3>Administrateurs</h3>
                <p><?php echo (int) ($stats['admins'] ?? 0); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c5">
                <div class="bo-home-stat-emoji" aria-hidden="true">👤</div>
                <h3>Clients</h3>
                <p><?php echo (int) ($stats['clients'] ?? 0); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c6">
                <div class="bo-home-stat-emoji" aria-hidden="true">🥗</div>
                <h3>Nutritionnistes</h3>
                <p><?php echo (int) ($stats['nutritionnistes'] ?? 0); ?></p>
            </article>
        </div>

        <section class="bo-table-wrap" aria-label="Tableau utilisateurs">
            <?php if ($users === []) { ?>
                <div class="bo-empty">Aucun utilisateur.</div>
            <?php } else { ?>
                <div class="bo-table-scroll">
                    <table class="bo-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom et prénom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) { ?>
                                <?php
                                $userId = (int) ($user['id'] ?? $user['id_utilisateur'] ?? 0);
                                $nomComplet = (string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                                $email = (string) ($user['email'] ?? '');
                                $role = (string) ($user['role'] ?? '');
                                $statutRaw = (string) ($user['statut'] ?? '');
                                $statutKey = str_replace('é', 'e', strtolower($statutRaw));
                                $pill = 'bo-pill bo-pill--muted';
                                if ($statutKey === 'actif') {
                                    $pill = 'bo-pill bo-pill--success';
                                } elseif (str_contains($statutKey, 'bloque')) {
                                    $pill = 'bo-pill bo-pill--danger';
                                }
                                ?>
                                <tr>
                                    <td class="bo-td-center"><?php echo $userId; ?></td>
                                    <td class="bo-td-left"><strong><?php echo htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td class="bo-td-left"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="bo-td-center">
                                        <span class="bo-pill bo-pill--muted"><?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td class="bo-td-center">
                                        <span class="<?php echo htmlspecialchars($pill, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statutRaw !== '' ? $statutRaw : '—', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
</body>
</html>
