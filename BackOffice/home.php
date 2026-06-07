<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/../Controllers/CommandeController.php';
require_once __DIR__ . '/../Controllers/LivraisonController.php';
require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Controllers/SuiviJournalierController.php';
require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../Controllers/UtilisateurPhotoSql.php';
require_once __DIR__ . '/../config/Database.php';

use Model\User;

$prenom = trim((string) ($_SESSION['bo_user_prenom'] ?? ''));
$nom = trim((string) ($_SESSION['bo_user_nom'] ?? ''));
$displayName = $prenom !== '' || $nom !== '' ? trim($prenom . ' ' . $nom) : 'Admin';
$boUid = (int) ($_SESSION['bo_user_id'] ?? 0);

$now = new DateTimeImmutable('now');
$todayYmd = $now->format('Y-m-d');
$yesterdayYmd = $now->modify('-1 day')->format('Y-m-d');

$commandes = [];
$livraisons = [];
$recentCommandes = [];
$cmdCtrl = null;
$userNamesById = [];
$kpis = ['total' => 0, 'new_today' => 0, 'active' => 0];
$userRoleStats = ['total' => 0, 'admins' => 0, 'clients' => 0, 'nutritionnistes' => 0];
$postsTotal = 0;
$postsPreview = [];
$produitsCount = 0;
$recettesCount = 0;
$profilStats = ['avec' => 0, 'sans' => 0];
$newestUser = null;
$topLikedPost = null;

try {
    $pdo = Database::getConnection();
    $cmdCtrl = new CommandeController();
    $commandes = $cmdCtrl->listCommandes();
    $recentCommandes = array_slice($commandes, 0, 5);
    $uids = [];
    foreach ($recentCommandes as $rc) {
        $u = (int) ($rc['id_utilisateur'] ?? 0);
        if ($u > 0) {
            $uids[] = $u;
        }
    }
    $uids = array_values(array_unique($uids));
    if ($uids !== []) {
        $pk = 'id';
        $stPk = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
        );
        $stPk->execute(['t' => 'utilisateur', 'c' => 'id']);
        if ((int) $stPk->fetchColumn() === 0) {
            $pk = 'id_utilisateur';
        }
        $in = implode(',', array_fill(0, count($uids), '?'));
        $stU = $pdo->prepare("SELECT `{$pk}` AS uid, prenom, nom FROM utilisateur WHERE `{$pk}` IN ({$in})");
        $stU->execute($uids);
        foreach ($stU->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $userNamesById[(int) ($row['uid'] ?? 0)] = trim((string) ($row['prenom'] ?? '') . ' ' . (string) ($row['nom'] ?? ''));
        }
    }

    $livCtrl = new LivraisonController();
    $livraisons = $livCtrl->listLivraisons();
} catch (Throwable) {
    // keep defaults
}

try {
    $userModel = new User();
    $kpis = $userModel->getKPIs();
    $userRoleStats = $userModel->getStats();
} catch (Throwable) {
}

try {
    $postCtrl = new PostController();
    $postsTotal = $postCtrl->getTotalCount();
    $postsPreview = $postCtrl->getAll(3, 0);
} catch (Throwable) {
}

try {
    $prodCtrl = new ProduitController();
    $produitsCount = count($prodCtrl->listProduits());
} catch (Throwable) {
}

try {
    $pdo = Database::getConnection();
    $recettesCount = (int) $pdo->query('SELECT COUNT(*) FROM recette')->fetchColumn();
} catch (Throwable) {
}

try {
    $sj = new SuiviJournalierController();
    $rawPs = $sj->getStatsProfilsVsNon();
    if (is_array($rawPs)) {
        $profilStats = [
            'avec' => (int) ($rawPs['avec'] ?? 0),
            'sans' => (int) ($rawPs['sans'] ?? 0),
        ];
    }
} catch (Throwable) {
}

