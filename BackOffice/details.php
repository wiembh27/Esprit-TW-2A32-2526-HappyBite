<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/../Controllers/SuiviJournalierController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$controller = new SuiviJournalierController();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    header('Location: affiche.php');
    exit;
}

$user = $controller->getUser($id);
$suivis = $controller->getSuiviUser($id);

$date = isset($_GET['date']) ? (string) $_GET['date'] : '';
$sort = isset($_GET['sort']) ? (string) $_GET['sort'] : '';
$suivisFiltered = $suivis;

if ($date !== '') {
    $suivisFiltered = array_values(array_filter($suivisFiltered, static function ($s) use ($date): bool {
        return (string) ($s['date_jour'] ?? '') === $date;
    }));
}

if ($sort !== '') {
    $map = [
        'poids_asc' => static fn($a, $b) => ((float) ($a['poids'] ?? 0)) <=> ((float) ($b['poids'] ?? 0)),
        'poids_desc' => static fn($a, $b) => ((float) ($b['poids'] ?? 0)) <=> ((float) ($a['poids'] ?? 0)),
        'calories_asc' => static fn($a, $b) => ((float) ($a['calories'] ?? 0)) <=> ((float) ($b['calories'] ?? 0)),
        'calories_desc' => static fn($a, $b) => ((float) ($b['calories'] ?? 0)) <=> ((float) ($a['calories'] ?? 0)),
        'sommeil_asc' => static fn($a, $b) => ((float) ($a['sommeil_heures'] ?? 0)) <=> ((float) ($b['sommeil_heures'] ?? 0)),
        'sommeil_desc' => static fn($a, $b) => ((float) ($b['sommeil_heures'] ?? 0)) <=> ((float) ($a['sommeil_heures'] ?? 0)),
        'pas_asc' => static fn($a, $b) => ((float) ($a['nbr_pas'] ?? 0)) <=> ((float) ($b['nbr_pas'] ?? 0)),
        'pas_desc' => static fn($a, $b) => ((float) ($b['nbr_pas'] ?? 0)) <=> ((float) ($a['nbr_pas'] ?? 0)),
    ];
    if (isset($map[$sort])) {
        usort($suivisFiltered, $map[$sort]);
    }
}

