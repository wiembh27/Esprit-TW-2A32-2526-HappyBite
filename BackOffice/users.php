<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';

require_once __DIR__ . '/../Model/User.php';

require_once __DIR__ . '/includes/bo_user_admin_nav.php';

use Model\User;

$userModel = new User();

if (isset($_GET['action']) && $_GET['action'] === 'detail' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $detail = $userModel->getUserDetail($id);
    header('Content-Type: application/json');
    echo json_encode($detail ?: []);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'toggle' || $action === 'delete') {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            echo json_encode(['ok' => false, 'error' => 'invalid_id']);
            exit;
        }
        if ($action === 'toggle') {
            $ok = $userModel->toggleStatut($id);
            echo json_encode(['ok' => $ok, 'error' => $ok ? null : 'toggle_failed']);
            exit;
        }
        $actor = (int) ($_SESSION['bo_user_id'] ?? 0);
        $ok = $userModel->deleteById($id, $actor > 0 ? $actor : null);
        echo json_encode(['ok' => $ok, 'error' => $ok ? null : 'delete_failed']);
        exit;
    }
}

$iconModify = is_file(__DIR__ . '/images/modify.png') ? 'images/modify.png' : 'images/modify.svg';
$iconDelete = is_file(__DIR__ . '/images/delete.png') ? 'images/delete.png' : 'images/delete.svg';