try {
    $pdo = Database::getConnection();
    $stPk = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stPk->execute(['t' => 'utilisateur', 'c' => 'id']);
    $pkCol = (int) $stPk->fetchColumn() > 0 ? 'id' : 'id_utilisateur';
    $stNew = $pdo->query('SELECT prenom, nom, email FROM utilisateur ORDER BY `' . $pkCol . '` DESC LIMIT 1');
    $newestUser = $stNew ? $stNew->fetch(PDO::FETCH_ASSOC) : null;
    $stTop = $pdo->query('SELECT id, contenu, nombreLikes, datePublication FROM Post ORDER BY nombreLikes DESC, datePublication DESC LIMIT 1');
    $topLikedPost = $stTop ? $stTop->fetch(PDO::FETCH_ASSOC) : null;
} catch (Throwable) {
}

$commandeDateKey = static function (array $c): string {
    $raw = (string) ($c['date'] ?? $c['date_commande'] ?? '');
    if ($raw === '') {
        return '';
    }
    return substr($raw, 0, 10);
};

$commandeTotal = count($commandes);
$commandeToday = 0;
$commandeYesterday = 0;
$commandeRevenueToday = 0.0;
$commandeRevenueYesterday = 0.0;
$commandePending = 0;
$commandePaid = 0;
$ordersByDay = [];
for ($i = 6; $i >= 0; $i--) {
    $d = (new DateTimeImmutable('today'))->modify('-' . $i . ' days');
    $ordersByDay[$d->format('Y-m-d')] = ['label' => $d->format('d/m'), 'count' => 0, 'revenue' => 0.0];
}

foreach ($commandes as $c) {
    $ymd = $commandeDateKey($c);
    $total = (float) ($c['total'] ?? 0);
    $mode = trim((string) ($c['modePaiement'] ?? ''));
    if ($mode === '') {
        $commandePending++;
    } else {
        $commandePaid++;
    }
    if ($ymd === $todayYmd) {
        $commandeToday++;
        $commandeRevenueToday += $total;
    }
    if ($ymd === $yesterdayYmd) {
        $commandeYesterday++;
        $commandeRevenueYesterday += $total;
    }
    if ($ymd !== '' && isset($ordersByDay[$ymd])) {
        $ordersByDay[$ymd]['count']++;
        $ordersByDay[$ymd]['revenue'] += $total;
    }
}

$livraisonsToday = 0;
$livraisonsDelayed = 0;
$todayDt = new DateTimeImmutable('today');
foreach ($livraisons as $liv) {
    $dStr = LivraisonController::extraireDatePourAffichage($liv);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dStr)) {
        $dL = DateTimeImmutable::createFromFormat('Y-m-d', $dStr);
        if ($dL && $dL->format('Y-m-d') === $todayYmd) {
            $livraisonsToday++;
        }
        if ($dL && $dL < $todayDt) {
            $st = strtolower((string) ($liv['statut'] ?? ''));
            if ($st !== '' && !str_contains($st, 'livr') && !str_contains($st, 'annul')) {
                $livraisonsDelayed++;
            }
        }
    }
}

$pctVsYesterday = null;
if ($commandeYesterday > 0) {
    $pctVsYesterday = (int) round(100 * ($commandeToday - $commandeYesterday) / $commandeYesterday);
} elseif ($commandeToday > 0) {
    $pctVsYesterday = 100;
}

$alerts = [];
if ($commandePending > 0) {
    $alerts[] = ['icon' => '⚠️', 'text' => $commandePending . ' commande(s) en attente de paiement', 'class' => 'bo-home-alert--warn'];
}
if ($livraisonsDelayed > 0) {
    $alerts[] = ['icon' => '🚚', 'text' => $livraisonsDelayed . ' livraison(s) à suivre (date dépassée, non livrée)', 'class' => 'bo-home-alert--warn'];
}
if (($kpis['new_today'] ?? 0) > 0) {
    $alerts[] = ['icon' => '👋', 'text' => (int) $kpis['new_today'] . ' nouvel(le)s utilisateur(s) aujourd’hui', 'class' => 'bo-home-alert--info'];
}
if ($alerts === []) {
    $alerts[] = ['icon' => '✅', 'text' => 'Aucune alerte critique — tout semble en ordre.', 'class' => 'bo-home-alert--ok'];
}

