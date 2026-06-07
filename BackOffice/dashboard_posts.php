<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/includes/bo_layout_start.php';
require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/../Controllers/CommentaireController.php';
require_once __DIR__ . '/../config/Database.php';

$postController = new PostController();
$commentaireController = new CommentaireController();
$posts = $postController->getAll();

$pdo = Database::getConnection();

function hb_bo_challenge(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$challengeStats = [
    'total' => 0,
    'disponible' => 0,
    'selectionne' => 0,
    'termine' => 0,
    'participations' => 0,
    'likes' => 0,
];

$challenges = [];

try {
    $challengeStats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM challenge")->fetchColumn();

    $stStatuts = $pdo->query(
        "SELECT statut, COUNT(*) AS total
         FROM challenge
         GROUP BY statut"
    );

    foreach ($stStatuts->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $statut = (string) ($row['statut'] ?? '');
        if (array_key_exists($statut, $challengeStats)) {
            $challengeStats[$statut] = (int) ($row['total'] ?? 0);
        }
    }

    $challengeStats['participations'] = (int) $pdo->query("SELECT COUNT(*) FROM participation_challenge")->fetchColumn();
    $challengeStats['likes'] = (int) $pdo->query("SELECT COALESCE(SUM(nombreLikes), 0) FROM participation_challenge")->fetchColumn();

    $stChallenges = $pdo->query(
        "SELECT
            c.id,
            c.titre,
            c.description,
            c.image,
            c.statut,
            c.dateCreation,
            c.dateSelection,
            c.nutritionnisteId,
            COALESCE(u.prenom, '') AS prenom,
            COALESCE(u.nom, '') AS nom,
            COUNT(pc.id) AS total_participations,
            COALESCE(SUM(pc.nombreLikes), 0) AS total_likes
         FROM challenge c
         LEFT JOIN utilisateur u ON u.id_utilisateur = c.nutritionnisteId
         LEFT JOIN participation_challenge pc ON pc.challengeId = c.id
         GROUP BY
            c.id,
            c.titre,
            c.description,
            c.image,
            c.statut,
            c.dateCreation,
            c.dateSelection,
            c.nutritionnisteId,
            u.prenom,
            u.nom
         ORDER BY c.dateCreation DESC"
    );

    $challenges = $stChallenges->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $challenges = [];
}

$challengeChartLabels = ['Disponibles', 'Sélectionnés', 'Terminés'];
$challengeChartValues = [
    $challengeStats['disponible'],
    $challengeStats['selectionne'],
    $challengeStats['termine'],
];

$topChallengeLabels = [];
$topChallengeLikes = [];

$topChallenges = $challenges;
usort($topChallenges, static fn(array $a, array $b): int => ((int) $b['total_likes']) <=> ((int) $a['total_likes']));
$topChallenges = array_slice($topChallenges, 0, 6);

foreach ($topChallenges as $item) {
    $topChallengeLabels[] = mb_strimwidth((string) ($item['titre'] ?? 'Challenge'), 0, 22, '…', 'UTF-8');
    $topChallengeLikes[] = (int) ($item['total_likes'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Dashboard Posts - BackOffice HappyBite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-dashboard-posts .commande-wrap { padding-top: 8px; }

        .dashboard-section-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1f3a28;
            margin: 0;
        }

        .dashboard-section-subtitle {
            margin: 6px 0 0;
            color: #647067;
            font-size: 0.92rem;
            font-weight: 500;
        }

        .dashboard-kpi-card,
        .dashboard-chart-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border: 1px solid #eef0ef;
        }

        .dashboard-kpi-card {
            padding: 20px;
            height: 100%;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .dashboard-kpi-label {
            color: #7d8b88;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 0.86rem;
        }

        .dashboard-kpi-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: #1f3a28;
            line-height: 1.1;
        }

        .dashboard-chart-card {
            padding: 24px;
            height: 100%;
        }

        .dashboard-chart-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #204b39;
            margin-bottom: 18px;
        }

        .challenge-admin-table {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border: 1px solid #eef0ef;
            overflow: hidden;
        }

        .challenge-admin-table table { margin: 0; }

        .challenge-admin-table th {
            color: #647067;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #f8faf9;
            white-space: nowrap;
        }

        .challenge-title-cell {
            font-weight: 800;
            color: #1f3a28;
        }

        .challenge-desc-cell {
            color: #647067;
            font-size: 0.88rem;
            max-width: 360px;
        }

        .badge-statut {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 5px 11px;
            font-size: 0.76rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .badge-disponible { background: #dcfce7; color: #166534; }
        .badge-selectionne { background: #dbeafe; color: #1d4ed8; }
        .badge-termine { background: #f3f4f6; color: #6b7280; }

        .dashboard-section-divider {
            margin: 2.5rem 0 1.75rem;
            border: 0;
            border-top: 2px solid #e3ebe6;
        }

        .bo-table-pager {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin: 14px 16px 16px;
            flex-wrap: wrap;
        }

        .bo-pager-arrow {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 2px solid #2c7e34;
            background: #e8f5e9;
            color: #1f6b31;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .bo-pager-arrow[disabled] {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .bo-pager-info {
            color: #1f3a28;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body class="page-bo page-list-com-liv page-dashboard-posts">
<?php bo_layout_start('post'); ?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <a href="list_posts.php" class="btn-commande-outline btn-vue-toggle">Post</a>
                <a href="list_commentaires.php" class="btn-commande-outline btn-vue-toggle">Commentaire</a>
                <a href="dashboard_posts.php" class="btn-commande-primary is-active btn-vue-toggle">Dashboard</a>
            </div>
        </div>

        <div class="liste-com-liv-title-row mb-4">
            <div>
                <h1 class="liste-com-liv-title">Dashboard des posts</h1>
                <p class="liste-com-liv-subtitle">Statistiques et graphiques — posts &amp; challenges</p>
            </div>
        </div>

        <div class="mb-3">
            <h2 class="dashboard-section-title">Posts</h2>
            <p class="dashboard-section-subtitle">Interactions, likes et commentaires par publication</p>
        </div>

        <?php if (!empty($posts)) { ?>
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="border rounded-3 p-3" style="background:#fafbfc;">
                                <p class="text-muted fw-semibold mb-3" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.5px;">
                                    Total interactions
                                </p>
                                <div class="d-flex justify-content-center mb-3">
                                    <canvas id="statsChart" width="180" height="180" style="max-width:180px;max-height:180px;"></canvas>
                                </div>
                                <div id="chartLegend" class="d-flex flex-wrap gap-1 justify-content-center"></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="border rounded-3 p-3" style="background:#fafbfc;">
                                <p class="text-muted fw-semibold mb-3" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.5px;">
                                    Likes par post
                                </p>
                                <div class="d-flex justify-content-center mb-3">
                                    <canvas id="likesChart" width="180" height="180" style="max-width:180px;max-height:180px;"></canvas>
                                </div>
                                <div id="likesLegend" class="d-flex flex-wrap gap-1 justify-content-center"></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="border rounded-3 p-3" style="background:#fafbfc;">
                                <p class="text-muted fw-semibold mb-3" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.5px;">
                                    Commentaires par post
                                </p>
                                <div class="d-flex justify-content-center mb-3">
                                    <canvas id="commentsChart" width="180" height="180" style="max-width:180px;max-height:180px;"></canvas>
                                </div>
                                <div id="commentsLegend" class="d-flex flex-wrap gap-1 justify-content-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <table id="postsDataTable" style="display:none;">
                <tbody>
                <?php foreach ($posts as $post) { ?>
                    <?php $commentsCount = count($commentaireController->getByPostId($post['id'])); ?>
                    <tr
                        data-id="<?php echo (int) $post['id']; ?>"
                        data-likes="<?php echo (int) $post['nombreLikes']; ?>"
                        data-comments="<?php echo $commentsCount; ?>"
                    ></tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">Aucune donnée disponible</h6>
                </div>
            </div>
        <?php } ?>

        <hr class="dashboard-section-divider" id="dashboard-challenges">

        <div class="mb-4">
            <h2 class="dashboard-section-title">Challenges</h2>
            <p class="dashboard-section-subtitle">
                Vue globale des challenges, participations et likes de la communauté
            </p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-6 col-lg-2">
                <div class="dashboard-kpi-card">
                    <div class="dashboard-kpi-label">Challenges</div>
                    <div class="dashboard-kpi-value"><?= (int) $challengeStats['total'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-kpi-card">
                    <div class="dashboard-kpi-label">Disponibles</div>
                    <div class="dashboard-kpi-value"><?= (int) $challengeStats['disponible'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-kpi-card">
                    <div class="dashboard-kpi-label">Sélectionnés</div>
                    <div class="dashboard-kpi-value"><?= (int) $challengeStats['selectionne'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-kpi-card">
                    <div class="dashboard-kpi-label">Terminés</div>
                    <div class="dashboard-kpi-value"><?= (int) $challengeStats['termine'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-kpi-card">
                    <div class="dashboard-kpi-label">Participations</div>
                    <div class="dashboard-kpi-value"><?= (int) $challengeStats['participations'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="dashboard-kpi-card">
                    <div class="dashboard-kpi-label">Likes</div>
                    <div class="dashboard-kpi-value"><?= (int) $challengeStats['likes'] ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-5">
                <div class="dashboard-chart-card">
                    <div class="dashboard-chart-title">Répartition des statuts</div>
                    <div style="max-width: 320px; margin: 0 auto;">
                        <canvas id="chartStatuts"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="dashboard-chart-card">
                    <div class="dashboard-chart-title">Top challenges par likes</div>
                    <div style="height: 320px;">
                        <canvas id="chartTopLikes"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="challenge-admin-table">
            <div class="p-4 border-bottom">
                <h3 class="dashboard-chart-title mb-1">Liste des challenges</h3>
                <p class="text-muted mb-0">Tous les challenges créés dans la communauté.</p>
            </div>

            <?php if (empty($challenges)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted mb-0">Aucun challenge trouvé</h6>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Challenge</th>
                                <th>Nutritionniste</th>
                                <th>Statut</th>
                                <th>Participations</th>
                                <th>Likes</th>
                                <th>Créé le</th>
                            </tr>
                        </thead>
                        <tbody id="challengesTableBody">
                            <?php foreach ($challenges as $ch): ?>
                                <?php
                                $statut = (string) ($ch['statut'] ?? 'disponible');
                                $nutriName = trim((string) ($ch['prenom'] ?? '') . ' ' . (string) ($ch['nom'] ?? ''));
                                if ($nutriName === '') {
                                    $nutriName = '—';
                                }
                                $dateCreation = (string) ($ch['dateCreation'] ?? '');
                                $dateLabel = $dateCreation !== ''
                                    ? date('d/m/Y', strtotime($dateCreation) ?: time())
                                    : '—';
                                ?>
                                <tr>
                                    <td><?= (int) ($ch['id'] ?? 0) ?></td>
                                    <td>
                                        <div class="challenge-title-cell">
                                            <?= hb_bo_challenge((string) ($ch['titre'] ?? '')) ?>
                                        </div>
                                        <?php if (trim((string) ($ch['description'] ?? '')) !== ''): ?>
                                            <div class="challenge-desc-cell">
                                                <?= hb_bo_challenge(mb_strimwidth((string) ($ch['description'] ?? ''), 0, 90, '…', 'UTF-8')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= hb_bo_challenge($nutriName) ?></td>
                                    <td>
                                        <span class="badge-statut badge-<?= hb_bo_challenge($statut) ?>">
                                            <?= hb_bo_challenge(ucfirst($statut)) ?>
                                        </span>
                                    </td>
                                    <td><?= (int) ($ch['total_participations'] ?? 0) ?></td>
                                    <td><?= (int) ($ch['total_likes'] ?? 0) ?></td>
                                    <td><?= hb_bo_challenge($dateLabel) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="challengesTablePagination" class="bo-table-pager">
                    <button type="button" class="bo-pager-arrow" id="challengesPagerPrev" aria-label="Précédent">‹</button>
                    <span class="bo-pager-info" id="challengesPageInfo">Page 1 / 1</span>
                    <button type="button" class="bo-pager-arrow" id="challengesPagerNext" aria-label="Suivant">›</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php bo_layout_end(); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function buildPostCharts() {
    const table = document.getElementById('postsDataTable');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tr'));
    if (!rows.length) return;

    const palette = ['#2f6f57','#4a9070','#6dbf9e','#f0a500','#e05c5c','#5b8dee','#a78bfa','#94a3b8'];
    const likePalette = ['#f0a500','#f5bc3a','#f7cc6a','#e8960a','#c97d00','#ffd966','#ffe8a0','#fbecc8'];
    const commentPalette = ['#0ea5e9','#38bdf8','#0284c7','#7dd3fc','#0369a1','#60a5fa','#3b82f6','#93c5fd'];
    const TOP = 6;

    const allData = rows.map((r) => {
        const id = r.dataset.id || '?';
        const likes = parseInt(r.dataset.likes, 10) || 0;
        const comments = parseInt(r.dataset.comments, 10) || 0;
        return {
            label: 'Post #' + id,
            likes: likes,
            comments: comments,
            combined: likes + comments
        };
    });

    function prepData(key) {
        const sorted = [...allData].sort((a, b) => b[key] - a[key]);
        const nonZero = sorted.filter((d) => d[key] > 0);
        const source = nonZero.length ? nonZero : sorted;

        if (source.length > TOP) {
            const top = source.slice(0, TOP);
            const rest = source.slice(TOP).reduce((sum, d) => sum + d[key], 0);
            return {
                labels: [...top.map((d) => d.label), 'Autres'],
                values: [...top.map((d) => d[key]), rest]
            };
        }
        return {
            labels: source.map((d) => d.label),
            values: source.map((d) => d[key])
        };
    }

    function makeChart(canvasId, labels, values, colors) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const hasData = values.some((v) => v > 0);
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: hasData ? labels : ['Aucune donnee'],
                datasets: [{
                    data: hasData ? values : [1],
                    backgroundColor: hasData ? colors.slice(0, labels.length) : ['#e9ecef'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: hasData ? 8 : 0
                }]
            },
            options: {
                responsive: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: hasData,
                        callbacks: {
                            label: function (ctx) {
                                return ' ' + ctx.label + ': ' + ctx.parsed;
                            }
                        }
                    }
                }
            }
        });
    }

    function makeLegend(legendId, labels, values, colors, hasData) {
        const el = document.getElementById(legendId);
        if (!el) return;
        if (!hasData) {
            el.innerHTML = '<span class="text-muted small">Aucune donnee</span>';
            return;
        }
        labels.forEach((label, i) => {
            const pill = document.createElement('div');
            pill.style.cssText = 'display:inline-flex;align-items:center;gap:5px;background:#fff;border-radius:20px;padding:3px 8px 3px 5px;font-size:0.72rem;font-weight:600;border:1px solid #e9ecef;white-space:nowrap;margin:2px;';
            pill.innerHTML = '<span style="width:8px;height:8px;border-radius:50%;background:' + colors[i] + ';flex-shrink:0;display:inline-block;"></span>' + label + '<span style="background:' + colors[i] + ';color:#fff;border-radius:10px;padding:1px 6px;font-size:0.68rem;margin-left:3px;">' + values[i] + '</span>';
            el.appendChild(pill);
        });
    }

    const c = prepData('combined');
    makeChart('statsChart', c.labels, c.values, palette);
    makeLegend('chartLegend', c.labels, c.values, palette, c.values.some((v) => v > 0));

    const l = prepData('likes');
    makeChart('likesChart', l.labels, l.values, likePalette);
    makeLegend('likesLegend', l.labels, l.values, likePalette, l.values.some((v) => v > 0));

    const cm = prepData('comments');
    makeChart('commentsChart', cm.labels, cm.values, commentPalette);
    makeLegend('commentsLegend', cm.labels, cm.values, commentPalette, cm.values.some((v) => v > 0));
})();

(function buildChallengeCharts() {
    const statutLabels = <?= json_encode($challengeChartLabels, JSON_UNESCAPED_UNICODE) ?>;
    const statutValues = <?= json_encode($challengeChartValues, JSON_UNESCAPED_UNICODE) ?>;
    const topLabels = <?= json_encode($topChallengeLabels, JSON_UNESCAPED_UNICODE) ?>;
    const topLikes = <?= json_encode($topChallengeLikes, JSON_UNESCAPED_UNICODE) ?>;
    const palette = ['#2C7E34', '#3b82f6', '#9ca3af'];

    const statutCanvas = document.getElementById('chartStatuts');
    if (statutCanvas) {
        const hasData = statutValues.some(function (v) { return v > 0; });
        new Chart(statutCanvas, {
            type: 'doughnut',
            data: {
                labels: hasData ? statutLabels : ['Aucune donnée'],
                datasets: [{
                    data: hasData ? statutValues : [1],
                    backgroundColor: hasData ? palette : ['#e9ecef'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '62%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    const topCanvas = document.getElementById('chartTopLikes');
    if (topCanvas) {
        const hasTop = topLikes.some(function (v) { return v > 0; });
        new Chart(topCanvas, {
            type: 'bar',
            data: {
                labels: hasTop ? topLabels : ['Aucune donnée'],
                datasets: [{
                    label: 'Likes',
                    data: hasTop ? topLikes : [0],
                    backgroundColor: '#2C7E34',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
})();

(function initChallengesPager() {
    const tbody = document.getElementById('challengesTableBody');
    if (!tbody) return;

    const ROWS_PER_PAGE = 8;
    let currentPage = 1;
    const paginatedRows = Array.from(tbody.querySelectorAll('tr'));

    function renderPaginationControls() {
        const total = paginatedRows.length;
        const totalPages = Math.ceil(total / ROWS_PER_PAGE) || 1;
        const bar = document.getElementById('challengesTablePagination');
        const info = document.getElementById('challengesPageInfo');
        const prev = document.getElementById('challengesPagerPrev');
        const next = document.getElementById('challengesPagerNext');
        if (!bar || !info || !prev || !next) return;

        bar.style.display = total === 0 ? 'none' : 'flex';
        info.textContent = 'Page ' + currentPage + ' / ' + totalPages;
        prev.disabled = currentPage <= 1;
        next.disabled = currentPage >= totalPages;
    }

    function renderPage(page) {
        currentPage = page;
        const start = (page - 1) * ROWS_PER_PAGE;
        const end = start + ROWS_PER_PAGE;
        paginatedRows.forEach(function (row, i) {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });
        renderPaginationControls();
    }

    const prev = document.getElementById('challengesPagerPrev');
    if (prev) {
        prev.addEventListener('click', function () {
            if (currentPage > 1) {
                renderPage(currentPage - 1);
            }
        });
    }

    const next = document.getElementById('challengesPagerNext');
    if (next) {
        next.addEventListener('click', function () {
            const totalPages = Math.ceil(paginatedRows.length / ROWS_PER_PAGE) || 1;
            if (currentPage < totalPages) {
                renderPage(currentPage + 1);
            }
        });
    }

    renderPage(1);
})();
</script>
</body>
</html>