$kpis = $userModel->getKPIs();
$growth_day = $userModel->getGrowth('day');
$growth_week = $userModel->getGrowth('week');
$growth_month = $userModel->getGrowth('month');
$activity = $userModel->getActivityStats(10);
$inactive = $userModel->getInactiveUsers(30);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — Utilisateurs (dashboard)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo page-list-com-liv page-bo-users-dashboard">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('utilisateur');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1100px; width: 100%;">
        <style>
            .page-bo-users-dashboard .bo-users-controls {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }
            .page-bo-users-dashboard .bo-users-controls h2 {
                margin: 0;
                flex: 1 1 200px;
                font-size: 1.1rem;
                font-weight: 700;
                color: #1f3a28;
            }
            .page-bo-users-dashboard .bo-users-controls-tools {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }
            .page-bo-users-dashboard .bo-users-controls-tools input[type="text"],
            .page-bo-users-dashboard .bo-users-controls-tools select {
                padding: 0.5rem 0.65rem;
                border-radius: 8px;
                border: 1px solid var(--bo-border, #d8e8dc);
                font-family: inherit;
            }
            .page-bo-users-dashboard .bo-users-controls-tools input[type="text"] { min-width: 180px; }
            .page-bo-users-dashboard .bo-users-two-col {
                display: grid;
                grid-template-columns: 1fr minmax(260px, 300px);
                gap: 1rem;
                margin-top: 1rem;
                align-items: start;
            }
            @media (max-width: 900px) {
                .page-bo-users-dashboard .bo-users-two-col { grid-template-columns: 1fr; }
            }
            .page-bo-users-dashboard .user-row { cursor: pointer; }
            .page-bo-users-dashboard .modal {
                position: fixed;
                inset: 0;
                z-index: 2000;
                display: none;
                background: rgba(0, 0, 0, 0.45);
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .page-bo-users-dashboard .modal .card {
                background: #fff;
                padding: 1.5rem;
                border-radius: 12px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
                border: 1px solid var(--bo-border, #d8e8dc);
            }
            .page-bo-users-dashboard .bo-users-aside h2 {
                font-size: 1.05rem;
                margin: 0 0 0.75rem;
                color: #1f3a28;
            }
            .page-bo-users-dashboard .bo-users-aside ul {
                margin: 0;
                padding-left: 1.1rem;
                font-size: 0.9rem;
                color: #2f3d36;
            }
            .page-bo-users-dashboard .bo-table-actions {
                display: inline-flex;
                gap: 8px;
                align-items: center;
                justify-content: center;
                flex-wrap: wrap;
            }
        </style>

        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <?php bo_user_admin_nav('dashboard'); ?>
            </div>
        </div>

        <div class="liste-com-liv-title-row">
            <div>
                <h1 class="liste-com-liv-title">Dashboard utilisateurs</h1>
                <p class="liste-com-liv-subtitle">Indicateurs, croissance et activité</p>
            </div>
            <div class="liste-com-liv-title-actions">
                <button type="button" id="btnExport" class="btn-commande-outline btn-vue-toggle">Export PDF</button>
                <button type="button" id="btnNewUser" class="btn-commande-primary btn-vue-toggle">Nouvel utilisateur</button>
            </div>
        </div>

        <div class="bo-stats-grid bo-home-stats-row bo-home-stats-row--5" style="margin-bottom: 16px;">
            <article class="bo-stat-card bo-home-stat bo-home-stat--c1">
                <div class="bo-home-stat-emoji" aria-hidden="true">👥</div>
                <h3>Total utilisateurs</h3>
                <p><?php echo htmlspecialchars((string) ($kpis['total'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c2">
                <div class="bo-home-stat-emoji" aria-hidden="true">✨</div>
                <h3>Nouveaux aujourd'hui</h3>
                <p><?php echo htmlspecialchars((string) ($kpis['new_today'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c3">
                <div class="bo-home-stat-emoji" aria-hidden="true">📆</div>
                <h3>Nouveaux cette semaine</h3>
                <p><?php echo htmlspecialchars((string) ($kpis['new_week'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c5">
                <div class="bo-home-stat-emoji" aria-hidden="true">✅</div>
                <h3>Actifs</h3>
                <p><?php echo htmlspecialchars((string) ($kpis['active'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c6">
                <div class="bo-home-stat-emoji" aria-hidden="true">📈</div>
                <h3>Taux de rétention</h3>
                <p><?php echo htmlspecialchars((string) ($kpis['retention_rate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> %</p>
            </article>
        </div>

        <section class="bo-panel" aria-label="Croissance">
            <div class="bo-users-controls">
                <h2>Croissance des utilisateurs</h2>
                <div class="bo-users-controls-tools">
                    <input id="searchInput" type="text" placeholder="Rechercher…" autocomplete="off">
                    <select id="periodSelect" aria-label="Période">
                        <option value="day">Jour (30 j.)</option>
                        <option value="week">Semaine (12 sem.)</option>
                        <option value="month">Mois (12 mois)</option>
                    </select>
                </div>
            </div>
            <div class="bo-home-chart-wrap" style="min-height: 240px;">
                <canvas id="usersGrowthChart" aria-label="Graphique de croissance"></canvas>
            </div>
        </section>

        <div class="bo-users-two-col">
            <section class="bo-panel" aria-label="Activité">
                <h2 class="liste-com-liv-subtitle" style="margin: 0 0 1rem;">Activité utilisateurs</h2>
                <div class="bo-table-scroll" id="usersCard">
                    <table class="bo-table" id="usersTable">
                        <thead>
                            <tr>
                                <th data-key="id">ID</th>
                                <th data-key="name">Nom</th>
                                <th data-key="email">Email</th>
                                <th data-key="logins">Connexions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity as $u) { ?>
                                <?php
                                $uid = (int) ($u['id'] ?? $u['id_utilisateur'] ?? 0);
                                $name = (string) (($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
                                $email = (string) ($u['email'] ?? '');
                                $statutKey = str_replace(['é', 'è'], 'e', strtolower(trim((string) ($u['statut'] ?? ''))));
                                $isBlocked = str_contains($statutKey, 'bloque');
                                $lockSrc = $isBlocked ? 'images/lock-closed.svg' : 'images/lock-open.svg';
                                $lockTitle = $isBlocked ? 'Débloquer' : 'Bloquer';
                                ?>
                                <tr class="user-row" data-id="<?php echo $uid; ?>">
                                    <td class="bo-td-center col-id"><?php echo $uid; ?></td>
                                    <td class="bo-td-left col-name"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="bo-td-left col-email"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="bo-td-center col-logins"><?php echo htmlspecialchars((string) ($u['logins'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="bo-td-center col-actions">
                                        <span class="bo-table-actions">
                                            <a class="bo-img-link" href="edit_user.php?id=<?php echo $uid; ?>" title="Modifier" aria-label="Modifier"><img src="<?php echo htmlspecialchars($iconModify, ENT_QUOTES, 'UTF-8'); ?>" width="22" height="22" alt=""></a>
                                            <button type="button" class="bo-img-link btn-toggle" data-id="<?php echo $uid; ?>" data-blocked="<?php echo $isBlocked ? '1' : '0'; ?>" title="<?php echo htmlspecialchars($lockTitle, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($lockTitle, ENT_QUOTES, 'UTF-8'); ?>"><img src="<?php echo htmlspecialchars($lockSrc, ENT_QUOTES, 'UTF-8'); ?>" width="22" height="22" alt=""></button>
                                            <button type="button" class="bo-img-link btn-delete" data-id="<?php echo $uid; ?>" title="Supprimer" aria-label="Supprimer"><img src="<?php echo htmlspecialchars($iconDelete, ENT_QUOTES, 'UTF-8'); ?>" width="22" height="22" alt=""></button>
                                        </span>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="bo-panel bo-users-aside" aria-label="Inactifs">
                <h2>Utilisateurs inactifs (30+ j.)</h2>
                <div><strong><?php echo count($inactive); ?></strong> compte(s) concerné(s)</div>
                <ul style="margin-top: 0.75rem;">
                    <?php foreach ($inactive as $i) { ?>
                        <li>
                            <?php echo htmlspecialchars((string) (($i['prenom'] ?? '') . ' ' . ($i['nom'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                            — dernière activité :
                            <?php echo htmlspecialchars((string) ($i['last_login'] ?? 'Jamais'), ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                    <?php } ?>
                </ul>
            </aside>
        </div>

        <div class="modal" id="userModal" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
            <div class="card">
                <h2 id="userModalTitle" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">Détail utilisateur</h2>
                <div id="modalContent">Chargement…</div>
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" id="closeModal" class="btn-commande-outline btn-vue-toggle">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const growthData = {
        day: <?php echo json_encode($growth_day); ?>,
        week: <?php echo json_encode($growth_week); ?>,
        month: <?php echo json_encode($growth_month); ?>
    };
</script>
<script src="js/users.js"></script>

<?php bo_layout_end(); ?>
</body>
</html>