$chartLineLabels = [];
$chartLineCounts = [];
$chartLineRevenue = [];
foreach ($ordersByDay as $bucket) {
    $chartLineLabels[] = $bucket['label'];
    $chartLineCounts[] = $bucket['count'];
    $chartLineRevenue[] = round($bucket['revenue'], 2);
}

$othersUsers = max(0, (int) ($userRoleStats['total'] ?? 0)
    - (int) ($userRoleStats['admins'] ?? 0)
    - (int) ($userRoleStats['clients'] ?? 0)
    - (int) ($userRoleStats['nutritionnistes'] ?? 0));
$donutLabels = ['Clients', 'Nutritionnistes', 'Admins', 'Autres'];
$donutData = [
    (int) ($userRoleStats['clients'] ?? 0),
    (int) ($userRoleStats['nutritionnistes'] ?? 0),
    (int) ($userRoleStats['admins'] ?? 0),
    $othersUsers,
];

$profileImgSrc = '../FrontOffice/images/profile.png';
try {
    $rel = utilisateur_fetch_profile_relative_path(Database::getConnection(), $boUid);
    $src = utilisateur_nav_profile_img_src($rel);
    if ($src !== null) {
        $profileImgSrc = $src;
    }
} catch (Throwable) {
}

/** @return array{class: string, label: string} */
$commandeBadge = static function (array $c): array {
    $mode = trim((string) ($c['modePaiement'] ?? ''));
    if ($mode === '') {
        return ['class' => 'bo-home-badge bo-home-badge--pending', 'label' => 'En attente'];
    }
    $m = strtolower($mode);
    if (str_contains($m, 'annul')) {
        return ['class' => 'bo-home-badge bo-home-badge--danger', 'label' => 'Annulée'];
    }

    return ['class' => 'bo-home-badge bo-home-badge--ok', 'label' => 'Payée'];
};

