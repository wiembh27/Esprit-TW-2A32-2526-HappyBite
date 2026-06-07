<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

if (empty($_SESSION['logged_in']) || strtolower((string) ($_SESSION['user_role'] ?? '')) !== 'nutritionniste') {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../Controllers/ChallengeController.php';

function nutri_status_label(string $statut): string
{
    $map = [
        'disponible' => 'nutritionist.status_disponible',
        'selectionne' => 'nutritionist.status_selectionne',
        'termine' => 'nutritionist.status_termine',
    ];

    return fo_t($map[$statut] ?? 'nutritionist.status_disponible');
}

$ctrl = new ChallengeController();
$nutritionnisteId = (int) $_SESSION['user_id'];
$success = '';
$error   = '';

function nutri_resolve_message(array $res): string
{
    if (!empty($res['message_key'])) {
        return fo_t((string) $res['message_key']);
    }

    return (string) ($res['message'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creer_challenge') {
    $res = $ctrl->creerChallenge(
        (string) ($_POST['titre'] ?? ''),
        (string) ($_POST['description'] ?? ''),
        $nutritionnisteId,
        $_FILES['image'] ?? null
    );
    if ($res['success']) {
        $success = nutri_resolve_message($res);
    } else {
        $error = nutri_resolve_message($res);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_challenge') {
    $res = $ctrl->supprimerChallenge((int) ($_POST['challenge_id'] ?? 0), $nutritionnisteId);
    if ($res['success']) {
        $success = nutri_resolve_message($res);
    } else {
        $error = nutri_resolve_message($res);
    }
}

$challengesPerPage = 4;
$challengesPage = max(1, (int) ($_GET['ch_page'] ?? $_POST['ch_page'] ?? 1));
$totalChallenges = $ctrl->countChallengesNutritionniste($nutritionnisteId);
$totalChallengePages = max(1, (int) ceil($totalChallenges / $challengesPerPage));
if ($challengesPage > $totalChallengePages) {
    $challengesPage = $totalChallengePages;
}

$mesChallenges = $ctrl->getChallengesNutritionniste($nutritionnisteId, $challengesPage, $challengesPerPage);

$nutriToastMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($success !== '') {
        $nutriToastMsg = $success;
    } elseif ($error !== '') {
        $nutriToastMsg = $error;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo fo_e('nav.nutritionist_space'); ?> — HappyBite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root{--green:#2C7E34;--green-mid:#256b2d;--mint:#2ec4b6;--bg:#f4f7f5;--border:#e3ebe6;--text:#1a1a1a;--muted:#6b7280;}
        html,body.nutri-dashboard{overflow-x:hidden;max-width:100%;}
        body.nutri-dashboard{background:var(--bg);color:var(--text);}
        .nutri-header{background:linear-gradient(135deg,#2C7E34,#2ec4b6);color:#fff;padding:2.5rem 2rem;border-radius:0 0 1.5rem 1.5rem;box-sizing:border-box;width:100%;max-width:100%;}
        .nutri-header h1{margin:0;font-size:1.8rem;}
        .nutri-header-inner{max-width:1100px;margin:0 auto;box-sizing:border-box;width:100%;}
        .badge-nutri{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);border-radius:999px;padding:4px 14px;font-size:.8rem;font-weight:600;margin-top:.5rem;}
        .nutri-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:1.5rem;box-sizing:border-box;width:100%;max-width:1100px;margin:2rem auto;padding:0 1.5rem;}
        @media(max-width:768px){.nutri-grid{grid-template-columns:minmax(0,1fr);}}
        .nutri-community-wrap{box-sizing:border-box;width:100%;max-width:1100px;margin:0 auto 3rem;padding:0 1.5rem;}
        .card-panel{background:#fff;border-radius:1rem;padding:1.5rem;box-shadow:0 2px 12px rgba(0,0,0,.08);border:1px solid var(--border);min-width:0;max-width:100%;}
        .card-panel h2{font-size:1.1rem;color:var(--green);margin:0 0 1rem;display:flex;align-items:center;gap:8px;min-width:0;}
        .form-group{margin-bottom:1rem;min-width:0;}
        .form-group label{display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.35rem;}
        .form-group input,.form-group textarea{width:100%;max-width:100%;box-sizing:border-box;padding:.65rem .9rem;border:1.5px solid var(--border);border-radius:.6rem;font-family:inherit;font-size:.9rem;transition:border-color .2s;background:#fff;color:var(--text);}
        .form-group input[type="file"]{padding:.45rem .5rem;}
        .form-group input:focus,.form-group textarea:focus{outline:none;border-color:var(--green);}
        .form-group textarea{resize:vertical;min-height:100px;}
        .btn-green{background:var(--green);color:#fff;border:none;border-radius:.6rem;padding:.7rem 1.5rem;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .2s;}
        .btn-green:hover{background:var(--green-mid);}
        .challenge-item{border:1px solid var(--border);border-radius:.7rem;padding:1rem;margin-bottom:.75rem;background:#fff;min-width:0;max-width:100%;}
        .challenge-item-head{display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;min-width:0;}
        .challenge-item h4{margin:0 0 .3rem;font-size:.95rem;color:var(--text);min-width:0;flex:1;word-break:break-word;}
        .challenge-item p{margin:0 0 .5rem;font-size:.82rem;color:var(--muted);overflow-wrap:anywhere;}
        .challenge-item-foot{display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;}
        .badge-statut{flex-shrink:0;}
        .badge-statut{display:inline-block;font-size:.72rem;font-weight:700;padding:2px 10px;border-radius:999px;}
        .badge-disponible{background:#d1fae5;color:#065f46;}
        .badge-selectionne{background:#dbeafe;color:#1d4ed8;}
        .badge-termine{background:#f3f4f6;color:#6b7280;}
        .btn-danger{background:#ef4444;color:#fff;border:none;border-radius:.5rem;padding:.35rem .8rem;font-size:.78rem;cursor:pointer;font-weight:600;}
        .btn-danger:hover{background:#dc2626;}
        .empty-state{text-align:center;color:#9ca3af;padding:2rem;font-size:.9rem;}
        .nutri-pagination{display:flex;align-items:center;justify-content:center;gap:16px;margin-top:1rem;}
        .nutri-btn-page{padding:10px 18px;min-width:48px;border-radius:10px;border:none;background:var(--green);color:#fff;cursor:pointer;font-weight:700;font-size:1rem;line-height:1;transition:background .2s ease,transform .12s ease;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;}
        .nutri-btn-page:hover:not(.is-disabled){background:var(--green-mid);}
        .nutri-btn-page.is-disabled{opacity:.45;cursor:not-allowed;pointer-events:none;}
        .nutri-page-info{font-weight:600;color:var(--muted);font-size:.92rem;}
        .nutri-community-panel{background:linear-gradient(135deg,#f0fdf4,#e0f7fa);border:1px solid #b2dfdb;}
        .nutri-community-panel p{color:#374151;}
    </style>
</head>
<body class="nutri-dashboard">
<?php
$nav_active = 'nutritionniste';
$hide_frigo = true;
$hide_sante = true;
$hide_panier = true;
$hide_ai = true;
require __DIR__ . '/includes/nav_front.php';
?>

<div class="nutri-header" style="margin:0;border-radius:0;padding:2rem 2rem 1.5rem;">
    <div class="nutri-header-inner">
        <h1><i class="fas fa-leaf"></i> <?php echo fo_e('nav.nutritionist_space'); ?></h1>
        <div class="badge-nutri"><i class="fas fa-check-circle"></i> <?php echo fo_e('nutritionist.verified_badge'); ?></div>
        <p style="margin:.5rem 0 0;opacity:.9;">
            <?php echo htmlspecialchars(trim((string) ($_SESSION['user_prenom'] ?? '') . ' ' . (string) ($_SESSION['user_nom'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </div>
</div>

<div class="nutri-grid">
    <div class="card-panel">
        <h2><i class="fas fa-plus-circle"></i> <?php echo fo_e('nutritionist.create_title'); ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="creer_challenge">
            <div class="form-group">
                <label for="titre"><?php echo fo_e('nutritionist.field_title'); ?></label>
                <input type="text" name="titre" id="titre" required placeholder="<?php echo fo_e('nutritionist.field_title_ph'); ?>">
            </div>
            <div class="form-group">
                <label for="description"><?php echo fo_e('nutritionist.field_description'); ?></label>
                <textarea name="description" id="description" required placeholder="<?php echo fo_e('nutritionist.field_description_ph'); ?>"></textarea>
            </div>
            <div class="form-group">
                <label for="image_challenge"><?php echo fo_e('nutritionist.field_image'); ?></label>
                <input type="file" name="image" id="image_challenge" accept="image/*">
                <small style="color:var(--muted);font-size:.75rem;"><?php echo fo_e('nutritionist.field_image_hint'); ?></small>
            </div>
            <button type="submit" class="btn-green"><?php echo fo_e('nutritionist.publish_btn'); ?></button>
        </form>
    </div>

    <div class="card-panel">
        <h2><i class="fas fa-list-check"></i> <?php echo htmlspecialchars(sprintf(fo_t('nutritionist.my_challenges'), $totalChallenges), ENT_QUOTES, 'UTF-8'); ?></h2>

        <?php if ($mesChallenges === []): ?>
            <div class="empty-state"><i class="fas fa-inbox fa-2x" style="margin-bottom:.5rem;display:block;"></i><?php echo fo_e('nutritionist.empty_challenges'); ?></div>
        <?php else: ?>
            <?php foreach ($mesChallenges as $ch): ?>
            <div class="challenge-item">
                <div class="challenge-item-head">
                    <h4><?php echo htmlspecialchars((string) $ch['titre'], ENT_QUOTES, 'UTF-8'); ?></h4>
                    <span class="badge-statut badge-<?php echo htmlspecialchars((string) $ch['statut'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(nutri_status_label((string) ($ch['statut'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <p><?php echo htmlspecialchars(mb_strimwidth((string) $ch['description'], 0, 120, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="challenge-item-foot">
                    <small style="color:#9ca3af;font-size:.75rem;"><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime((string) $ch['dateCreation']) ?: time()); ?></small>
                    <?php if (($ch['statut'] ?? '') === 'disponible'): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm(<?php echo json_encode(fo_t('nutritionist.delete_confirm'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);">
                        <input type="hidden" name="action" value="supprimer_challenge">
                        <input type="hidden" name="ch_page" value="<?php echo (int) $challengesPage; ?>">
                        <input type="hidden" name="challenge_id" value="<?php echo (int) $ch['id']; ?>">
                        <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> <?php echo fo_e('nutritionist.delete_btn'); ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($totalChallengePages > 1): ?>
                <div class="nutri-pagination">
                    <?php if ($challengesPage > 1): ?>
                        <a
                            class="nutri-btn-page"
                            href="nutritionniste_dashboard.php?ch_page=<?php echo (int) ($challengesPage - 1); ?>"
                            aria-label="<?php echo fo_e('nutritionist.page_prev'); ?>"
                        >&lt;</a>
                    <?php else: ?>
                        <span class="nutri-btn-page is-disabled" aria-hidden="true">&lt;</span>
                    <?php endif; ?>

                    <span class="nutri-page-info">
                        <?php echo htmlspecialchars(sprintf(fo_t('health.page_n'), $challengesPage, $totalChallengePages), ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                    <?php if ($challengesPage < $totalChallengePages): ?>
                        <a
                            class="nutri-btn-page"
                            href="nutritionniste_dashboard.php?ch_page=<?php echo (int) ($challengesPage + 1); ?>"
                            aria-label="<?php echo fo_e('nutritionist.page_next'); ?>"
                        >&gt;</a>
                    <?php else: ?>
                        <span class="nutri-btn-page is-disabled" aria-hidden="true">&gt;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="nutri-community-wrap">
    <div class="card-panel nutri-community-panel">
        <h2 style="color:var(--green);margin:0 0 .5rem;"><i class="fas fa-users"></i> <?php echo fo_e('nav.community'); ?></h2>
        <p style="margin:0 0 1rem;font-size:.9rem;"><?php echo fo_e('nutritionist.community_desc'); ?></p>
        <a href="Communaute.php" class="btn-green" style="display:inline-block;text-decoration:none;"><?php echo fo_e('nutritionist.community_btn'); ?></a>
    </div>
</div>

<footer style="text-align:center;padding:1rem;color:#2C7E34;font-weight:400;font-family:Poppins,sans-serif;">
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<?php if ($nutriToastMsg !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.hbShowActionToast === 'function') {
        window.hbShowActionToast(<?php echo json_encode($nutriToastMsg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 3500);
    }
});
</script>
<?php endif; ?>

</body>
</html>