$labels = [];
$poids = [];
$calories = [];
$sommeil = [];
$pas = [];
foreach ($suivisFiltered as $row) {
    $labels[] = (string) ($row['date_jour'] ?? '');
    $poids[] = (float) ($row['poids'] ?? 0);
    $calories[] = (float) ($row['calories'] ?? 0);
    $sommeil[] = (float) ($row['sommeil_heures'] ?? 0);
    $pas[] = (float) ($row['nbr_pas'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Santé — Détails utilisateur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-sante-details .commande-wrap { padding-top: 8px; }
        .page-sante-details .btn-commande-primary,
        .page-sante-details .btn-commande-outline,
        .page-sante-details .bo-btn-primary {
            text-decoration: none !important;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .sante-user-meta { margin: 6px 0 0; color: #334155; font-size: 14px; }
        .page-sante-details .bo-form-row { grid-template-columns: 1fr 1fr auto; }
        .sante-filter-actions { display: inline-flex; align-items: center; gap: 10px; }
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
<body class="page-bo page-list-com-liv page-sante-details">
<?php bo_layout_start('sante'); ?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1100px; width: 100%;">
        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <a href="affiche.php" class="btn-commande-outline btn-vue-toggle">Santé</a>
                <a href="details.php?id=<?php echo urlencode((string) $id); ?>" class="btn-commande-primary btn-vue-toggle is-active">Détails</a>
            </div>
        </div>

        <div class="liste-com-liv-title-row">
            <div>
                <h1 class="liste-com-liv-title">Suivi santé utilisateur</h1>
                <p class="liste-com-liv-subtitle">Historique et tendances santé</p>
                <p class="sante-user-meta">
                    <?php echo htmlspecialchars(trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')))); ?>
                    — <?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?>
                </p>
            </div>
            <div class="liste-com-liv-title-actions">
                <a href="export_pdf.php?id=<?php echo urlencode((string) $id); ?>" target="_blank" rel="noopener" class="btn-commande-primary">Exporter PDF</a>
            </div>
        </div>

        <section class="bo-panel" aria-label="Filtres de suivi">
            <form method="get" action="details.php">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <div class="bo-form-row">
                    <div class="bo-field">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
                    </div>
                    <div class="bo-field">
                        <label for="sort">Trier par</label>
                        <select id="sort" name="sort">
                            <option value="">Tri par défaut (date)</option>
                            <option value="poids_asc"<?php echo $sort === 'poids_asc' ? ' selected' : ''; ?>>Poids ↑</option>
                            <option value="poids_desc"<?php echo $sort === 'poids_desc' ? ' selected' : ''; ?>>Poids ↓</option>
                            <option value="calories_asc"<?php echo $sort === 'calories_asc' ? ' selected' : ''; ?>>Calories ↑</option>
                            <option value="calories_desc"<?php echo $sort === 'calories_desc' ? ' selected' : ''; ?>>Calories ↓</option>
                            <option value="sommeil_asc"<?php echo $sort === 'sommeil_asc' ? ' selected' : ''; ?>>Sommeil ↑</option>
                            <option value="sommeil_desc"<?php echo $sort === 'sommeil_desc' ? ' selected' : ''; ?>>Sommeil ↓</option>
                            <option value="pas_asc"<?php echo $sort === 'pas_asc' ? ' selected' : ''; ?>>Pas ↑</option>
                            <option value="pas_desc"<?php echo $sort === 'pas_desc' ? ' selected' : ''; ?>>Pas ↓</option>
                        </select>
                    </div>
                    <div class="bo-field bo-field-submit">
                        <div class="sante-filter-actions">
                            <button type="submit" class="bo-btn-primary">Filtrer</button>
                            <a href="details.php?id=<?php echo (int) $id; ?>" class="btn-commande-outline">Réinitialiser</a>
                            <a href="affiche.php" class="btn-commande-outline">Retour</a>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section class="bo-charts-grid bo-dash-charts-tall" style="margin-bottom: 16px;">
            <section class="bo-panel">
                <h2>Poids</h2>
                <div class="bo-home-chart-wrap">
                    <canvas id="chartPoids"></canvas>
                </div>
            </section>
            <section class="bo-panel">
                <h2>Calories</h2>
                <div class="bo-home-chart-wrap">
                    <canvas id="chartCalories"></canvas>
                </div>
            </section>
            <section class="bo-panel">
                <h2>Sommeil</h2>
                <div class="bo-home-chart-wrap">
                    <canvas id="chartSommeil"></canvas>
                </div>
            </section>
            <section class="bo-panel">
                <h2>Pas</h2>
                <div class="bo-home-chart-wrap">
                    <canvas id="chartPas"></canvas>
                </div>
            </section>
        </section>

        <section class="bo-table-wrap" aria-label="Tableau suivi santé">
            <div class="bo-table-scroll">
                <table class="bo-table" id="santeDetailsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Poids</th>
                            <th>Calories</th>
                            <th>Sommeil</th>
                            <th>Pas</th>
                            <th>Sport</th>
                            <th>Hydratation</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($suivisFiltered === []): ?>
                        <tr><td colspan="7" class="bo-empty">Aucune donnée de suivi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suivisFiltered as $s): ?>
                            <tr>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($s['date_jour'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($s['poids'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($s['calories'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($s['sommeil_heures'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($s['nbr_pas'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($s['nbr_activites_sport'] ?? '')); ?></td>
                                <td class="bo-td-center"><?php echo htmlspecialchars((string) ($s['hydratation_litre'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="bo-table-pager" id="santeDetailsPager">
                <button type="button" class="bo-pager-arrow" id="santeDetailsPrev" aria-label="Précédent">‹</button>
                <span class="bo-pager-info" id="santeDetailsInfo">Page 1 / 1</span>
                <button type="button" class="bo-pager-arrow" id="santeDetailsNext" aria-label="Suivant">›</button>
            </div>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        if (!window.Chart) return;
        var labels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
        var poids = <?php echo json_encode($poids, JSON_UNESCAPED_UNICODE); ?>;
        var calories = <?php echo json_encode($calories, JSON_UNESCAPED_UNICODE); ?>;
        var sommeil = <?php echo json_encode($sommeil, JSON_UNESCAPED_UNICODE); ?>;
        var pas = <?php echo json_encode($pas, JSON_UNESCAPED_UNICODE); ?>;

        var gridColor = 'rgba(0,0,0,0.06)';
        function draw(id, label, data, border, fillRgba) {
            var c = document.getElementById(id);
            if (!c) return;
            new Chart(c, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: border,
                        backgroundColor: fillRgba,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: border,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { grid: { color: gridColor } },
                        y: { beginAtZero: true, grid: { color: gridColor } }
                    }
                }
            });
        }

        draw('chartPoids', 'Poids', poids, '#22c55e', 'rgba(134,239,172,0.35)');
        draw('chartCalories', 'Calories', calories, '#fb923c', 'rgba(253,186,116,0.28)');
        draw('chartSommeil', 'Sommeil (h)', sommeil, '#3b82f6', 'rgba(147,197,253,0.35)');
        draw('chartPas', 'Pas', pas, '#a855f7', 'rgba(216,180,254,0.35)');
    })();
    (function () {
        var table = document.getElementById('santeDetailsTable');
        if (!table) return;
        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
        if (!rows.length) return;
        var pager = document.getElementById('santeDetailsPager');
        var prev = document.getElementById('santeDetailsPrev');
        var next = document.getElementById('santeDetailsNext');
        var info = document.getElementById('santeDetailsInfo');
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