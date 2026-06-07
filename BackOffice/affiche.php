<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/../Controllers/SuiviJournalierController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$controller = new SuiviJournalierController();
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

if ($search !== '') {
    $users = $controller->searchUsersBackoffice($search);
} else {
    $users = $controller->listUsersBackoffice();
}

$pieStats = $controller->getStatsProfilsVsNon();

$iconDetails = is_file(__DIR__ . '/images/details.png') ? 'images/details.png' : 'images/details.svg';

function formatList($data): array
{
    if (empty($data)) {
        return [];
    }
    if (is_array($data)) {
        return $data;
    }
    $decoded = json_decode((string) $data, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    return explode(',', (string) $data);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Santé — Utilisateurs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-sante-users .commande-wrap { padding-top: 8px; }
        .page-sante-users .btn-commande-primary,
        .page-sante-users .btn-commande-outline,
        .page-sante-users .bo-btn-primary {
            text-decoration: none !important;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .sante-badge {
            display: inline-block; padding: 4px 10px; margin: 2px; border-radius: 999px;
            font-size: 12px; font-weight: 600; background: #e8f5e9; color: #1f6b31; border: 1px solid #b6ddbb;
        }
        .bo-table-actions { display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap; justify-content: center; }
        .bo-table-btn {
            display: inline-block; padding: 6px 10px; border-radius: 8px; font-size: 12px;
            text-decoration: none; line-height: 1.2; border: 1px solid transparent;
        }
        .bo-table-btn--view { background: #2563eb; border-color: #1d4ed8; color: #fff; }
        .bo-table-btn:hover { filter: brightness(0.96); }
        .page-sante-users .bo-form-row { grid-template-columns: 1fr auto; }
        .sante-search-actions { display: inline-flex; align-items: center; gap: 10px; }
        .page-sante-users .sante-pie-panel .sante-pie-wrap {
            position: relative;
            width: 100%;
            height: 280px;
            max-width: 100%;
            margin: 0;
        }
        .page-sante-users .sante-pie-panel .sante-pie-wrap canvas {
            max-width: 100% !important;
            width: 100% !important;
            height: 100% !important;
        }
        .bo-table-pager {
            display: flex; align-items: center; justify-content: flex-end; gap: 10px;
            margin-top: 10px; flex-wrap: wrap;
        }
        .bo-pager-arrow {
            width: 36px; height: 36px; border-radius: 999px; border: 2px solid #2c7e34;
            background: #e8f5e9; color: #1f6b31; cursor: pointer; display: inline-flex;
            align-items: center; justify-content: center; line-height: 1;
        }
        .bo-pager-arrow[disabled] { opacity: 0.45; cursor: not-allowed; }
        .bo-pager-info { color: #1f3a28; font-size: 13px; }
    </style>
</head>
<body class="page-bo page-list-com-liv page-sante-users">
<?php bo_layout_start('sante'); ?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1100px; width: 100%;">
        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <a href="affiche.php" class="btn-commande-primary is-active btn-vue-toggle">Santé</a>
            </div>
        </div>

        <div class="liste-com-liv-title-row">
            <div>
                <h1 class="liste-com-liv-title">Profils santé des utilisateurs</h1>
                <p class="liste-com-liv-subtitle">Tableau de bord santé et suivi utilisateur</p>
            </div>
            <div class="liste-com-liv-title-actions">
                <a href="export_users_pdf.php?search=<?php echo urlencode($search); ?>" class="btn-commande-primary" target="_blank" rel="noopener">Exporter PDF</a>
            </div>
        </div>

        <section class="bo-panel" aria-label="Recherche santé">
            <form method="get" action="affiche.php">
                <div class="bo-form-row">
                    <div class="bo-field">
                        <label for="search">Recherche</label>
                        <input id="search" type="text" name="search" placeholder="Nom, email ou ID..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="bo-field bo-field-submit">
                        <div class="sante-search-actions">
                            <button type="submit" class="bo-btn-primary">Rechercher</button>
                            <a href="affiche.php" class="btn-commande-outline">Réinitialiser</a>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section class="bo-panel sante-pie-panel" aria-label="Répartition des utilisateurs">
            <h2 class="liste-com-liv-subtitle" style="margin: 0 0 1rem;">Répartition des utilisateurs</h2>
            <div class="sante-pie-wrap">
                <canvas id="pieChart"></canvas>
            </div>
        </section>

        <section class="bo-table-wrap" aria-label="Tableau utilisateurs santé">
            <div class="bo-table-scroll" id="sante-users-table-wrap">
                <table class="bo-table" id="santeUsersTable">
                    <thead>
                        <tr>
                            <th>ID profil</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Taille</th>
                            <th>Poids</th>
                            <th>Objectif</th>
                            <th>Allergènes</th>
                            <th>Carences</th>
                            <th>Maladies</th>
                            <th>Date MAJ</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($users === []): ?>
                        <tr>
                            <td colspan="11" class="bo-empty">Aucun utilisateur trouvé<?php echo $search !== '' ? ' pour "' . htmlspecialchars($search) . '"' : ''; ?>.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($user['id_profil_sante'] ?? '')); ?></td>
                                <td class="bo-td-left"><?php echo htmlspecialchars(trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')))); ?></td>
                                <td class="bo-td-left"><?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($user['taille'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($user['poids_actuel'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($user['objectif'] ?? '')); ?></td>
                                <td class="bo-td-left">
                                    <?php foreach (formatList($user['allergenes'] ?? '') as $a): ?>
                                        <?php $v = trim((string) $a); if ($v === '') { continue; } ?>
                                        <span class="sante-badge"><?php echo htmlspecialchars($v); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="bo-td-left">
                                    <?php foreach (formatList($user['carences'] ?? '') as $c): ?>
                                        <?php $v = trim((string) $c); if ($v === '') { continue; } ?>
                                        <span class="sante-badge"><?php echo htmlspecialchars($v); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="bo-td-left">
                                    <?php foreach (formatList($user['maladies'] ?? '') as $m): ?>
                                        <?php $v = trim((string) $m); if ($v === '') { continue; } ?>
                                        <span class="sante-badge"><?php echo htmlspecialchars($v); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($user['date_mise_a_jour'] ?? '')); ?></td>
                                <td class="bo-td-center">
                                    <span class="bo-table-actions">
                                        <a class="bo-img-link" href="details.php?id=<?php echo urlencode((string) ($user['id_utilisateur'] ?? '')); ?>" title="Voir le détail" aria-label="Voir le détail"><img src="<?php echo htmlspecialchars($iconDetails, ENT_QUOTES, 'UTF-8'); ?>" width="22" height="22" alt=""></a>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="bo-table-pager" id="santeUsersPager">
                <button type="button" class="bo-pager-arrow" id="santeUsersPrev" aria-label="Précédent">‹</button>
                <span class="bo-pager-info" id="santeUsersInfo">Page 1 / 1</span>
                <button type="button" class="bo-pager-arrow" id="santeUsersNext" aria-label="Suivant">›</button>
            </div>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        var canvas = document.getElementById('pieChart');
        if (!canvas || !window.Chart) return;
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Avec profil santé', 'Sans profil santé'],
                datasets: [{
                    data: [<?php echo (int) ($pieStats['avec'] ?? 0); ?>, <?php echo (int) ($pieStats['sans'] ?? 0); ?>],
                    backgroundColor: ['#2c7e34', '#ef4444'],
                    radius: 108
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '45%',
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 28, font: { family: 'Poppins', weight: 500 } }
                    }
                },
                layout: { padding: 0 }
            }
        });
    })();
    (function () {
        var table = document.getElementById('santeUsersTable');
        if (!table) return;
        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
        if (!rows.length) return;
        var pager = document.getElementById('santeUsersPager');
        var prev = document.getElementById('santeUsersPrev');
        var next = document.getElementById('santeUsersNext');
        var info = document.getElementById('santeUsersInfo');
        if (!pager || !prev || !next || !info) return;
        if (rows.length === 1 && rows[0].querySelector('.bo-empty')) {
            pager.hidden = true;
            return;
        }
        var perPage = 5;
        var page = 1;
        function render() {
            var totalPages = Math.max(1, Math.ceil(rows.length / perPage));
            if (page > totalPages) page = totalPages;
            var start = (page - 1) * perPage;
            var end = start + perPage;
            rows.forEach(function (row, i) {
                row.hidden = !(i >= start && i < end);
            });
            info.textContent = 'Page ' + page + ' / ' + totalPages;
            prev.disabled = page <= 1;
            next.disabled = page >= totalPages;
        }
        prev.addEventListener('click', function () {
            if (page > 1) { page--; render(); }
        });
        next.addEventListener('click', function () {
            var totalPages = Math.max(1, Math.ceil(rows.length / perPage));
            if (page < totalPages) { page++; render(); }
        });
        render();
    })();
</script>
</body>
</html>