if ($cmdCtrl === null) {
    $cmdCtrl = new CommandeController();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — Tableau de bord</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Aligné sur list-com-liv / commande : fond clair, pastels vert / orange / jaune / rouge */
        .page-bo-home .commande-wrap { padding-top: 8px; }
        .page-bo-home .liste-com-liv-stack { max-width: 1180px; margin: 0 auto; }
        .page-bo-home .bo-home-header-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .page-bo-home .bo-home-time {
            text-align: right;
            font-size: 0.88rem;
            color: #5a6560;
        }
        .page-bo-home .bo-home-time strong { display: block; color: #1f3a28; font-size: 1rem; font-weight: 700; }
        .page-bo-home .bo-home-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #d8e8dc;
            background: #f0fdf4;
        }
        .page-bo-home .bo-home-summary {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 16px;
            font-weight: 600;
            color: #422006;
        }
        .page-bo-home .bo-home-summary strong { color: #14532d; }
        .page-bo-home .bo-home-summary span { color: #c2410c; font-weight: 700; }
        .page-bo-home .bo-charts-grid {
            margin-bottom: 16px;
        }

        @media (max-width: 960px) {
            .page-bo-home .bo-charts-grid {
                grid-template-columns: 1fr;
            }
        }
        .page-bo-home .bo-home-alerts { margin-bottom: 18px; }
        .page-bo-home .bo-home-alerts h2 { margin: 0 0 10px; font-size: 1.05rem; font-weight: 700; color: #1f3a28; }
        .page-bo-home .bo-home-alert-list { display: flex; flex-direction: column; gap: 8px; }
        .page-bo-home .bo-home-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .page-bo-home .bo-home-alert--warn { background: #fffbeb; border: 1px solid #fcd34d; color: #78350f; }
        .page-bo-home .bo-home-alert--info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; }
        .page-bo-home .bo-home-alert--ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .page-bo-home .bo-home-table-wrap { margin-bottom: 18px; }
        .page-bo-home .bo-home-table-wrap h2 { margin: 0 0 10px; font-size: 1.05rem; font-weight: 700; color: #1f3a28; }
        .page-bo-home .bo-home-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .page-bo-home .bo-home-badge--ok { background: #dcfce7; color: #166534; }
        .page-bo-home .bo-home-badge--pending { background: #ffedd5; color: #9a3412; }
        .page-bo-home .bo-home-badge--danger { background: #fee2e2; color: #b91c1c; }
        .page-bo-home .bo-home-community { margin-bottom: 18px; }
        .page-bo-home .bo-home-community h2 { margin: 0 0 10px; font-size: 1.05rem; font-weight: 700; color: #1f3a28; }
        .page-bo-home .bo-home-community-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .page-bo-home .bo-home-mini {
            background: #fff;
            border: 1px solid var(--bo-border, #d8e8dc);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.88rem;
            color: #2f3d36;
        }
        .page-bo-home .bo-home-mini strong { display: block; color: #1f3a28; margin-bottom: 6px; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .page-bo-home .bo-home-cal h2 { margin: 0 0 10px; font-size: 1.05rem; font-weight: 700; color: #1f3a28; }
        .page-bo-home .bo-home-cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            text-align: center;
            font-size: 0.75rem;
        }
        .page-bo-home .bo-home-cal-cell {
            padding: 8px 4px;
            border-radius: 8px;
            background: #f4faf5;
            border: 1px solid #e0e8e3;
        }
        .page-bo-home .bo-home-cal-cell--today {
            background: #fef3c7;
            border-color: #f59e0b;
            font-weight: 700;
        }
    </style>
</head>
<body class="page-bo page-list-com-liv page-bo-home">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack">
        <div class="liste-com-liv-title-row" aria-label="En-tête tableau de bord">
            <div>
                <h1 class="liste-com-liv-title">Bienvenue 👋</h1>
                <p class="liste-com-liv-subtitle">Voici ce qui se passe sur <strong>HappyBite</strong> aujourd’hui.</p>
            </div>
            <div class="liste-com-liv-title-actions bo-home-header-meta">
                <div class="bo-home-time">
                    <span><?php echo htmlspecialchars($now->format('d/m/Y'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <strong><?php echo htmlspecialchars($now->format('H:i'), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <img class="bo-home-avatar" src="<?php echo htmlspecialchars($profileImgSrc, ENT_QUOTES, 'UTF-8'); ?>" width="44" height="44" alt="">
            </div>
        </div>

        <div class="bo-stats-grid bo-home-stats-row" aria-label="Statistiques globales">
            <article class="bo-stat-card bo-home-stat bo-home-stat--c1">
                <div class="bo-home-stat-emoji" aria-hidden="true">🛒</div>
                <h3>Commandes (total)</h3>
                <p><?php echo (int) $commandeTotal; ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c2">
                <div class="bo-home-stat-emoji" aria-hidden="true">📅</div>
                <h3>Commandes aujourd’hui</h3>
                <p><?php echo (int) $commandeToday; ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c6">
                <div class="bo-home-stat-emoji" aria-hidden="true">🚚</div>
                <h3>Livraisons prévues ce jour</h3>
                <p><?php echo (int) $livraisonsToday; ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c4">
                <div class="bo-home-stat-emoji" aria-hidden="true">👥</div>
                <h3>Utilisateurs actifs</h3>
                <p><?php echo (int) ($kpis['active'] ?? 0); ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c5">
                <div class="bo-home-stat-emoji" aria-hidden="true">📸</div>
                <h3>Posts communauté</h3>
                <p><?php echo (int) $postsTotal; ?></p>
            </article>
            <article class="bo-stat-card bo-home-stat bo-home-stat--c3">
                <div class="bo-home-stat-emoji" aria-hidden="true">🥗</div>
                <h3>Produits catalogue</h3>
                <p><?php echo (int) $produitsCount; ?></p>
            </article>
        </div>

        <section class="bo-home-summary" aria-label="Synthèse du jour">
            <?php if ($commandeToday > 0) { ?>
                <strong>Aujourd’hui :</strong>
                <?php echo (int) $commandeToday; ?> commande(s) enregistrée(s),
                chiffre du jour ≈ <span><?php echo htmlspecialchars(number_format($commandeRevenueToday, 2, ',', ' '), ENT_QUOTES, 'UTF-8'); ?> DT</span>
                <?php if ($pctVsYesterday !== null) { ?>
                    — <?php echo $pctVsYesterday >= 0 ? '+' : ''; ?><?php echo (int) $pctVsYesterday; ?> % vs hier (volume de commandes).
                <?php } ?>
            <?php } else { ?>
                Aucune commande aujourd’hui pour l’instant. Profils santé actifs :
                <span><?php echo (int) ($profilStats['avec'] ?? 0); ?></span>
                — recettes référencées : <span><?php echo (int) $recettesCount; ?></span>.
            <?php } ?>
        </section>

        <div class="bo-charts-grid bo-dash-charts-tall">
            <section class="bo-panel" aria-labelledby="home-chart-orders">
                <h2 id="home-chart-orders">Aperçu commandes &amp; CA (7 jours)</h2>
                <div class="bo-home-chart-wrap">
                    <canvas id="homeChartOrders"></canvas>
                </div>
            </section>
            <section class="bo-panel" aria-labelledby="home-chart-users">
                <h2 id="home-chart-users">Utilisateurs par rôle</h2>
                <div class="bo-home-chart-wrap">
                    <canvas id="homeChartUsers"></canvas>
                </div>
            </section>
        </div>

        <section class="bo-panel bo-home-alerts" aria-label="Alertes">
            <h2>Alertes &amp; points d’attention</h2>
            <div class="bo-home-alert-list">
                <?php foreach ($alerts as $a) { ?>
                    <div class="bo-home-alert <?php echo htmlspecialchars($a['class'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span aria-hidden="true"><?php echo htmlspecialchars($a['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span><?php echo htmlspecialchars($a['text'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php } ?>
            </div>
        </section>

        <section class="bo-panel bo-home-table-wrap" aria-label="Dernières commandes">
            <h2>Dernières commandes</h2>
            <div class="bo-table-scroll">
                <table class="bo-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Produit(s)</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentCommandes === []) { ?>
                            <tr><td colspan="5" class="bo-empty">Aucune commande.</td></tr>
                        <?php } else { ?>
                            <?php foreach ($recentCommandes as $c) { ?>
                                <?php
                                $idC = (int) ($c['id_commande'] ?? 0);
                                $uid = (int) ($c['id_utilisateur'] ?? 0);
                                $client = $uid > 0 ? ($userNamesById[$uid] ?? ('#' . $uid)) : '—';
                                $noms = $cmdCtrl->getNomsProduitsCommande($idC);
                                if ($noms === '') {
                                    $noms = '—';
                                }
                                $badge = $commandeBadge($c);
                                $dAff = htmlspecialchars((string) ($c['date'] ?? $c['date_commande'] ?? ''), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td class="bo-td-left"><?php echo htmlspecialchars($client, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="bo-td-left"><?php echo htmlspecialchars($noms, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="bo-td-center"><?php echo htmlspecialchars(number_format((float) ($c['total'] ?? 0), 2, ',', ' '), ENT_QUOTES, 'UTF-8'); ?> DT</td>
                                    <td class="bo-td-center"><span class="<?php echo htmlspecialchars($badge['class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td class="bo-td-center"><?php echo $dAff; ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bo-panel bo-home-community" aria-label="Communauté">
            <h2>Aperçu communauté</h2>
            <div class="bo-home-community-grid">
                <div class="bo-home-mini">
                    <strong>Derniers posts</strong>
                    <?php if ($postsPreview === []) { ?>
                        Aucun post.
                    <?php } else { ?>
                        <ul style="margin:0;padding-left:1.1rem;">
                            <?php foreach ($postsPreview as $p) { ?>
                                <li><?php echo htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth((string) ($p['contenu'] ?? ''), 0, 72, '…') : substr((string) ($p['contenu'] ?? ''), 0, 72), ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
                <div class="bo-home-mini">
                    <strong>Post le plus aimé</strong>
                    <?php if ($topLikedPost) { ?>
                        <?php echo (int) ($topLikedPost['nombreLikes'] ?? 0); ?> likes —
                        <?php echo htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth((string) ($topLikedPost['contenu'] ?? ''), 0, 80, '…') : substr((string) ($topLikedPost['contenu'] ?? ''), 0, 80), ENT_QUOTES, 'UTF-8'); ?>
                    <?php } else { ?>
                        —
                    <?php } ?>
                </div>
                <div class="bo-home-mini">
                    <strong>Nouvel utilisateur</strong>
                    <?php if ($newestUser) { ?>
                        <?php echo htmlspecialchars(trim((string) ($newestUser['prenom'] ?? '') . ' ' . (string) ($newestUser['nom'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                        <br><span style="color:#64748b;font-size:0.82rem;"><?php echo htmlspecialchars((string) ($newestUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php } else { ?>
                        —
                    <?php } ?>
                </div>
            </div>
        </section>

        <section class="bo-panel bo-home-cal" aria-label="Semaine en cours">
            <h2>Semaine (aperçu)</h2>
            <div class="bo-home-cal-grid">
                <?php
                $joursFr = ['lun', 'mar', 'mer', 'jeu', 'ven', 'sam', 'dim'];
                $startWeek = new DateTimeImmutable('monday this week');
                for ($i = 0; $i < 7; $i++) {
                    $d = $startWeek->modify('+' . $i . ' days');
                    $isToday = $d->format('Y-m-d') === $todayYmd;
                    $cls = 'bo-home-cal-cell' . ($isToday ? ' bo-home-cal-cell--today' : '');
                    $idx = (int) $d->format('N') - 1;
                    $jf = $joursFr[$idx] ?? $d->format('D');
                    ?>
                    <div class="<?php echo htmlspecialchars($cls, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($jf, ENT_QUOTES, 'UTF-8'); ?><br>
                        <?php echo htmlspecialchars($d->format('j'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php } ?>
            </div>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    if (!window.Chart) return;
    var lineLabels = <?php echo json_encode($chartLineLabels, JSON_UNESCAPED_UNICODE); ?>;
    var lineCounts = <?php echo json_encode($chartLineCounts, JSON_UNESCAPED_UNICODE); ?>;
    var lineRevenue = <?php echo json_encode($chartLineRevenue, JSON_UNESCAPED_UNICODE); ?>;
    var c1 = document.getElementById('homeChartOrders');
    if (c1) {
        new Chart(c1, {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: [
                    {
                        label: 'Commandes',
                        data: lineCounts,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(134,239,172,0.35)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'CA (DT)',
                        data: lineRevenue,
                        borderColor: '#fb923c',
                        backgroundColor: 'rgba(253,186,116,0.2)',
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Nb commandes' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'CA' } }
                }
            }
        });
    }
    var donutLabels = <?php echo json_encode($donutLabels, JSON_UNESCAPED_UNICODE); ?>;
    var donutData = <?php echo json_encode($donutData, JSON_UNESCAPED_UNICODE); ?>;
    var c2 = document.getElementById('homeChartUsers');
    if (c2) {
        new Chart(c2, {
            type: 'doughnut',
            data: {
                labels: donutLabels,
                datasets: [{
                    data: donutData,
                    backgroundColor: ['#AB47BC', '#4CB963', '#29B6F6', '#FFCA28'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
})();
</script>
</body>
</html>
