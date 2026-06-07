<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/includes/bo_layout_start.php';
require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/../Controllers/CommentaireController.php';

$postController = new PostController();
$commentaireController = new CommentaireController();

if (isset($_GET['delete_post'])) {
    $id = (int) $_GET['delete_post'];
    if ($id > 0) {
        $postController->delete($id);
    }
    header('Location: list_posts.php');
    exit;
}

$posts = $postController->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Liste des Posts - BackOffice HappyBite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-list-posts .commande-wrap { padding-top: 8px; }
        .page-list-posts .bo-form-row--posts {
            display: grid;
            grid-template-columns: minmax(200px, 1.5fr) 1fr 1fr auto;
            gap: 14px;
            align-items: end;
        }
        @media (max-width: 900px) {
            .page-list-posts .bo-form-row--posts { grid-template-columns: 1fr; }
        }
        .page-list-posts .bo-result-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .table-thumb {
            max-width: 56px;
            max-height: 56px;
            object-fit: cover;
            border-radius: 8px;
        }
        .bo-table-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            text-decoration: none;
            line-height: 1.2;
            border: 1px solid transparent;
        }
        .bo-table-btn--delete {
            background: #ef4444;
            border-color: #dc2626;
            color: #fff;
        }
        .bo-table-btn--view {
            background: #2563eb;
            border-color: #1d4ed8;
            color: #fff;
        }
        .bo-table-btn:hover { filter: brightness(0.96); }
        .bo-table-pager {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
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
        }
    </style>
</head>
<body class="page-bo page-list-com-liv page-list-posts">
<?php bo_layout_start('post'); ?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1100px; width: 100%;">
        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <a href="list_posts.php" class="btn-commande-primary is-active btn-vue-toggle">Post</a>
                <a href="list_commentaires.php" class="btn-commande-outline btn-vue-toggle">Commentaire</a>
                <a href="dashboard_posts.php" class="btn-commande-outline btn-vue-toggle">Dashboard</a>
            </div>
        </div>

        <div class="liste-com-liv-title-row">
            <div>
                <h1 class="liste-com-liv-title">Liste des posts</h1>
                <p class="liste-com-liv-subtitle">Gérez les publications de la communauté</p>
            </div>
            <div class="liste-com-liv-title-actions">
                <a href="export_posts_pdf.php" target="_blank" rel="noopener" class="btn-commande-primary">Exporter PDF</a>
            </div>
        </div>

        <section class="bo-panel" aria-label="Filtres">
            <div class="bo-form-row bo-form-row--posts">
                <div class="bo-field">
                    <label for="searchInput">Recherche en temps réel</label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="text" id="searchInput" placeholder="Filtrer par contenu du post…" style="flex:1; min-width:0;">
                        <button type="button" class="btn-commande-outline btn-vue-toggle" id="clearSearch" style="display:none; flex-shrink:0;">Effacer</button>
                    </div>
                </div>
                <div class="bo-field">
                    <label for="sortBy">Trier par</label>
                    <select id="sortBy">
                        <option value="date">Date</option>
                        <option value="likes">Likes</option>
                        <option value="comments">Commentaires</option>
                    </select>
                </div>
                <div class="bo-field">
                    <label for="sortOrder">Ordre</label>
                    <select id="sortOrder">
                        <option value="desc">Décroissant</option>
                        <option value="asc">Croissant</option>
                    </select>
                </div>
                <div class="bo-field">
                    <label>Résultats</label>
                    <div class="bo-result-count">
                        <span class="bo-pill bo-pill--muted" id="resultCount"><?php echo count($posts); ?></span>
                        <span style="color:#5a6560; font-size:0.9rem; font-weight:500;">résultats</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="bo-table-wrap" aria-label="Tableau des posts">
                <?php if (empty($posts)) { ?>
                    <div class="bo-empty">Aucun post trouvé</div>
                <?php } else { ?>
                    <div class="bo-table-scroll">
                        <table class="bo-table" id="postsTable">
                            <thead>
                                <tr>
                                    <th>Contenu</th>
                                    <th class="bo-td-center">Image</th>
                                    <th class="bo-td-center sortable" data-col="likes" style="cursor:pointer;">Likes</th>
                                    <th class="bo-td-center sortable" data-col="comments" style="cursor:pointer;">Commentaires</th>
                                    <th class="bo-td-center sortable" data-col="date" style="cursor:pointer;">Date</th>
                                    <th class="bo-td-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="postsTableBody">
                                <?php foreach ($posts as $post) { ?>
                                    <?php $commentsCount = count($commentaireController->getByPostId($post['id'])); ?>
                                    <tr
                                        data-content="<?php echo strtolower(htmlspecialchars($post['contenu'])); ?>"
                                        data-likes="<?php echo (int) $post['nombreLikes']; ?>"
                                        data-comments="<?php echo $commentsCount; ?>"
                                        data-date="<?php echo strtotime($post['datePublication']); ?>"
                                    >
                                        <td class="bo-td-left">
                                            <div style="font-weight:500; color:#5a6560; font-size:12px; margin-bottom:4px;">Post #<?php echo $post['id']; ?></div>
                                            <div class="text-truncate" style="max-width: 320px;" title="<?php echo htmlspecialchars($post['contenu']); ?>">
                                                <?php echo htmlspecialchars(substr($post['contenu'], 0, 100)) . (strlen($post['contenu']) > 100 ? '...' : ''); ?>
                                            </div>
                                        </td>
                                        <td class="bo-td-center">
                                            <?php if (!empty($post['image'])) { ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="" class="table-thumb">
                                            <?php } else { ?>
                                                <span class="bo-pill bo-pill--muted">—</span>
                                            <?php } ?>
                                        </td>
                                        <td class="bo-td-center">
                                            <span class="bo-pill bo-pill--muted"><?php echo (int) $post['nombreLikes']; ?></span>
                                        </td>
                                        <td class="bo-td-center">
                                            <span class="bo-pill bo-pill--muted"><?php echo $commentsCount; ?></span>
                                        </td>
                                        <td class="bo-td-center">
                                            <span style="color:#2f3d36; font-size:13px;"><?php echo date('d/m/Y', strtotime($post['datePublication'])); ?></span><br>
                                            <span style="color:#5a6560; font-size:12px;"><?php echo date('H:i', strtotime($post['datePublication'])); ?></span>
                                        </td>
                                        <td class="bo-td-center">
                                            <a href="#" role="button" class="bo-img-link me-1" title="Voir le détail" aria-label="Voir le détail" onclick='viewPostDetails(<?php echo htmlspecialchars(json_encode($post['contenu'])); ?>, "<?php echo date('d/m/Y H:i', strtotime($post['datePublication'])); ?>"); return false;'><img src="images/details.png" width="22" height="22" alt=""></a>
                                            <a href="?delete_post=<?php echo $post['id']; ?>" class="bo-img-link" title="Supprimer" aria-label="Supprimer" onclick="return confirm('Supprimer ce post ?');"><img src="images/delete.png" width="22" height="22" alt=""></a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="noResults" class="bo-empty" style="display:none;">Aucun post ne correspond à votre recherche</div>
                    <div id="tablePagination" class="bo-table-pager">
                        <button type="button" class="bo-pager-arrow" id="pagerPrev" aria-label="Précédent">‹</button>
                        <span class="bo-pager-info" id="pageInfo">Page 1 / 1</span>
                        <button type="button" class="bo-pager-arrow" id="pagerNext" aria-label="Suivant">›</button>
                    </div>
                <?php } ?>
        </section>
    </div>
</main>

<div class="modal fade" id="postDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="postDetailsContent"></div>
        </div>
    </div>
</div>

<?php bo_layout_end(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ROWS_PER_PAGE = 5;
let currentPage = 1;
let paginatedRows = [];
let currentSort = 'date';
let currentOrder = 'desc';

const searchInput = document.getElementById('searchInput');
const sortBySelect = document.getElementById('sortBy');
const sortOrderSel = document.getElementById('sortOrder');
const clearBtn = document.getElementById('clearSearch');

function renderPage(page) {
    currentPage = page;
    const start = (page - 1) * ROWS_PER_PAGE;
    const end = start + ROWS_PER_PAGE;
    paginatedRows.forEach((row, i) => {
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });
    renderPaginationControls();
}

function renderPaginationControls() {
    const total = paginatedRows.length;
    const totalPages = Math.ceil(total / ROWS_PER_PAGE) || 1;
    const bar = document.getElementById('tablePagination');
    const info = document.getElementById('pageInfo');
    const prev = document.getElementById('pagerPrev');
    const next = document.getElementById('pagerNext');
    if (!bar || !info || !prev || !next) return;

    bar.style.display = total === 0 ? 'none' : 'flex';
    info.textContent = `Page ${currentPage} / ${totalPages}`;
    prev.disabled = currentPage <= 1;
    next.disabled = currentPage >= totalPages;
}

function filterAndSort() {
    const query = (searchInput ? searchInput.value.toLowerCase().trim() : '');
    const tbody = document.getElementById('postsTableBody');
    if (!tbody) return;

    clearBtn.style.display = query ? 'inline-block' : 'none';
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const visible = rows.filter((row) => {
        const content = row.dataset.content || '';
        const match = !query || content.includes(query);
        row.style.display = 'none';
        return match;
    });

    visible.sort((a, b) => {
        let va;
        let vb;
        if (currentSort === 'likes') {
            va = parseInt(a.dataset.likes, 10) || 0;
            vb = parseInt(b.dataset.likes, 10) || 0;
        } else if (currentSort === 'comments') {
            va = parseInt(a.dataset.comments, 10) || 0;
            vb = parseInt(b.dataset.comments, 10) || 0;
        } else {
            va = parseInt(a.dataset.date, 10) || 0;
            vb = parseInt(b.dataset.date, 10) || 0;
        }
        return currentOrder === 'asc' ? va - vb : vb - va;
    });

    visible.forEach((row) => tbody.appendChild(row));
    const countEl = document.getElementById('resultCount');
    if (countEl) countEl.textContent = visible.length;

    const noResults = document.getElementById('noResults');
    if (noResults) noResults.style.display = visible.length === 0 ? 'block' : 'none';

    paginatedRows = visible;
    renderPage(1);
}

function viewPostDetails(contenu, date) {
    const content = `
        <div class="mb-3"><strong>Contenu :</strong><p class="mt-2">${String(contenu).replace(/\n/g, '<br>')}</p></div>
        <div class="mb-3"><strong>Date :</strong><p class="mt-1 text-muted">${date}</p></div>
    `;
    const target = document.getElementById('postDetailsContent');
    if (target) target.innerHTML = content;
    const modal = new bootstrap.Modal(document.getElementById('postDetailsModal'));
    modal.show();
}

if (searchInput) {
    searchInput.addEventListener('input', filterAndSort);
}
if (clearBtn) {
    clearBtn.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        filterAndSort();
    });
}
if (sortBySelect) {
    sortBySelect.addEventListener('change', () => {
        currentSort = sortBySelect.value;
        filterAndSort();
    });
}
if (sortOrderSel) {
    sortOrderSel.addEventListener('change', () => {
        currentOrder = sortOrderSel.value;
        filterAndSort();
    });
}
document.querySelectorAll('.sortable').forEach((th) => {
    th.addEventListener('click', () => {
        const col = th.dataset.col;
        if (currentSort === col) {
            currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort = col;
            currentOrder = 'desc';
        }
        if (sortBySelect) sortBySelect.value = currentSort;
        if (sortOrderSel) sortOrderSel.value = currentOrder;
        filterAndSort();
    });
});

const pagerPrev = document.getElementById('pagerPrev');
if (pagerPrev) {
    pagerPrev.addEventListener('click', () => {
        if (currentPage > 1) {
            renderPage(currentPage - 1);
        }
    });
}
const pagerNext = document.getElementById('pagerNext');
if (pagerNext) {
    pagerNext.addEventListener('click', () => {
        const totalPages = Math.ceil(paginatedRows.length / ROWS_PER_PAGE) || 1;
        if (currentPage < totalPages) {
            renderPage(currentPage + 1);
        }
    });
}

filterAndSort();
</script>
</body>
</html>
