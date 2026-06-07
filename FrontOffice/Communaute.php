<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Controllers/UtilisateurPhotoSql.php';
require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/../Controllers/CommentaireController.php';
require_once __DIR__ . '/../Controllers/StoryController.php';
require_once __DIR__ . '/../Controllers/GeminiController.php';
require_once __DIR__ . '/../Controllers/UploadStorage.php';

function communaute_media_url(?string $stored): string
{
    return UploadStorage::publicUrl($stored, '../');
}

$postController = new PostController();
$commentaireController = new CommentaireController();
$storyController = new StoryController();

if (empty($_SESSION['story_visitor_key'])) {
    $_SESSION['story_visitor_key'] = bin2hex(random_bytes(16));
}
$storyVisitorKey = (string) $_SESSION['story_visitor_key'];

/**
 * Relative time in French (e.g. il y a 1 heure).
 */
function temps_ecoule_fr(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 10) {
        return fo_t('community.time_now');
    }
    if ($diff < 60) {
        return fo_t('community.time_seconds');
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m === 1 ? fo_t('community.time_minute') : sprintf(fo_t('community.time_minutes'), $m);
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h === 1 ? fo_t('community.time_hour') : sprintf(fo_t('community.time_hours'), $h);
    }
    if ($diff < 604800) {
        $d = (int) floor($diff / 86400);
        return $d === 1 ? fo_t('community.time_day') : sprintf(fo_t('community.time_days'), $d);
    }
    return date('d/m/Y', $ts);
}

function communaute_utilisateur_pk(PDO $pdo): string
{
    static $col = null;
    if ($col !== null) {
        return $col;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'utilisateur', 'c' => 'id']);
    if ((int) $stmt->fetchColumn() > 0) {
        $col = 'id';
        return $col;
    }
    $stmt->execute(['t' => 'utilisateur', 'c' => 'id_utilisateur']);
    $col = (int) $stmt->fetchColumn() > 0 ? 'id_utilisateur' : 'id';
    return $col;
}

function communaute_load_profile_photo(PDO $pdo, int $userId): ?string
{
    if ($userId <= 0) {
        return null;
    }
    try {
        $cols = utilisateur_photo_db_columns($pdo);
        if ($cols === []) {
            return null;
        }
        $pk = communaute_utilisateur_pk($pdo);
        $select = [];
        foreach ($cols as $c) {
            $select[] = $c === 'profil-image' ? '`profil-image`' : $c;
        }
        $q = $pdo->prepare('SELECT ' . implode(', ', $select) . " FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
        $q->bindValue(':id', $userId, PDO::PARAM_INT);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        foreach ($cols as $c) {
            if (!empty($row[$c])) {
                return (string) $row[$c];
            }
        }
        return null;
    } catch (PDOException $e) {
        return null;
    }
}

function communaute_profile_public_url(?string $rel): ?string
{
    if ($rel === null || trim($rel) === '') {
        return null;
    }
    $p = trim($rel);
    if (preg_match('#^https?://#i', $p)) {
        return str_replace(' ', '%20', $p);
    }
    return str_replace(' ', '%20', '../' . ltrim($p, '/'));
}

function communaute_initials(string $prenom, string $nom): string
{
    $a = mb_strtoupper(mb_substr(trim($prenom), 0, 1, 'UTF-8'), 'UTF-8');
    $b = mb_strtoupper(mb_substr(trim($nom), 0, 1, 'UTF-8'), 'UTF-8');
    $s = $a . $b;
    return $s !== '' ? $s : 'M';
}

/** Nom puis prénom (affichage style carte d’identité). */
function communaute_display_nom_prenom(?string $nom, ?string $prenom): string
{
    $n = trim((string) $nom . ' ' . (string) $prenom);
    return $n !== '' ? $n : 'Membre';
}

/**
 * @param string $classes Optional extra CSS classes on the root element
 */
function communaute_avatar_html(?string $photoRel, string $prenom, string $nom, string $classes = ''): string
{
    $url = communaute_profile_public_url($photoRel);
    $init = htmlspecialchars(communaute_initials($prenom, $nom), ENT_QUOTES, 'UTF-8');
    $cls = trim('user-avatar-img ' . $classes);
    if ($url) {
        return '<img class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" src="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="">';
    }
    $divCls = trim('avatar-circle ' . $classes);
    return '<div class="' . htmlspecialchars($divCls, ENT_QUOTES, 'UTF-8') . '">' . $init . '</div>';
}

/** Avatar for post comments (32px ring, profile or initials). */
function communaute_comment_avatar_html(?string $photoRel, string $prenom, string $nom): string
{
    $url = communaute_profile_public_url($photoRel);
    $init = htmlspecialchars(communaute_initials($prenom, $nom), ENT_QUOTES, 'UTF-8');
    if ($url) {
        return '<img class="user-avatar-img comment-avatar" src="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="">';
    }
    return '<div class="comment-avatar">' . $init . '</div>';
}

$commLoggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$commUserId = $commLoggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;
$commPrenom = $commLoggedIn ? (string) ($_SESSION['user_prenom'] ?? '') : '';
$commNom = $commLoggedIn ? (string) ($_SESSION['user_nom'] ?? '') : '';
$commRole = $commLoggedIn ? strtolower(trim((string) ($_SESSION['user_role'] ?? 'client'))) : '';
$commPhotoRel = null;
if ($commLoggedIn && $commUserId > 0) {
    try {
        $commPhotoRel = communaute_load_profile_photo(Database::getConnection(), $commUserId);
    } catch (Throwable $e) {
        $commPhotoRel = null;
    }
}
$commPhotoUrl = communaute_profile_public_url($commPhotoRel);
$commComposerDisplayName = communaute_display_nom_prenom($commNom, $commPrenom);

$message = '';
$messageType = '';

if (isset($_GET['success'])) { $message = fo_t('community.flash_post_published'); $messageType = 'success'; }
if (isset($_GET['updated'])) { $message = fo_t('community.flash_post_updated'); $messageType = 'success'; }
if (isset($_GET['comment_success'])) { $message = fo_t('community.flash_comment_added'); $messageType = 'success'; }
if (isset($_GET['comment_updated'])) { $message = fo_t('community.flash_comment_updated'); $messageType = 'success'; }
if (isset($_GET['comment_deleted'])) { $message = fo_t('community.flash_comment_deleted'); $messageType = 'success'; }
if (isset($_GET['story_success'])) { $message = fo_t('community.flash_story_added'); $messageType = 'success'; }
if (isset($_GET['story_deleted'])) { $message = fo_t('community.flash_story_deleted'); $messageType = 'success'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    if ($_POST['action'] === 'add_post') {
        if (!$commLoggedIn || $commUserId < 1) {
            $message = fo_t('community.login_publish_post');
            $messageType = 'warning';
        } else {
        $contenu = $_POST['contenu'] ?? '';
        if (!empty($contenu)) {
            $image = null;
            if (!empty($_POST['ai_image'])) {
                $image = trim((string) $_POST['ai_image']);
            }
            if (!empty($_FILES['image']['name'])) {
                $uploaded = UploadStorage::saveUploadedImage($_FILES['image'], 'posts');
                if (!empty($uploaded['success']) && !empty($uploaded['path'])) {
                    $image = (string) $uploaded['path'];
                }
            }
            if ($postController->create($contenu, $image, $commUserId)) { header('Location: Communaute.php?success=1'); exit; }
            else { $message = 'Erreur lors de la publication du post.'; $messageType = 'danger'; }
        } else { $message = 'Le contenu du post ne peut pas être vide.'; $messageType = 'warning'; }
        }
    } elseif ($_POST['action'] === 'update_post') {
        $id = (int)$_POST['id']; $contenu = $_POST['contenu'] ?? '';
        if (!empty($contenu)) {
            if ($postController->update($id, $contenu)) {
                if ($isAjax) { echo json_encode(['success' => true, 'id' => $id, 'contenu' => $contenu]); exit; }
                header('Location: Communaute.php?updated=1'); exit;
            } else { $message = 'Erreur lors de la mise à jour du post.'; $messageType = 'danger'; }
        } else { $message = 'Le contenu du post ne peut pas être vide.'; $messageType = 'warning'; }
    } elseif ($_POST['action'] === 'delete_post') {
        $id = (int)$_POST['id'];
        if ($postController->delete($id)) {
            if ($isAjax) { echo json_encode(['success' => true, 'id' => $id]); exit; }
            $message = fo_t('community.flash_post_deleted'); $messageType = 'success';
        } else { $message = 'Erreur lors de la suppression du post.'; $messageType = 'danger'; }
    } elseif ($_POST['action'] === 'like_post') {
        $id = (int)$_POST['id']; $liked = $_POST['liked'] === 'true';
        if ($liked) {
            $postController->removeLike($id);
        } else {
            $postController->addLike($id);
            if ($commLoggedIn && $commUserId > 0) {
                require_once __DIR__ . '/../Controllers/UserNotificationService.php';
                user_notification_post_liked(Database::getConnection(), $id, $commUserId);
            }
        }
        $post = $postController->getById($id);
        echo json_encode(['success' => true, 'likes' => $post['nombreLikes']]); exit;
    } elseif ($_POST['action'] === 'add_comment') {
        $post_id = (int) ($_POST['post_id'] ?? 0);
        $contenu = trim((string) ($_POST['contenu'] ?? ''));
        if ($post_id <= 0) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'error' => 'invalid_post'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $message = 'Publication invalide.';
            $messageType = 'danger';
        } elseif ($contenu !== '') {
            if (!$commLoggedIn || $commUserId < 1) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'error' => 'not_logged_in'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $message = fo_t('community.login_comment');
                $messageType = 'warning';
            } else {
            $commentId = $commentaireController->create($contenu, $post_id, $commUserId);
            if ($commentId !== false) {
                require_once __DIR__ . '/../Controllers/UserNotificationService.php';
                user_notification_post_commented(Database::getConnection(), $post_id, $commUserId, (int) $commentId);
                if ($isAjax) {
                    echo json_encode([
                        'success' => true,
                        'id' => $commentId,
                        'post_id' => $post_id,
                        'contenu' => $contenu,
                        'dateCommentaire' => temps_ecoule_fr(date('Y-m-d H:i:s')),
                        'prenom' => $commPrenom,
                        'nom' => $commNom,
                        'photoUrl' => $commPhotoUrl,
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                header('Location: Communaute.php?comment_success=1');
                exit;
            }
            if ($isAjax) {
                echo json_encode(['success' => false, 'error' => 'db'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $message = "Erreur lors de l'ajout du commentaire.";
            $messageType = 'danger';
            }
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'error' => 'empty'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $message = 'Le commentaire ne peut pas être vide.';
            $messageType = 'warning';
        }
    } elseif ($_POST['action'] === 'update_comment') {
        $id = (int) ($_POST['id'] ?? 0);
        $contenu = trim((string) ($_POST['contenu'] ?? ''));
        if ($contenu !== '') {
            if ($commentaireController->update($id, $contenu)) {
                if ($isAjax) {
                    echo json_encode(['success' => true, 'id' => $id, 'contenu' => $contenu], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                header('Location: Communaute.php?comment_updated=1');
                exit;
            }
            if ($isAjax) {
                echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $message = 'Erreur lors de la mise à jour du commentaire.';
            $messageType = 'danger';
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'error' => 'empty'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $message = 'Le commentaire ne peut pas être vide.';
            $messageType = 'warning';
        }
    } elseif ($_POST['action'] === 'delete_comment') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($commentaireController->delete($id)) {
            if ($isAjax) {
                echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: Communaute.php?comment_deleted=1');
            exit;
        }
        if ($isAjax) {
            echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $message = 'Erreur lors de la suppression du commentaire.';
        $messageType = 'danger';
    } elseif ($_POST['action'] === 'add_story') {
        if (!$commLoggedIn || $commUserId < 1) {
            $message = fo_t('community.login_add_story');
            $messageType = 'warning';
        } else {
        if (!empty($_FILES['image']['name']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = UploadStorage::saveUploadedImage($_FILES['image'], 'stories');
            if (!empty($uploaded['success']) && !empty($uploaded['path'])) {
                $imagePath = (string) $uploaded['path'];
                if ($storyController->create($imagePath, $commUserId)) {
                    header('Location: Communaute.php?story_success=1');
                    exit;
                }
                $serverPath = UploadStorage::serverPath($imagePath);
                if (is_file($serverPath)) {
                    @unlink($serverPath);
                }
            }
        }
        $message = 'Erreur lors de l\'ajout de la story.';
        $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'delete_story') {
        $id = (int)$_POST['id'];
        if ($storyController->delete($id)) {
            if ($isAjax) { echo json_encode(['success' => true, 'id' => $id]); exit; }
            header('Location: Communaute.php?story_deleted=1'); exit;
        } else {
            if ($isAjax) { echo json_encode(['success' => false]); exit; }
            $message = 'Erreur lors de la suppression de la story.'; $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'story_toggle_like') {
        $sid = (int) ($_POST['story_id'] ?? 0);
        if ($sid <= 0) {
            echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $r = $storyController->toggleLike($sid, $storyVisitorKey);
        echo json_encode(['success' => true, 'liked' => $r['liked'], 'count' => $r['count']], JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($_POST['action'] === 'story_add_comment') {
        $sid = (int) ($_POST['story_id'] ?? 0);
        $contenu = trim((string) ($_POST['contenu'] ?? ''));
        if ($sid <= 0 || $contenu === '') {
            echo json_encode(['success' => false, 'error' => 'invalid'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$commLoggedIn || $commUserId < 1) {
            echo json_encode(['success' => false, 'error' => 'not_logged_in'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $cid = $storyController->addComment($sid, $contenu, $commUserId);
        if ($cid === false) {
            echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'success' => true,
            'id' => $cid,
            'contenu' => $contenu,
            'dateCommentaire' => temps_ecoule_fr(date('Y-m-d H:i:s')),
            'commentCount' => $storyController->countComments($sid),
            'prenom' => $commPrenom,
            'nom' => $commNom,
            'photoUrl' => $commPhotoUrl,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($_POST['action'] === 'get_story_comments') {
        $sid = (int) ($_POST['story_id'] ?? 0);
        if ($sid <= 0) {
            echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $list = $storyController->getCommentsByStoryId($sid);
        $out = [];
        foreach ($list as $c) {
            $ap = (string) ($c['auteur_prenom'] ?? '');
            $an = (string) ($c['auteur_nom'] ?? '');
            $out[] = [
                'id' => (int) $c['id'],
                'contenu' => $c['contenu'],
                'dateCommentaire' => temps_ecoule_fr($c['dateCommentaire']),
                'prenom' => $ap,
                'nom' => $an,
                'photoUrl' => communaute_profile_public_url(isset($c['auteur_photo']) ? (string) $c['auteur_photo'] : null),
            ];
        }
        echo json_encode(['success' => true, 'comments' => $out], JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($_POST['action'] === 'generate_image') {
        header('Content-Type: application/json; charset=utf-8');
        if (!$commLoggedIn || $commUserId < 1) {
            echo json_encode(['success' => false, 'message' => fo_t('community.login_generate_image')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require_once __DIR__ . '/../Controllers/CommunauteImageService.php';
        $gen = communaute_generate_food_image((string) ($_POST['prompt'] ?? ''));
        if (!empty($gen['ok'])) {
            echo json_encode(['success' => true, 'image' => $gen['image']], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $gen['message'] ?? fo_t('community.image_gen_error'),
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

// Load posts (large limit — no pagination on this page)
$posts = $postController->getAll(3000, 0);
$stories = $storyController->getActiveStories();
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title><?php echo fo_e('community.title'); ?> - HappyBite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #2C7E34;
            --green-dark: #256b2d;
            --green-light: #eaf4ef;
            --green-mid: #3d9a47;
            --accent: #f0a500;
            --bg: #f4f6f9;
            --card-bg: #ffffff;
            --text: #1a1a2e;
            --text-muted: #6b7280;
            --border: #e8ecf0;
            --shadow: 0 4px 24px rgba(44,126,52,0.1);
            --shadow-hover: 0 8px 32px rgba(44,126,52,0.18);
            --radius: 16px;
            --radius-sm: 10px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        footer.site-copyright {
            margin-top: auto;
            padding: 1rem;
            text-align: center;
            background: #fff;
            color: var(--green);
            border-top: 1px solid var(--border);
            font-size: 0.9rem;
            font-weight: 400;
        }

        button, .btn, [type="button"], [type="submit"] { font-weight: 600; }
        h4, h5, h6, label, .form-label { font-weight: 500; }

        /* ── NAVBAR ── */
        .main-navbar {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            box-shadow: 0 1px 0 var(--border), 0 4px 20px rgba(0,0,0,0.04);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .main-navbar .nav-container {
            width: 90%; max-width: 1400px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between; gap: 24px;
        }
        .main-navbar .nav-logo {
            display: flex; align-items: center; gap: 10px; text-decoration: none !important; flex-shrink: 0;
        }
        .main-navbar .nav-logo img { height: 40px; width: auto; }
        .main-navbar .nav-logo span { font-weight: 700; font-size: 1.2rem; color: var(--green) !important; }
        .main-navbar .nav-links {
            list-style: none; display: flex; align-items: center; gap: 6px; margin: 0; padding: 0;
        }
        .main-navbar .nav-links li a {
            text-decoration: none !important; color: var(--text-muted) !important;
            font-weight: 500; font-size: 0.95rem; padding: 8px 14px; border-radius: 8px;
            transition: all 0.2s ease;
        }
        .main-navbar .nav-links li a:hover { color: var(--green) !important; background: var(--green-light); }
        .main-navbar .nav-links li a.active {
            color: var(--green) !important; font-weight: 700; background: var(--green-light);
        }
        .main-navbar .nav-user { display: flex; align-items: center; gap: 8px; }
        .main-navbar .nav-action {
            text-decoration: none !important; color: var(--text-muted) !important;
            font-weight: 500; padding: 8px 14px; border-radius: 8px; transition: all 0.2s ease; font-size: 0.9rem;
        }
        .main-navbar .nav-action:hover { background: var(--green-light); color: var(--green) !important; }
        @media (max-width: 900px) {
            .main-navbar .nav-container { flex-wrap: wrap; justify-content: center; }
        }

        /* ── HERO BANNER ── */
        .community-hero {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-mid) 60%, #6dbf9e 100%);
            padding: 48px 0 56px;
            position: relative;
            overflow: hidden;
        }
        .community-hero::before {
            content: '';
            position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .community-hero .hero-inner {
            max-width: 700px; margin: 0 auto; text-align: center; position: relative; padding: 0 20px;
        }
        .community-hero h1 {
            font-size: 2.4rem; font-weight: 700; color: #fff; margin-bottom: 12px; letter-spacing: -0.5px;
        }
        .community-hero p { font-size: 1.05rem; color: rgba(255,255,255,0.85); margin-bottom: 0; }
        .hero-stats {
            display: flex; justify-content: center; gap: 40px; margin-top: 28px;
        }
        .hero-stat { text-align: center; }
        .hero-stat .stat-num { font-size: 1.6rem; font-weight: 700; color: #fff; display: block; }
        .hero-stat .stat-label { font-size: 0.8rem; color: rgba(255,255,255,0.75); text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── LAYOUT ── */
        .page-layout {
            max-width: 680px;
            margin: 0 auto;
            padding: 28px 16px;
        }
        .feed-col { width: 100%; }

        /* ── SIDEBAR ── */
        .sidebar-widget {
            background: var(--card-bg); border-radius: var(--radius);
            padding: 22px; box-shadow: var(--shadow); margin-bottom: 20px;
        }
        .sidebar-widget h6 {
            font-size: 0.8rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.8px; color: var(--text-muted); margin-bottom: 16px;
        }
        .sidebar-tip {
            display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px;
        }
        .sidebar-tip:last-child { margin-bottom: 0; }
        .tip-icon {
            width: 36px; height: 36px; border-radius: 10px; background: var(--green-light);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            color: var(--green); font-size: 0.9rem;
        }
        .tip-text { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; }
        .tip-text strong { color: var(--text); display: block; margin-bottom: 2px; }

        /* ── ALERT ── */
        .toast-alert {
            border-radius: var(--radius-sm); border: none; font-size: 0.9rem;
            animation: slideDown 0.4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── CREATE POST CARD ── */
        .create-post-card {
            background: var(--card-bg); border-radius: var(--radius);
            padding: 22px; box-shadow: var(--shadow); margin-bottom: 24px;
            transition: box-shadow 0.3s ease;
        }
        .create-post-card:hover { box-shadow: var(--shadow-hover); }
        .create-post-header {
            display: flex; align-items: center; gap: 14px; margin-bottom: 16px;
        }
        .avatar-circle {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--green-mid));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 1rem; flex-shrink: 0;
        }
        .user-avatar-img {
            border-radius: 50%; object-fit: cover; flex-shrink: 0;
            display: block;
        }
        .create-post-header .user-avatar-img,
        .create-post-header .avatar-circle { width: 44px; height: 44px; }
        .post-author-info .user-avatar-img,
        .post-author-info .avatar-circle { width: 40px; height: 40px; font-size: 0.85rem; }
        .comment-item .user-avatar-img {
            width: 32px; height: 32px; background: none;
        }
        .comment-item .comment-avatar.user-avatar-img {
            display: block;
        }
        .comment-form-row .user-avatar-img,
        .comment-form-row .avatar-circle { width: 32px; height: 32px; font-size: 0.72rem; }
        .create-post-header span { color: var(--text-muted); font-size: 0.9rem; }
        .post-textarea {
            width: 100%; border: 2px solid var(--border); border-radius: var(--radius-sm);
            padding: 14px 16px; resize: none; font-family: inherit; font-size: 0.95rem;
            color: var(--text); background: #fafbfc; transition: all 0.25s ease; line-height: 1.6;
        }
        .post-textarea:focus {
            outline: none; border-color: var(--green);
            background: #fff; box-shadow: 0 0 0 4px rgba(47,111,87,0.08);
        }
        .post-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 14px; flex-wrap: wrap; gap: 10px;
        }
        .file-label {
            display: flex; align-items: center; gap: 8px; cursor: pointer;
            color: var(--text-muted); font-size: 0.88rem; font-weight: 500;
            padding: 8px 14px; border-radius: 8px; border: 1.5px dashed var(--border);
            transition: all 0.2s ease;
        }
        .file-label:hover { border-color: var(--green); color: var(--green); background: var(--green-light); }
        .file-label i { font-size: 1rem; }
        #postImage { display: none; }
        .image-preview-wrap { margin-top: 12px; position: relative; display: inline-block; }
        .image-preview-wrap img {
            max-height: 200px; border-radius: var(--radius-sm); border: 2px solid var(--border);
        }
        .remove-preview {
            position: absolute; top: -8px; right: -8px; width: 24px; height: 24px;
            background: #ef4444; color: #fff; border: none; border-radius: 50%;
            font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        .btn-publish {
            background: linear-gradient(135deg, var(--green), var(--green-mid));
            color: #fff; border: none; padding: 10px 28px; border-radius: 10px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.25s ease; display: flex; align-items: center; gap: 8px;
        }
        .btn-publish:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(47,111,87,0.35); }
        .btn-publish:active { transform: translateY(0); }
        .error-message { color: #ef4444; font-size: 0.8rem; margin-top: 6px; }

        /* ── POST CARD ── */
        .post-card {
            background: var(--card-bg); border-radius: var(--radius);
            margin-bottom: 20px; box-shadow: var(--shadow);
            transition: all 0.3s ease; overflow: hidden;
            animation: fadeUp 0.5s ease both;
        }
        .post-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-2px); }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .post-card:nth-child(1) { animation-delay: 0.05s; }
        .post-card:nth-child(2) { animation-delay: 0.1s; }
        .post-card:nth-child(3) { animation-delay: 0.15s; }
        .post-card:nth-child(4) { animation-delay: 0.2s; }
        .post-card:nth-child(5) { animation-delay: 0.25s; }

        .post-header {
            padding: 18px 20px 14px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .post-author-info { display: flex; align-items: center; gap: 12px; }
        .post-author-name {
            font-weight: 700; font-size: 0.95rem; color: var(--text);
            display: flex; flex-wrap: wrap; align-items: center; gap: 4px;
        }
        .post-card.post-nutritionniste {
            border: 2px solid #2ec4b6;
            background: linear-gradient(to bottom right, #f0fdf4, #e0f7fa);
            position: relative;
        }
        .post-card.post-nutritionniste::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2C7E34, #2ec4b6);
            border-radius: 1rem 1rem 0 0;
        }
        .badge-nutritionniste {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, #2C7E34, #2ec4b6);
            color: #fff;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 0.72rem;
            font-weight: 700;
            margin-left: 2px;
            vertical-align: middle;
        }
        .post-date { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
        .menu-dots {
            background: none; border: none; width: 34px; height: 34px; border-radius: 8px;
            cursor: pointer; color: var(--text-muted); font-size: 1rem;
            display: flex; align-items: center; justify-content: center; transition: all 0.2s;
        }
        .menu-dots:hover { background: var(--bg); color: var(--text); }

        .post-body { padding: 0 20px 16px; }
        .post-body p { font-size: 0.95rem; line-height: 1.7; color: var(--text); margin: 0; }
        .post-image {
            width: 100%; height: auto; display: block;
            border-radius: var(--radius-sm); margin-top: 14px;
        }

        .post-actions {
            padding: 4px 12px 12px;
            display: flex; gap: 6px; border-top: 1px solid var(--border); padding-top: 12px; margin: 0 8px;
        }
        .post-action-btn {
            flex: 1; border: none; background: none; cursor: pointer;
            padding: 9px 12px; border-radius: 10px; transition: all 0.2s ease;
            color: var(--text-muted); font-size: 0.88rem; font-weight: 500;
            display: flex; align-items: center; justify-content: center; gap: 7px;
        }
        .post-action-btn:hover { background: var(--bg); color: var(--text); }
        .post-action-btn.liked { color: #ef4444; }
        .post-action-btn.liked:hover { background: #fef2f2; }
        .post-action-btn i { font-size: 1rem; transition: transform 0.2s ease; }
        .post-action-btn:hover i { transform: scale(1.15); }
        .post-action-btn.liked i { animation: heartPop 0.3s ease; }
        @keyframes heartPop {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.4); }
            100% { transform: scale(1); }
        }

        /* ── COMMENTS ── */
        .comments-section {
            background: #f8faf9; border-top: 1px solid var(--border);
            padding: 16px 20px; max-height: 500px; overflow-y: auto;
        }
        .comments-section.hidden { display: none; }
        .comment-item {
            display: flex; gap: 10px; margin-bottom: 14px;
            animation: fadeUp 0.3s ease both;
        }
        .comment-avatar {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--green-mid), #6dbf9e);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.75rem; font-weight: 700;
        }
        .comment-bubble {
            background: #fff; border-radius: 0 12px 12px 12px;
            padding: 10px 14px; flex: 1; border: 1px solid var(--border);
            position: relative;
        }
        .comment-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .comment-author { font-weight: 700; font-size: 0.82rem; color: var(--text); }
        .comment-date { font-size: 0.75rem; color: var(--text-muted); }
        .comment-text { font-size: 0.88rem; color: var(--text); line-height: 1.5; }
        .comment-actions-row { display: flex; gap: 6px; margin-top: 6px; }
        .comment-action-link {
            background: none; border: none; font-size: 0.75rem; color: var(--text-muted);
            cursor: pointer; padding: 2px 6px; border-radius: 4px; transition: all 0.15s;
        }
        .comment-action-link:hover { background: var(--bg); color: var(--green); }
        .comment-action-link.danger:hover { color: #ef4444; background: #fef2f2; }

        .comment-form-row { display: flex; gap: 10px; align-items: flex-start; margin-top: 12px; }
        .comment-textarea {
            flex: 1; border: 1.5px solid var(--border); border-radius: 10px;
            padding: 9px 13px; font-size: 0.88rem; font-family: inherit; resize: none;
            transition: all 0.2s ease; background: #fff;
        }
        .comment-textarea:focus {
            outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(47,111,87,0.08);
        }
        .btn-comment {
            background: var(--green); color: #fff; border: none; padding: 9px 18px;
            border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer;
            transition: all 0.2s ease; white-space: nowrap;
        }
        .btn-comment:hover { background: var(--green-dark); transform: translateY(-1px); }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center; padding: 60px 20px;
            background: var(--card-bg); border-radius: var(--radius); box-shadow: var(--shadow);
        }
        .empty-icon {
            width: 80px; height: 80px; border-radius: 50%; background: var(--green-light);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; font-size: 2rem; color: var(--green);
        }
        .empty-state h5 { font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; }

        /* ── MODALS ── */
        .modal-custom {
            display: none; position: fixed; z-index: 2000; inset: 0;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-custom.show { display: flex; animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-custom-content {
            background: #fff; border-radius: var(--radius); padding: 32px;
            max-width: 440px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: scaleIn 0.25s ease;
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }
        .modal-custom-content h4 { font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .modal-custom-content p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 22px; }
        .modal-custom-content .form-control {
            border: 1.5px solid var(--border); border-radius: 10px; font-size: 0.9rem; padding: 10px 14px;
        }
        .modal-custom-content .form-control:focus {
            border-color: var(--green); box-shadow: 0 0 0 3px rgba(47,111,87,0.1);
        }
        .modal-footer-btns { display: flex; gap: 10px; margin-top: 18px; }
        .modal-footer-btns .btn { flex: 1; border-radius: 10px; font-weight: 600; padding: 10px; }
        .btn-green { background: var(--green); color: #fff; border: none; }
        .btn-green:hover { background: var(--green-dark); color: #fff; }
        .modal-icon { font-size: 2.5rem; margin-bottom: 14px; }

        /* ── DROPDOWN ── */
        .dropdown-menu {
            border: 1px solid var(--border); border-radius: var(--radius-sm);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1); padding: 6px;
        }
        .dropdown-item {
            border-radius: 7px; font-size: 0.88rem; padding: 8px 12px;
            display: flex; align-items: center; gap: 8px;
        }
        .dropdown-item:hover { background: var(--bg); }
        .dropdown-item.text-danger:hover { background: #fef2f2; }

        /* ── SCROLLBAR ── */
        .comments-section::-webkit-scrollbar { width: 4px; }
        .comments-section::-webkit-scrollbar-track { background: transparent; }
        .comments-section::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        /* ── STORIES ── */
        .stories-wrapper {
            background: var(--card-bg); border-radius: var(--radius);
            padding: 16px 20px; box-shadow: var(--shadow); margin-bottom: 24px;
        }
        .stories-wrapper h6 {
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.8px; color: var(--text-muted); margin-bottom: 14px;
        }
        .stories-container {
            display: flex; gap: 14px; overflow-x: auto; padding-bottom: 4px;
            scrollbar-width: none;
        }
        .stories-container::-webkit-scrollbar { display: none; }
        .story-item { display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; flex-shrink: 0; }
        .story-ring {
            width: 62px; height: 62px; border-radius: 50%; padding: 2.5px;
            background: linear-gradient(45deg, #f0a500, var(--green), #6dbf9e);
        }
        .story-ring.add-ring { background: none; border: 2px dashed var(--green); }
        .story-ring-inner {
            width: 100%; height: 100%; border-radius: 50%; border: 2px solid #fff;
            overflow: hidden; background: var(--green-light);
            display: flex; align-items: center; justify-content: center;
        }
        .story-ring-inner img { width: 100%; height: 100%; object-fit: cover; }
        .story-ring-inner .add-icon { font-size: 1.3rem; color: var(--green); }
        .story-label { font-size: 0.7rem; font-weight: 600; color: var(--text-muted); max-width: 72px; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .story-stats {
            font-size: 0.62rem; font-weight: 600; color: var(--text-muted);
            display: flex; align-items: center; justify-content: center; gap: 5px;
            max-width: 72px; line-height: 1.2;
        }
        .story-stats i.fa-heart { color: #f87171; font-size: 0.6rem; }
        .story-stats i.fa-comment { color: var(--green-mid); font-size: 0.6rem; }

        /* ── STORY VIEWER (carte type téléphone, image cover = pas de bandes noires) ── */
        .story-viewer-overlay {
            display: none; position: fixed; inset: 0; z-index: 3000;
            background: rgba(0,0,0,0.88); align-items: center; justify-content: center;
        }
        .story-viewer-overlay.show { display: flex; animation: fadeIn 0.2s ease; }
        .story-phone {
            width: 340px; max-width: 92vw;
            height: min(640px, 90vh);
            max-height: 90vh;
            border-radius: 28px; overflow: hidden;
            background: #000; position: relative;
            box-shadow: 0 30px 80px rgba(0,0,0,0.6);
            display: flex; flex-direction: column;
        }
        .story-phone-header {
            position: absolute; top: 0; left: 0; right: 0; z-index: 15;
            padding: max(16px, env(safe-area-inset-top, 0px)) 16px 10px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.55), transparent);
        }
        .story-progress-bar {
            height: 3px; background: rgba(255,255,255,0.35); border-radius: 2px; margin-bottom: 12px;
        }
        .story-progress-fill {
            height: 100%; background: #fff; border-radius: 2px;
            animation: storyProgress 5s linear forwards;
        }
        @keyframes storyProgress { from { width: 0; } to { width: 100%; } }
        .story-phone-user { display: flex; align-items: center; gap: 10px; }
        .story-user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), #6dbf9e);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.6);
            overflow: hidden;
        }
        .story-user-avatar .user-avatar-img {
            width: 100%; height: 100%; object-fit: cover; border: none;
        }
        .story-comment-head {
            display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
        }
        .story-comment-head .user-avatar-img,
        .story-comment-head .avatar-circle {
            width: 26px; height: 26px; font-size: 0.62rem; flex-shrink: 0;
        }
        .story-comment-author-name {
            color: rgba(255,255,255,0.95); font-weight: 700; font-size: 0.78rem;
        }
        .story-user-name { color: #fff; font-weight: 700; font-size: 0.88rem; }
        .story-user-time { color: rgba(255,255,255,0.7); font-size: 0.72rem; }
        .story-close-btn {
            position: absolute; top: 14px; right: 14px; z-index: 20;
            background: rgba(0,0,0,0.4); border: none; color: #fff;
            width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.9rem;
            transition: background 0.2s;
        }
        .story-close-btn:hover { background: rgba(0,0,0,0.7); }
        /* Zone média remplit la carte : paysage → zoom (cover), pas de fond noir visible */
        .story-img-wrap {
            flex: 1;
            position: relative;
            z-index: 0;
            min-height: 0;
            width: 100%;
            background: #000;
            overflow: hidden;
        }
        .story-img-wrap img {
            position: absolute;
            left: 0; top: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .story-phone-footer {
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 15;
            padding: 12px 16px max(20px, env(safe-area-inset-bottom, 0px));
            background: linear-gradient(to top, rgba(0,0,0,0.82), transparent);
            display: flex; flex-direction: column; gap: 10px;
        }
        .story-footer-row {
            display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
        }
        .story-engage-btns {
            display: flex; align-items: center; gap: 14px;
        }
        .story-engage-btn {
            background: none; border: none; color: #fff; font-size: 0.82rem; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            padding: 6px 10px; border-radius: 20px; transition: background 0.2s;
        }
        .story-engage-btn:hover { background: rgba(255,255,255,0.12); }
        .story-engage-btn.liked i { color: #fb7185; }
        .story-engage-btn i { font-size: 1rem; }
        .story-comment-form {
            display: flex; gap: 8px; align-items: center; width: 100%;
        }
        .story-comment-form input {
            flex: 1; border: none; border-radius: 20px; padding: 8px 14px;
            font-size: 0.82rem; background: rgba(255,255,255,0.92); color: #111;
        }
        .story-comment-form input:focus { outline: 2px solid var(--green); }
        .story-comment-form button {
            border: none; background: var(--green); color: #fff; border-radius: 20px;
            padding: 8px 14px; font-size: 0.8rem; font-weight: 600; cursor: pointer;
        }
        .story-comments-scroll {
            max-height: 100px; overflow-y: auto; font-size: 0.78rem; color: rgba(255,255,255,0.92);
            display: flex; flex-direction: column; gap: 6px; padding-right: 4px;
        }
        .story-comments-scroll::-webkit-scrollbar { width: 3px; }
        .story-comments-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.35); border-radius: 3px; }
        .story-comment-row { margin-bottom: 8px; }
        .story-comment-line { line-height: 1.35; display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px; }
        .story-comment-line time { color: rgba(255,255,255,0.55); font-size: 0.72rem; }
        .story-comment-text { flex: 1; min-width: 0; word-break: break-word; }
        .story-comment-translate-wrap { position: relative; margin-top: 4px; }
        .story-translate-toggle {
            background: none; border: none; color: rgba(255,255,255,0.85);
            font-size: 0.72rem; font-weight: 600; cursor: pointer; padding: 2px 0;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .story-translate-toggle:hover { color: #fff; text-decoration: underline; }
        .story-comments-scroll .translate-menu {
            left: 0; right: auto; background: rgba(30,30,30,0.98);
            border-color: rgba(255,255,255,0.15); box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        .story-comments-scroll .translate-menu button { color: #f3f4f6; }
        .story-comments-scroll .translate-menu button:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .story-comments-scroll .translate-menu button:last-child { color: rgba(255,255,255,0.65); }
        .story-delete-btn {
            background: rgba(239,68,68,0.85); border: none; color: #fff;
            padding: 7px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            transition: background 0.2s;
        }
        .story-delete-btn:hover { background: #ef4444; }
        
        .ai-btn {
            border: 2px solid #43a047;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 34px rgba(19, 30, 23, 0.25);
            color: #1d4a2f;
            padding: 8px 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .ai-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 36px rgba(19, 30, 23, 0.3);
        }
        .ai-btn-label {
            background: linear-gradient(90deg, #e53935 0%, #fb8c00 52%, #43a047 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .ai-btn-icon {
            width: 20px;
            height: 20px;
            object-fit: contain;
            display: block;
        }

        .pdf-btn { display: inline-flex; align-items: center; gap: 8px; color: #ef4444; background: #fef2f2; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid #fecaca; transition: all 0.2s; cursor: pointer; margin-bottom: 15px; }
        .pdf-btn:hover { background: #fee2e2; color: #dc2626; }

        .translate-dropdown { font-size: 0.75rem; border: 1px solid var(--border); border-radius: 6px; padding: 3px 6px; background: #fff; color: var(--text-muted); cursor: pointer; outline: none; transition: all 0.2s; }
        .translate-dropdown:hover { border-color: var(--green); color: var(--green); }

        /* ── TRANSLATE BUTTON DROPDOWN ── */
        .translate-btn-wrap { position: relative; display: inline-block; }
        .translate-toggle { color: #0ea5e9 !important; }
        .translate-toggle:hover { background: #f0f9ff !important; color: #0284c7 !important; }
        .translate-menu {
            position: absolute; top: calc(100% + 4px); left: 0; z-index: 500;
            background: #fff; border: 1px solid var(--border);
            border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            min-width: 160px; padding: 6px; overflow: hidden;
        }
        .translate-menu button {
            display: block; width: 100%; text-align: left;
            background: none; border: none; padding: 8px 12px;
            font-size: 0.82rem; font-weight: 500; color: var(--text);
            cursor: pointer; border-radius: 7px; transition: background 0.15s;
            white-space: nowrap;
        }
        .translate-menu button:hover { background: var(--green-light); color: var(--green); }
        .translate-menu button:last-child { border-top: 1px solid var(--border); margin-top: 4px; color: var(--text-muted); }

        /* ── CHALLENGE FLOAT (fixed top-left) ── */
        .community-challenge-float {
            position: fixed;
            top: 92px;
            left: 16px;
            z-index: 1100;
            width: min(390px, calc(100vw - 32px));
            aspect-ratio: 5 / 4;
            height: auto;
            border: none;
            border-radius: 22px 18px 24px 16px;
            overflow: hidden;
            box-shadow: none;
        }
        .community-challenge-float__bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: inherit;
        }
        .community-challenge-float__overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
            gap: 14px;
            padding: 20px 24px;
            background: transparent;
        }
        .community-challenge-float__title {
            margin: 0;
            max-width: 54%;
            font-size: 1.22rem;
            font-weight: 700;
            line-height: 1.3;
            color: #FFD54F;
            text-align: right;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.55), 0 0 18px rgba(255, 193, 7, 0.35);
        }
        .community-challenge-float__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 26px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--green), var(--green-mid));
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease;
            font-family: inherit;
            box-shadow: none;
        }
        .community-challenge-float__btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.06);
            color: #fff;
        }
        .community-challenge-float__btn:active {
            transform: translateY(0);
        }
        @media (max-width: 640px) {
            .community-challenge-float {
                top: 88px;
                left: 10px;
                width: min(390px, calc(100vw - 20px));
                border-radius: 18px 14px 20px 12px;
            }
            .community-challenge-float__overlay {
                padding: 14px 16px;
                gap: 10px;
            }
            .community-challenge-float__title {
                max-width: 58%;
                font-size: 1rem;
            }
        }

        :root {
    --hb-forest: #2C7E34;
    --hb-forest-mid: #256b2d;
    --hb-mint: #2ec4b6;
    --hb-page-bg: #f4f7f5;
    --hb-card-border: #e3ebe6;
    --hb-green-accent: #2C7E34;
}

        .main-nav .nav-link:hover {
            color: var(--hb-forest-mid);
            border-bottom-color: rgba(37, 107, 45, 0.4);
        }

        .main-nav .nav-link.nav-link-active:hover {
            color: var(--hb-forest-mid);
            border-bottom-color: var(--hb-forest);
        }

        .main-nav {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    box-sizing: border-box;
    min-height: 78px;
    padding: 8px 120px 8px 16px;
    background: #fff;
    border-bottom: 1px solid var(--hb-card-border);
}

.nav-brand {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    text-decoration: none;
    color: var(--hb-forest);
}

.nav-brand-logo {
    display: block;
    width: auto;
    height: 75px;
    object-fit: contain;
    border-radius: 14px;
}

.nav-links-wrap {
    flex: 1;
    display: flex;
    justify-content: center;
    min-width: 0;
}

.nav-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 8px 22px;
}

.nav-link {
    color: var(--hb-forest);
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    padding: 8px 2px 10px;
    border-bottom: 3px solid transparent;
    transition: color 0.2s, border-color 0.2s;
}

.nav-link.nav-link-active {
    border-bottom-color: var(--hb-forest);
}

.nav-icons {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 20;
    display: flex;
    align-items: center;
    gap: 12px;
}

.nav-cart-link {
    display: block;
    line-height: 0;
    flex-shrink: 0;
}

/* Icon + (hidden) label pattern for the right-side nav icons */
.nav-icon-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 4px 8px;
    border-bottom: 3px solid transparent;
    text-decoration: none;
    color: inherit;
    transition: opacity 0.2s, border-color 0.2s;
}

.nav-icon-label {
    line-height: 1;
    font-weight: 700;
    font-size: 14px;
    color: var(--hb-forest);
    white-space: nowrap;
    max-width: 0;
    opacity: 0;
    overflow: hidden;
    transform: translateX(-4px);
    transition: max-width 0.2s ease, opacity 0.2s ease, transform 0.2s ease;
}

.nav-icon-link:hover .nav-icon-label,
.nav-icon-link.nav-icon-active .nav-icon-label,
.nav-profile-dropdown.nav-icon-active .nav-icon-label,
.nav-profile-dropdown[open] .nav-icon-label {
    max-width: 120px;
    opacity: 1;
    transform: translateX(0);
}

.nav-icon-link.nav-icon-active,
.nav-profile-dropdown.nav-icon-active > .nav-profile-trigger,
.nav-profile-dropdown[open] > .nav-profile-trigger {
    border-bottom-color: var(--hb-forest);
}

.nav-cart-link:hover {
    opacity: 0.9;
}

.nav-cart-img {
    display: block;
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.nav-profile-dropdown {
    position: relative;
    flex-shrink: 0;
}

.nav-profile-trigger {
    list-style: none;
    cursor: pointer;
    padding: 0;
    margin: 0;
    line-height: 0;
}

.nav-profile-trigger::-webkit-details-marker {
    display: none;
}

.nav-profile-img {
    display: block;
    width: 40px;
    height: 40px;
    object-fit: cover;
}

@media (max-width: 600px) {
    .nav-brand-logo {
        width: auto;
        height: 84px;
    }

    .main-nav {
        min-height: 68px;
    }

    .nav-link {
        font-size: 13px;
    }
}

.nav-profile-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 160px;
    padding: 8px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.nav-profile-btn {
    display: block;
    text-align: center;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    padding: 10px 14px;
    border-radius: 6px;
    box-sizing: border-box;
}

.nav-profile-signup {
    background-color: var(--hb-forest);
    color: #fff;
}

.nav-profile-signup:hover {
    filter: brightness(1.05);
}

.nav-profile-login {
    background-color: #fff;
    color: var(--hb-forest);
    border: 2px solid var(--hb-forest);
}

.nav-profile-login:hover {
    background-color: #eef5f1;
}

.nav-profile-logout {
    background-color: #b91c1c;
    color: #fff;
    border: 2px solid #991b1b;
}

.nav-profile-logout:hover {
    background-color: #991b1b;
    filter: brightness(1.05);
}

    </style>
</head>
<body>

<?php
$nav_active = 'communaute';
require __DIR__ . '/includes/nav_front.php';
?>

<aside class="community-challenge-float" aria-label="<?php echo fo_e('community.challenge_promo'); ?>">
    <img class="community-challenge-float__bg" src="images/challenge.png" alt="<?php echo fo_e('community.challenge_image_alt'); ?>" width="390" height="312" loading="lazy">
    <div class="community-challenge-float__overlay">
        <p class="community-challenge-float__title"><?php echo fo_e('community.challenge_promo'); ?></p>
        <a href="challenge_du_jour.php" class="community-challenge-float__btn"><?php echo fo_e('community.challenge_try'); ?></a>
    </div>
</aside>

<!-- MAIN LAYOUT -->
<div class="page-layout">
    <!-- FEED COLUMN -->
    <div class="feed-col" id="pdf-export-area">
        
        <!-- STORIES -->
        <div class="stories-wrapper">
            <h6><i class="fas fa-circle-notch me-2"></i><?php echo fo_e('community.stories'); ?></h6>
            <div class="stories-container">
                <!-- Add story -->
                <div class="story-item" role="button" tabindex="0"
                     onclick="<?php echo $commLoggedIn ? 'openStoryUploadModal()' : "location.href='auth/login.php'"; ?>">
                    <div class="story-ring add-ring">
                        <div class="story-ring-inner">
                            <span class="add-icon"><i class="fas fa-plus"></i></span>
                        </div>
                    </div>
                    <span class="story-label"><?php echo fo_e('community.add'); ?></span>
                </div>
                <?php if (!empty($stories)): foreach ($stories as $s):
                    $sid = (int) $s['id'];
                    $sLikes = $storyController->countLikes($sid);
                    $sComments = $storyController->countComments($sid);
                    $sLiked = $storyController->hasLiked($sid, $storyVisitorKey);
                    $saPrenom = (string) ($s['auteur_prenom'] ?? '');
                    $saNom = (string) ($s['auteur_nom'] ?? '');
                    $saLabel = communaute_display_nom_prenom($saNom, $saPrenom);
                    $saPhotoUrl = communaute_profile_public_url(isset($s['auteur_photo']) ? (string) $s['auteur_photo'] : null);
                    $saInitials = htmlspecialchars(communaute_initials($saPrenom, $saNom), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="story-item" role="button" tabindex="0"
                     data-story-id="<?php echo $sid; ?>"
                     data-story-image="<?php echo htmlspecialchars(communaute_media_url($s['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                     data-story-time="<?php echo htmlspecialchars(temps_ecoule_fr($s['dateCreation']), ENT_QUOTES, 'UTF-8'); ?>"
                     data-story-likes="<?php echo $sLikes; ?>"
                     data-story-comments="<?php echo $sComments; ?>"
                     data-story-liked="<?php echo $sLiked ? '1' : '0'; ?>"
                     data-author-name="<?php echo htmlspecialchars($saLabel, ENT_QUOTES, 'UTF-8'); ?>"
                     data-author-photo="<?php echo htmlspecialchars((string) ($saPhotoUrl ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                     data-author-initials="<?php echo $saInitials; ?>"
                     onclick="storyItemClick(this)">
                    <div class="story-ring">
                        <div class="story-ring-inner">
                            <img src="<?php echo htmlspecialchars(communaute_media_url($s['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="Story">
                        </div>
                    </div>
                    <span class="story-label"><?php echo htmlspecialchars($saLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="story-stats" title="<?php echo fo_e('community.likes_comments'); ?>">
                        <span><i class="fas fa-heart" aria-hidden="true"></i> <?php echo $sLikes; ?></span>
                        <span><i class="fas fa-comment" aria-hidden="true"></i> <?php echo $sComments; ?></span>
                    </span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- CREATE POST -->
        <?php if ($commLoggedIn): ?>
        <div class="create-post-card">
            <div class="create-post-header">
                <?php echo communaute_avatar_html($commPhotoRel, $commPrenom, $commNom, ''); ?>
                <div>
                    <div style="font-weight:600;color:var(--text);font-size:0.95rem;"><?php echo htmlspecialchars($commComposerDisplayName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <span><?php echo fo_e('community.whats_new'); ?></span>
                </div>
            </div>
            <form id="addPostForm" method="POST" enctype="multipart/form-data" onsubmit="return validateAddPost()">
                <input type="hidden" name="action" value="add_post">
                <textarea id="postContent" name="contenu" class="post-textarea" placeholder="<?php echo fo_e('community.post_placeholder'); ?>" rows="3"></textarea>
                <div id="contentError" class="error-message"></div>
                <div id="imagePreviewWrap" class="image-preview-wrap" style="display:none;">
                    <img id="imagePreview" src="" alt="preview">
                    <button type="button" class="remove-preview" onclick="removeImagePreview()"><i class="fas fa-times"></i></button>
                </div>
                <div class="post-toolbar">
                    <div style="display:flex;gap:10px;align-items:center;">
                        <label for="postImage" class="file-label">
                            <?php echo fo_e('community.add_photo'); ?>
                        </label>
                        <input type="file" id="postImage" name="image" accept="image/*" onchange="previewImage(this)">
                        <button type="button" class="ai-btn" onclick="generateWithAI(this)">
                            <img src="images/image.png" alt="" class="ai-btn-icon">
                            <span class="ai-btn-label"><?php echo fo_e('community.generate_image'); ?></span>
                        </button>
                    </div>
                    <button type="submit" class="btn-publish"><?php echo fo_e('community.publish'); ?></button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="create-post-card" style="text-align:center;padding:28px;">
            <p style="color:var(--text-muted);margin-bottom:12px;"><?php echo fo_e('community.login_to_post'); ?></p>
            <a href="auth/login.php" class="btn-publish" style="display:inline-flex;text-decoration:none;"><?php echo fo_e('nav.login'); ?></a>
        </div>
        <?php endif; ?>

        <!-- POSTS FEED -->

        <?php if (empty($posts)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-seedling"></i></div>
            <h5><?php echo fo_e('community.empty_title'); ?></h5>
            <p><?php echo fo_e('community.empty_desc'); ?></p>
        </div>
        <?php else: ?>
            <?php foreach ($posts as $post):
                $paPrenom = (string) ($post['auteur_prenom'] ?? '');
                $paNom = (string) ($post['auteur_nom'] ?? '');
                $paPhoto = isset($post['auteur_photo']) ? (string) $post['auteur_photo'] : null;
                $paName = communaute_display_nom_prenom($paNom, $paPrenom);
                $isPostOwner = $commLoggedIn && $commUserId > 0 && (int) ($post['id_utilisateur'] ?? 0) === $commUserId;
                $postUserRole = '';
                if (!empty($post['id_utilisateur'])) {
                    static $communauteRoleCache = [];
                    $puid = (int) $post['id_utilisateur'];
                    if (!isset($communauteRoleCache[$puid])) {
                        try {
                            $__pdo = Database::getConnection();
                            $__pk = communaute_utilisateur_pk($__pdo);
                            $__rs = $__pdo->prepare("SELECT role FROM utilisateur WHERE `{$__pk}` = :id LIMIT 1");
                            $__rs->execute(['id' => $puid]);
                            $communauteRoleCache[$puid] = (string) ($__rs->fetchColumn() ?: '');
                        } catch (Throwable $e) {
                            $communauteRoleCache[$puid] = '';
                        }
                    }
                    $postUserRole = $communauteRoleCache[$puid];
                }
                $isNutriPost = ($postUserRole === 'nutritionniste');
            ?>
            <div class="post-card<?php echo $isNutriPost ? ' post-nutritionniste' : ''; ?>" id="post-card-<?php echo $post['id']; ?>">
                <div class="post-header">
                    <div class="post-author-info">
                        <?php echo communaute_avatar_html($paPhoto, $paPrenom, $paNom, ''); ?>
                        <div>
                            <div class="post-author-name">
                                <?php echo htmlspecialchars($paName, ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($isNutriPost) { ?>
                                    <span class="badge-nutritionniste"><i class="fas fa-leaf" aria-hidden="true"></i> <?php echo fo_e('community.nutritionist_verified'); ?></span>
                                <?php } ?>
                            </div>
                            <div class="post-date"><i class="fas fa-clock me-1"></i><?php echo date('d M Y a H:i', strtotime($post['datePublication'])); ?></div>
                        </div>
                    </div>
                    <?php if ($isPostOwner): ?>
                    <div class="dropdown">
                        <button class="menu-dots" type="button" id="dropdownMenu<?php echo $post['id']; ?>" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenu<?php echo $post['id']; ?>">
                            <li><button type="button" class="dropdown-item" onclick="openEditPostModal(<?php echo $post['id']; ?>)"><i class="fas fa-edit text-primary"></i> <?php echo fo_e('common.edit'); ?></button></li>
                            <li><button type="button" class="dropdown-item text-danger" onclick="openDeleteConfirmation(<?php echo $post['id']; ?>)"><i class="fas fa-trash"></i> <?php echo fo_e('common.delete'); ?></button></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="post-body">
                    <p id="post-text-<?php echo $post['id']; ?>"><?php echo nl2br(htmlspecialchars($post['contenu'])); ?></p>
                    <?php if (!empty($post['image'])): ?>
                    <img src="<?php echo htmlspecialchars(communaute_media_url($post['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="Post image" class="post-image">
                    <?php endif; ?>
                </div>
                <div class="post-actions">
                    <button class="post-action-btn like-btn" id="like-btn-<?php echo $post['id']; ?>" onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                        <i class="fas fa-heart"></i>
                        <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['nombreLikes']; ?> J aime</span>
                    </button>
                    <button class="post-action-btn" onclick="toggleCommentsSection(<?php echo $post['id']; ?>)">
                        <i class="fas fa-comment-dots"></i>
                        <span>Commenter</span>
                    </button>
                </div>

                <!-- COMMENTS -->
                <div id="comments-section-<?php echo $post['id']; ?>" class="comments-section hidden">
                    <?php $comments = $commentaireController->getByPostId($post['id']); ?>
                    <?php if (empty($comments)): ?>
                        <p style="text-align:center;color:var(--text-muted);font-size:0.85rem;padding:10px 0;"><?php echo fo_e('community.no_comments'); ?></p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment):
                            $caPrenom = (string) ($comment['auteur_prenom'] ?? '');
                            $caNom = (string) ($comment['auteur_nom'] ?? '');
                            $caPhoto = isset($comment['auteur_photo']) ? (string) $comment['auteur_photo'] : null;
                            $caName = communaute_display_nom_prenom($caNom, $caPrenom);
                            $isCommentOwner = $commLoggedIn && $commUserId > 0 && (int) ($comment['id_utilisateur'] ?? 0) === $commUserId;
                        ?>
                        <div class="comment-item" id="comment-item-<?php echo $comment['id']; ?>">
                            <?php echo communaute_comment_avatar_html($caPhoto, $caPrenom, $caNom); ?>
                            <div class="comment-bubble">
                                <div class="comment-meta">
                                    <span class="comment-author"><?php echo htmlspecialchars($caName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="comment-date"><?php echo date('d M Y a H:i', strtotime($comment['dateCommentaire'])); ?></span>
                                </div>
                                <p class="comment-text" id="comment-content-<?php echo $comment['id']; ?>"><?php echo htmlspecialchars($comment['contenu']); ?></p>
                                <div class="comment-actions-row" style="align-items:center; flex-wrap:wrap; position:relative;">
                                    <?php if ($isCommentOwner): ?>
                                    <button class="comment-action-link" onclick='openEditCommentModal(<?php echo $comment['id']; ?>, "<?php echo addslashes($comment['contenu']); ?>")'>
                                        <i class="fas fa-edit"></i> <?php echo fo_e('common.edit'); ?>
                                    </button>
                                    <button class="comment-action-link danger" onclick="openDeleteCommentConfirmation(<?php echo $comment['id']; ?>)">
                                        <i class="fas fa-trash"></i> <?php echo fo_e('common.delete'); ?>
                                    </button>
                                    <?php endif; ?>
                                    <!-- Translate button with dropdown -->
                                    <div class="translate-btn-wrap" id="translate-wrap-<?php echo $comment['id']; ?>">
                                        <button type="button" class="comment-action-link translate-toggle"
                                                onclick="toggleTranslateMenu(<?php echo $comment['id']; ?>, event)">
                                            <i class="fas fa-language"></i> Traduire
                                            <i class="fas fa-chevron-down" style="font-size:0.6rem;margin-left:2px;"></i>
                                        </button>
                                        <div class="translate-menu" id="translate-menu-<?php echo $comment['id']; ?>" style="display:none;">
                                            <button type="button" onclick="translateComment(<?php echo $comment['id']; ?>, 'fr', this)" data-original="<?php echo htmlspecialchars(addslashes($comment['contenu'])); ?>">
                                                🇫🇷 Français
                                            </button>
                                            <button type="button" onclick="translateComment(<?php echo $comment['id']; ?>, 'en', this)" data-original="<?php echo htmlspecialchars(addslashes($comment['contenu'])); ?>">
                                                🇬🇧 English
                                            </button>
                                            <button type="button" onclick="restoreOriginalComment(<?php echo $comment['id']; ?>, this)" data-original="<?php echo htmlspecialchars(addslashes($comment['contenu'])); ?>">
                                                🔄 Original (texte enregistré)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($commLoggedIn): ?>
                    <div class="comment-form-row">
                        <?php echo communaute_comment_avatar_html($commPhotoRel, $commPrenom, $commNom); ?>
                        <form id="commentForm-<?php echo $post['id']; ?>" style="flex:1;" method="POST" onsubmit="return validateComment(<?php echo $post['id']; ?>)">
                            <input type="hidden" name="action" value="add_comment">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <div style="display:flex;gap:8px;align-items:flex-end;">
                                <textarea class="comment-textarea" name="contenu" id="commentContent-<?php echo $post['id']; ?>" placeholder="<?php echo fo_e('community.add_comment'); ?>" rows="1"></textarea>
                                <button type="submit" class="btn-comment"><i class="fas fa-paper-plane"></i></button>
                            </div>
                            <div id="commentError-<?php echo $post['id']; ?>" class="error-message"></div>
                        </form>
                    </div>
                    <?php else: ?>
                    <p style="text-align:center;color:var(--text-muted);font-size:0.82rem;margin-top:10px;">
                        <a href="auth/login.php"><?php echo fo_e('nav.login'); ?></a> — <?php echo fo_e('community.login_to_comment'); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <!-- SIDEBAR hidden — single column layout -->
    <div class="sidebar-col" style="display:none;">
        <div class="sidebar-widget">
            <h6><i class="fas fa-lightbulb me-2"></i>Conseils de la communaute</h6>
            <div class="sidebar-tip">
                <div class="tip-icon"><i class="fas fa-camera"></i></div>
                <div class="tip-text"><strong>Ajoutez des photos</strong>Les posts avec images recoivent 3x plus d engagement.</div>
            </div>
            <div class="sidebar-tip">
                <div class="tip-icon"><i class="fas fa-heart"></i></div>
                <div class="tip-text"><strong>Likez et commentez</strong>Encouragez les autres membres de la communaute.</div>
            </div>
            <div class="sidebar-tip">
                <div class="tip-icon"><i class="fas fa-utensils"></i></div>
                <div class="tip-text"><strong>Partagez vos recettes</strong>Inspirez la communaute avec vos creations culinaires.</div>
            </div>
        </div>
        <div class="sidebar-widget">
            <h6><i class="fas fa-fire me-2"></i>Tendances</h6>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <a href="List-Recette.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);padding:10px;border-radius:10px;transition:background 0.2s;" onmouseover="this.style.background='var(--green-light)'" onmouseout="this.style.background='transparent'">
                    <span style="width:32px;height:32px;border-radius:8px;background:var(--green-light);display:flex;align-items:center;justify-content:center;color:var(--green);font-size:0.9rem;"><i class="fas fa-book-open"></i></span>
                    <span style="font-size:0.88rem;font-weight:500;">Recettes populaires</span>
                </a>
                <a href="List-Produit.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);padding:10px;border-radius:10px;transition:background 0.2s;" onmouseover="this.style.background='var(--green-light)'" onmouseout="this.style.background='transparent'">
                    <span style="width:32px;height:32px;border-radius:8px;background:#fff3e0;display:flex;align-items:center;justify-content:center;color:#f0a500;font-size:0.9rem;"><i class="fas fa-shopping-bag"></i></span>
                    <span style="font-size:0.88rem;font-weight:500;">Nouveaux produits</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->
<div id="editPostModal" class="modal-custom">
    <div class="modal-custom-content">
        <div class="modal-icon">&#9998;</div>
        <h4><?php echo fo_e('community.edit_post'); ?></h4>
        <form id="editPostForm" method="POST" onsubmit="return validateEditPost()">
            <input type="hidden" name="action" value="update_post">
            <input type="hidden" id="postId" name="id" value="">
            <textarea id="editPostContent" name="contenu" class="form-control" rows="4"></textarea>
            <div id="editContentError" class="error-message mt-2"></div>
            <div class="modal-footer-btns">
                <button type="submit" class="btn btn-green">Enregistrer</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditPostModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteConfirmationModal" class="modal-custom">
    <div class="modal-custom-content">
        <div class="modal-icon">&#128465;</div>
        <h4><?php echo fo_e('community.delete_post'); ?></h4>
        <p>Cette action est irreversible. Le post sera definitivement supprime.</p>
        <div class="modal-footer-btns">
            <button type="button" class="btn btn-danger" onclick="confirmDelete()"><?php echo fo_e('common.delete'); ?></button>
            <button type="button" class="btn btn-secondary" onclick="closeDeleteConfirmation()">Annuler</button>
        </div>
    </div>
</div>

<div id="editCommentModal" class="modal-custom">
    <div class="modal-custom-content">
        <div class="modal-icon">&#128172;</div>
        <h4><?php echo fo_e('community.edit_comment'); ?></h4>
        <form id="editCommentForm" method="POST" onsubmit="return validateEditComment()">
            <input type="hidden" name="action" value="update_comment">
            <input type="hidden" id="commentId" name="id" value="">
            <textarea id="editCommentContent" name="contenu" class="form-control" rows="3"></textarea>
            <div id="editCommentError" class="error-message mt-2"></div>
            <div class="modal-footer-btns">
                <button type="submit" class="btn btn-green">Enregistrer</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditCommentModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteCommentConfirmationModal" class="modal-custom">
    <div class="modal-custom-content">
        <div class="modal-icon">&#128465;</div>
        <h4><?php echo fo_e('community.delete_comment'); ?></h4>
        <p>Cette action est irreversible.</p>
        <div class="modal-footer-btns">
            <button type="button" class="btn btn-danger" onclick="confirmDeleteComment()"><?php echo fo_e('common.delete'); ?></button>
            <button type="button" class="btn btn-secondary" onclick="closeDeleteCommentConfirmation()">Annuler</button>
        </div>
    </div>
</div>

<div id="uploadStoryModal" class="modal-custom">
    <div class="modal-custom-content">
        <div class="modal-icon" style="color:var(--green);">&#43;</div>
        <h4><?php echo fo_e('community.add_story'); ?></h4>
        <?php
        // PHP server-side story validation errors
        $storyError = '';
        if (isset($_POST['action']) && $_POST['action'] === 'add_story_validate') {
            if (empty($_FILES['story_image']['name'])) {
                $storyError = 'Veuillez sélectionner une image.';
            } elseif ($_FILES['story_image']['size'] > 5 * 1024 * 1024) {
                $storyError = 'L\'image ne doit pas dépasser 5 Mo.';
            } else {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['story_image']['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mimeType, $allowedTypes)) {
                    $storyError = 'Format non supporté. Utilisez JPG, PNG, GIF ou WebP.';
                }
            }
        }
        ?>
        <form id="addStoryForm" method="POST" enctype="multipart/form-data" onsubmit="return validateStoryPHP(this)">
            <input type="hidden" name="action" value="add_story">
            <div style="margin-bottom:15px;">
                <label class="file-label w-100 justify-content-center" for="storyImageInput" style="border-radius:10px;padding:20px;">
                    <i class="fas fa-image fa-lg"></i>
                    <span id="storyFileName">Choisir une image (JPG, PNG, GIF, WebP — max 5 Mo)</span>
                </label>
                <input type="file" id="storyImageInput" name="image" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;"
                       onchange="updateStoryFileName(this)">
            </div>
            <div id="storyPreviewWrap" style="display:none;text-align:center;margin-bottom:12px;">
                <img id="storyPreviewImg" src="" style="max-height:160px;border-radius:10px;border:2px solid var(--border);">
            </div>
            <div id="storyFormError" class="error-message mb-2"></div>
            <div class="modal-footer-btns">
                <button type="submit" class="btn btn-green"><?php echo fo_e('community.publish_story'); ?></button>
                <button type="button" class="btn btn-secondary" onclick="closeStoryUploadModal()"><?php echo fo_e('common.cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Story Viewer — Instagram style -->
<div id="storyViewerOverlay" class="story-viewer-overlay" onclick="closeViewStoryModal()">
    <div class="story-phone" onclick="event.stopPropagation()">
        <!-- Progress bar -->
        <div class="story-phone-header">
            <div class="story-progress-bar">
                <div class="story-progress-fill" id="storyProgressFill"></div>
            </div>
            <div class="story-phone-user">
                <div id="storyViewerAvatarSlot" class="story-user-avatar"></div>
                <div>
                    <div class="story-user-name" id="storyViewerUserName">—</div>
                    <div class="story-user-time" id="storyTime"></div>
                </div>
            </div>
        </div>
        <!-- Close -->
        <button class="story-close-btn" onclick="closeViewStoryModal()"><i class="fas fa-times"></i></button>
        <!-- Image -->
        <div class="story-img-wrap">
            <img id="storyViewerImg" src="" alt="Story">
        </div>
        <!-- Footer: comments, j'aime, delete -->
        <div class="story-phone-footer">
            <div id="storyCommentsList" class="story-comments-scroll" style="display:none;"></div>
            <div class="story-footer-row">
                <div class="story-engage-btns">
                    <button type="button" class="story-engage-btn" id="storyLikeBtn" onclick="toggleStoryLike(event)" title="<?php echo fo_e('community.like'); ?>">
                        <i class="fas fa-heart"></i> <span id="storyLikeCount">0</span> <span style="font-weight:500;opacity:0.9">J'aime</span>
                    </button>
                    <div class="story-engage-btn" style="cursor:default;pointer-events:none;opacity:0.95;" title="Commentaires">
                        <i class="fas fa-comment"></i> <span id="storyCommentCountFoot">0</span>
                    </div>
                </div>
                <button type="button" class="story-delete-btn" id="deleteStoryBtn">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
            <?php if ($commLoggedIn): ?>
            <form class="story-comment-form" id="storyCommentForm" onsubmit="return submitStoryComment(event)">
                <input type="text" name="story_comment" id="storyCommentInput" placeholder="<?php echo fo_e('community.add_comment'); ?>" maxlength="2000" autocomplete="off">
                <button type="submit" aria-label="Envoyer"><i class="fas fa-paper-plane"></i></button>
            </form>
            <?php else: ?>
            <p style="text-align:center;color:rgba(255,255,255,0.75);font-size:0.8rem;padding:8px 0 0;">
                <a href="auth/login.php" style="color:#fff;font-weight:600;"><?php echo fo_e('nav.login'); ?></a> — <?php echo fo_e('community.login_comment_stories'); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- html2pdf for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const COMMUNITY_PAGE = <?php echo json_encode(basename($_SERVER['SCRIPT_NAME'] ?? 'Communaute.php'), JSON_UNESCAPED_UNICODE); ?>;
const COMMUNITY_LOGGED_IN = <?php echo $commLoggedIn ? 'true' : 'false'; ?>;
const COMMUNITY_ME = <?php echo json_encode([
    'prenom' => $commPrenom,
    'nom' => $commNom,
    'photoUrl' => $commPhotoUrl,
    'displayName' => $commComposerDisplayName,
], JSON_UNESCAPED_UNICODE); ?>;
const COMMUNITY_TOAST = <?php echo json_encode([
    'postUpdated' => fo_t('community.flash_post_updated'),
    'postDeleted' => fo_t('community.flash_post_deleted'),
    'commentAdded' => fo_t('community.flash_comment_added'),
    'commentUpdated' => fo_t('community.flash_comment_updated'),
    'commentDeleted' => fo_t('community.flash_comment_deleted'),
    'storyDeleted' => fo_t('community.flash_story_deleted'),
    'error' => fo_t('toast.network_error'),
], JSON_UNESCAPED_UNICODE); ?>;

function commActionToast(text) {
    if (typeof window.hbShowActionToast === 'function' && text) {
        window.hbShowActionToast(text, 3500);
    }
}

function communityInitials(prenom, nom) {
    const p = (prenom || '').trim();
    const n = (nom || '').trim();
    const a = (p.charAt(0) + n.charAt(0)).toUpperCase();
    return a || 'M';
}

function communityCommentAvatarFromUser(photoUrl, prenom, nom) {
    const url = photoUrl || '';
    if (url) {
        return '<img class="user-avatar-img comment-avatar" src="' + escapeAttr(url) + '" alt="">';
    }
    return '<div class="comment-avatar">' + escapeHtml(communityInitials(prenom, nom)) + '</div>';
}

function communityStoryViewerAvatarInner(photoUrl, initials) {
    if (photoUrl) {
        return '<img class="user-avatar-img" src="' + escapeAttr(photoUrl) + '" alt="">';
    }
    return escapeHtml(initials || 'M');
}

function communityStoryCommentAvatarSmall(photoUrl, prenom, nom) {
    if (photoUrl) {
        return '<img class="user-avatar-img" src="' + escapeAttr(photoUrl) + '" alt="">';
    }
    const ini = communityInitials(prenom, nom);
    return '<div class="avatar-circle" style="width:26px;height:26px;font-size:0.62rem;">' + escapeHtml(ini) + '</div>';
}

let deletePostId = null;
let deleteCommentId = null;

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewWrap').style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function removeImagePreview() {
    document.getElementById('postImage').value = '';
    document.getElementById('imagePreviewWrap').style.display = 'none';
    document.getElementById('imagePreview').src = '';
}

function validateAddPost() {
    const err = document.getElementById('contentError');
    err.textContent = '';
    if (!COMMUNITY_LOGGED_IN) {
        err.textContent = <?php echo json_encode(fo_t('community.login_publish_js'), JSON_UNESCAPED_UNICODE); ?>;
        return false;
    }
    const content = document.getElementById('postContent').value.trim();
    if (!content) { err.textContent = 'Le contenu du post est obligatoire.'; return false; }
    return true;
}

function validateEditPost() {
    const content = document.getElementById('editPostContent').value.trim();
    const err = document.getElementById('editContentError');
    const postId = document.getElementById('postId').value;
    err.textContent = '';
    if (!content) { err.textContent = 'Le contenu du post est obligatoire.'; return false; }
    fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_post&ajax=1&id=' + encodeURIComponent(postId) + '&contenu=' + encodeURIComponent(content)
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const el = document.getElementById('post-text-' + data.id);
            if (el) el.innerHTML = content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
            closeEditPostModal();
            commActionToast(COMMUNITY_TOAST.postUpdated);
        } else err.textContent = 'Erreur lors de la mise a jour.';
    }).catch(() => err.textContent = 'Erreur lors de la mise a jour.');
    return false;
}

function openEditPostModal(postId) {
    const el = document.getElementById('post-text-' + postId);
    document.getElementById('postId').value = postId;
    document.getElementById('editPostContent').value = el.innerHTML.replace(/<br\s*\/?>/gi, '\n');
    document.getElementById('editPostModal').classList.add('show');
}
function closeEditPostModal() { document.getElementById('editPostModal').classList.remove('show'); }

function openDeleteConfirmation(postId) { deletePostId = postId; document.getElementById('deleteConfirmationModal').classList.add('show'); }
function closeDeleteConfirmation() { deletePostId = null; document.getElementById('deleteConfirmationModal').classList.remove('show'); }

function confirmDelete() {
    if (deletePostId !== null) {
        fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete_post&ajax=1&id=' + encodeURIComponent(deletePostId)
        }).then(r => r.json()).then(data => {
            if (data.success) {
                const card = document.getElementById('post-card-' + data.id);
                if (card) { card.style.animation = 'fadeOut 0.3s ease forwards'; setTimeout(() => card.remove(), 300); }
                closeDeleteConfirmation();
                commActionToast(COMMUNITY_TOAST.postDeleted);
            }
        }).catch(() => closeDeleteConfirmation());
    }
}

function toggleLike(postId, button) {
    const isLiked = button.classList.contains('liked');
    fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=like_post&id=' + postId + '&liked=' + isLiked
    }).then(r => r.json()).then(data => {
        if (data.success) {
            button.classList.toggle('liked');
            document.getElementById('like-count-' + postId).textContent = data.likes + ' J aime';
        }
    });
}

function toggleCommentsSection(postId) {
    const section = document.getElementById('comments-section-' + postId);
    section.classList.toggle('hidden');
    if (!section.classList.contains('hidden')) {
        section.style.animation = 'slideDown 0.3s ease';
    }
}

function validateComment(postId) {
    const err = document.getElementById('commentError-' + postId);
    err.textContent = '';
    if (!COMMUNITY_LOGGED_IN) {
        err.textContent = <?php echo json_encode(fo_t('community.login_comment_js'), JSON_UNESCAPED_UNICODE); ?>;
        return false;
    }
    const el = document.getElementById('commentContent-' + postId);
    const content = el.value.trim();
    if (!content) { err.textContent = 'Le commentaire ne peut pas etre vide.'; return false; }
    const form = new URLSearchParams();
    form.append('action', 'add_comment'); form.append('ajax', '1');
    form.append('post_id', document.querySelector('#commentForm-' + postId + ' input[name="post_id"]').value);
    form.append('contenu', content);
    fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: form.toString() })
    .then(async r => {
        const raw = await r.text();
        let data;
        try { data = JSON.parse(raw); } catch (e) {
            err.textContent = "Réponse invalide du serveur. Vérifiez les tables SQL (Commentaire) et les erreurs PHP.";
            return;
        }
        if (data.success) {
            const section = document.getElementById('comments-section-' + postId);
            const placeholder = section.querySelector('p[style*="text-align:center"]');
            if (placeholder) placeholder.remove();
            const authorName = escapeHtml(((data.nom || '') + ' ' + (data.prenom || '')).trim() || 'Membre');
            const avHtml = communityCommentAvatarFromUser(data.photoUrl || '', data.prenom, data.nom);
            const div = document.createElement('div');
            div.className = 'comment-item'; div.id = 'comment-item-' + data.id;
            div.innerHTML = avHtml + '<div class="comment-bubble"><div class="comment-meta"><span class="comment-author">' + authorName + '</span><span class="comment-date">' + escapeHtml(data.dateCommentaire) + '</span></div><p class="comment-text" id="comment-content-' + data.id + '">' + escapeHtml(data.contenu) + '</p><div class="comment-actions-row" style="align-items:center;flex-wrap:wrap;position:relative;"><button class="comment-action-link" onclick="openEditCommentModal(' + data.id + ', \'' + escapeJsString(data.contenu) + '\')"><i class="fas fa-edit"></i> Modifier</button><button class="comment-action-link danger" onclick="openDeleteCommentConfirmation(' + data.id + ')"><i class="fas fa-trash"></i> Supprimer</button><div class="translate-btn-wrap" id="translate-wrap-' + data.id + '"><button type="button" class="comment-action-link translate-toggle" onclick="toggleTranslateMenu(' + data.id + ', event)"><i class="fas fa-language"></i> Traduire <i class="fas fa-chevron-down" style="font-size:0.6rem;margin-left:2px;"></i></button><div class="translate-menu" id="translate-menu-' + data.id + '" style="display:none;"><button type="button" onclick="translateComment(' + data.id + ', \'fr\', this)" data-original="' + escapeAttr(data.contenu) + '">🇫🇷 Français</button><button type="button" onclick="translateComment(' + data.id + ', \'en\', this)" data-original="' + escapeAttr(data.contenu) + '">🇬🇧 English</button><button type="button" onclick="restoreOriginalComment(' + data.id + ', this)" data-original="' + escapeAttr(data.contenu) + '">🔄 Original (texte enregistré)</button></div></div></div></div>';
            section.insertBefore(div, section.querySelector('.comment-form-row'));
            el.value = '';
            commActionToast(COMMUNITY_TOAST.commentAdded);
        } else {
            if (data.error === 'not_logged_in') {
                err.textContent = <?php echo json_encode(fo_t('community.login_comment_js'), JSON_UNESCAPED_UNICODE); ?>;
            } else {
                err.textContent = data.error === 'db' ? "Impossible d'enregistrer en base de données." : "Erreur lors de l'ajout.";
            }
        }
    }).catch(() => { err.textContent = "Erreur réseau ou serveur."; });
    return false;
}

function escapeHtml(t) { return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;').replace(/\n/g,'<br>'); }
function escapeAttr(t) { return String(t).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
function escapeJsString(t) { return t.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"').replace(/\n/g,'\\n'); }

function storyCommentRowHtml(c) {
    const id = c.id;
    const text = escapeHtml(c.contenu);
    const time = escapeHtml(c.dateCommentaire);
    const orig = escapeAttr(c.contenu);
    const authorLabel = escapeHtml(((c.nom || '') + ' ' + (c.prenom || '')).trim() || 'Membre');
    const headAv = communityStoryCommentAvatarSmall(c.photoUrl || '', c.prenom, c.nom);
    return '<div class="story-comment-row" id="story-comment-row-' + id + '">' +
        '<div class="story-comment-head">' + headAv + '<span class="story-comment-author-name">' + authorLabel + '</span></div>' +
        '<div class="story-comment-line">' +
        '<span class="story-comment-text" id="story-comment-text-' + id + '">' + text + '</span>' +
        '<time>' + time + '</time></div>' +
        '<div class="story-comment-translate-wrap">' +
        '<button type="button" class="story-translate-toggle" onclick="toggleStoryTranslateMenu(' + id + ', event)">' +
        '<i class="fas fa-language"></i> Traduire <i class="fas fa-chevron-down" style="font-size:0.55rem;"></i></button>' +
        '<div class="translate-menu" id="story-translate-menu-' + id + '" style="display:none;">' +
        '<button type="button" onclick="translateStoryComment(' + id + ', \'fr\', this)" data-original="' + orig + '">🇫🇷 Français</button>' +
        '<button type="button" onclick="translateStoryComment(' + id + ', \'en\', this)" data-original="' + orig + '">🇬🇧 English</button>' +
        '<button type="button" onclick="restoreStoryOriginalComment(' + id + ', this)" data-original="' + orig + '">🔄 Original</button>' +
        '</div></div></div>';
}

function openEditCommentModal(id, content) {
    document.getElementById('commentId').value = id;
    document.getElementById('editCommentContent').value = content;
    document.getElementById('editCommentModal').classList.add('show');
}
function closeEditCommentModal() { document.getElementById('editCommentModal').classList.remove('show'); }

function validateEditComment() {
    const content = document.getElementById('editCommentContent').value.trim();
    const err = document.getElementById('editCommentError');
    const id = document.getElementById('commentId').value;
    err.textContent = '';
    if (!content) { err.textContent = 'Le commentaire ne peut pas etre vide.'; return false; }
    fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_comment&ajax=1&id=' + encodeURIComponent(id) + '&contenu=' + encodeURIComponent(content)
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const el = document.getElementById('comment-content-' + data.id);
            if (el) el.textContent = content;
            closeEditCommentModal();
            commActionToast(COMMUNITY_TOAST.commentUpdated);
        } else err.textContent = 'Erreur lors de la mise a jour.';
    }).catch(() => err.textContent = 'Erreur lors de la mise a jour.');
    return false;
}

function openDeleteCommentConfirmation(id) { deleteCommentId = id; document.getElementById('deleteCommentConfirmationModal').classList.add('show'); }
function closeDeleteCommentConfirmation() { deleteCommentId = null; document.getElementById('deleteCommentConfirmationModal').classList.remove('show'); }

function confirmDeleteComment() {
    if (deleteCommentId !== null) {
        fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete_comment&ajax=1&id=' + encodeURIComponent(deleteCommentId)
        }).then(r => r.json()).then(data => {
            if (data.success) {
                const el = document.getElementById('comment-item-' + data.id);
                if (el) el.remove();
                closeDeleteCommentConfirmation();
                commActionToast(COMMUNITY_TOAST.commentDeleted);
            }
        }).catch(() => closeDeleteCommentConfirmation());
    }
}

window.onclick = function(e) {
    ['editPostModal','deleteConfirmationModal','editCommentModal','deleteCommentConfirmationModal','uploadStoryModal','viewStoryModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.classList.remove('show');
    });
};

function openStoryUploadModal() {
    if (!COMMUNITY_LOGGED_IN) {
        window.location.href = 'auth/login.php';
        return;
    }
    document.getElementById('uploadStoryModal').classList.add('show');
}
function closeStoryUploadModal() { document.getElementById('uploadStoryModal').classList.remove('show'); }

function updateStoryFileName(input) {
    const label = document.getElementById('storyFileName');
    const preview = document.getElementById('storyPreviewWrap');
    const previewImg = document.getElementById('storyPreviewImg');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
        const reader = new FileReader();
        reader.onload = e => { previewImg.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function validateStoryPHP(form) {
    const input = document.getElementById('storyImageInput');
    const errEl = document.getElementById('storyFormError');
    errEl.textContent = '';
    if (!input.files || !input.files[0]) {
        errEl.textContent = 'Veuillez sélectionner une image.';
        return false;
    }
    const file = input.files[0];
    const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!allowed.includes(file.type)) {
        errEl.textContent = 'Format non supporté. Utilisez JPG, PNG, GIF ou WebP.';
        return false;
    }
    if (file.size > 5 * 1024 * 1024) {
        errEl.textContent = "L'image ne doit pas dépasser 5 Mo.";
        return false;
    }
    return true;
}

let storyProgressTimer = null;
let currentStoryId = null;

function storyItemClick(el) {
    viewStory(
        parseInt(el.dataset.storyId, 10),
        el.dataset.storyImage || '',
        el.dataset.storyTime || '',
        parseInt(el.dataset.storyLikes || '0', 10),
        parseInt(el.dataset.storyComments || '0', 10),
        el.dataset.storyLiked === '1',
        el.dataset.authorName || 'Membre',
        el.dataset.authorPhoto || '',
        el.dataset.authorInitials || 'M'
    );
}

function syncStoryRingStats(likes, comments) {
    if (!currentStoryId) return;
    const ring = document.querySelector('.story-item[data-story-id="' + currentStoryId + '"]');
    if (!ring) return;
    ring.dataset.storyLikes = String(likes);
    ring.dataset.storyComments = String(comments);
    const stats = ring.querySelector('.story-stats');
    if (stats) {
        stats.innerHTML = '<span><i class="fas fa-heart" aria-hidden="true"></i> ' + likes + '</span><span><i class="fas fa-comment" aria-hidden="true"></i> ' + comments + '</span>';
    }
}

function loadStoryCommentsForViewer(storyId) {
    const form = new URLSearchParams();
    form.append('action', 'get_story_comments');
    form.append('story_id', String(storyId));
    fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: form.toString() })
    .then(r => r.json()).then(data => {
        const listEl = document.getElementById('storyCommentsList');
        if (!data.success || !data.comments || data.comments.length === 0) {
            listEl.style.display = 'none';
            listEl.innerHTML = '';
            return;
        }
        listEl.style.display = 'flex';
        listEl.innerHTML = data.comments.map(function(c) {
            return storyCommentRowHtml(c);
        }).join('');
    }).catch(function() {
        document.getElementById('storyCommentsList').style.display = 'none';
    });
}

function toggleStoryLike(ev) {
    ev.stopPropagation();
    if (!currentStoryId) return;
    const form = new URLSearchParams();
    form.append('action', 'story_toggle_like');
    form.append('story_id', String(currentStoryId));
    fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: form.toString() })
    .then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('storyLikeCount').textContent = String(data.count);
            document.getElementById('storyLikeBtn').classList.toggle('liked', !!data.liked);
            const ring = document.querySelector('.story-item[data-story-id="' + currentStoryId + '"]');
            if (ring) ring.dataset.storyLiked = data.liked ? '1' : '0';
            const cc = parseInt(document.getElementById('storyCommentCountFoot').textContent, 10) || 0;
            syncStoryRingStats(data.count, cc);
        }
    });
}

function submitStoryComment(ev) {
    ev.preventDefault();
    ev.stopPropagation();
    if (!currentStoryId) return false;
    const input = document.getElementById('storyCommentInput');
    const text = (input.value || '').trim();
    if (!text) return false;
    const form = new URLSearchParams();
    form.append('action', 'story_add_comment');
    form.append('story_id', String(currentStoryId));
    form.append('contenu', text);
    fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: form.toString() })
    .then(r => r.json()).then(data => {
        if (data.success) {
            input.value = '';
            document.getElementById('storyCommentCountFoot').textContent = String(data.commentCount);
            const listEl = document.getElementById('storyCommentsList');
            listEl.style.display = 'flex';
            listEl.insertAdjacentHTML('beforeend', storyCommentRowHtml({
                id: data.id,
                contenu: data.contenu,
                dateCommentaire: data.dateCommentaire,
                prenom: data.prenom,
                nom: data.nom,
                photoUrl: data.photoUrl
            }));
            const lk = parseInt(document.getElementById('storyLikeCount').textContent, 10) || 0;
            syncStoryRingStats(lk, data.commentCount);
        }
    });
    return false;
}

function communityMediaUrl(stored) {
    if (!stored) return '';
    if (/^https?:\/\//i.test(stored) || stored.indexOf('../') === 0) return stored;
    if (stored.indexOf('/') !== -1) return '../' + stored;
    return '../uploads/' + stored;
}

function viewStory(id, image, time, likes, comments, liked, authorName, authorPhotoUrl, authorInitials) {
    currentStoryId = id;
    document.getElementById('storyViewerImg').src = communityMediaUrl(image);
    document.getElementById('storyTime').textContent = time || '';
    document.getElementById('storyViewerUserName').textContent = authorName || 'Membre';
    const avSlot = document.getElementById('storyViewerAvatarSlot');
    if (avSlot) {
        avSlot.innerHTML = communityStoryViewerAvatarInner(authorPhotoUrl || '', authorInitials || 'M');
    }
    document.getElementById('storyLikeCount').textContent = String(likes != null ? likes : 0);
    document.getElementById('storyCommentCountFoot').textContent = String(comments != null ? comments : 0);
    document.getElementById('storyLikeBtn').classList.toggle('liked', !!liked);
    document.getElementById('deleteStoryBtn').onclick = function() { deleteStory(id); };
    document.getElementById('storyCommentInput').value = '';
    loadStoryCommentsForViewer(id);
    const fill = document.getElementById('storyProgressFill');
    fill.style.animation = 'none';
    fill.offsetHeight;
    fill.style.animation = 'storyProgress 5s linear forwards';
    document.getElementById('storyViewerOverlay').classList.add('show');
    clearTimeout(storyProgressTimer);
    storyProgressTimer = setTimeout(closeViewStoryModal, 5000);
}
function closeViewStoryModal() {
    clearTimeout(storyProgressTimer);
    currentStoryId = null;
    document.getElementById('storyViewerOverlay').classList.remove('show');
}

function deleteStory(id) {
    (window.hbConfirm || function (m) { return Promise.resolve(window.confirm(m)); })(<?php echo json_encode(fo_t('community.delete_story_confirm'), JSON_UNESCAPED_UNICODE); ?>).then(function (ok) {
        if (!ok) {
            return;
        }
        fetch(COMMUNITY_PAGE, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete_story&ajax=1&id=' + id
        }).then(r => r.json()).then(data => {
            if (data.success) {
                window.location.href = COMMUNITY_PAGE + '?story_deleted=1';
            } else {
                commActionToast(COMMUNITY_TOAST.error);
            }
        }).catch(function () {
            (window.hbAlert || alert)('Erreur');
        });
    });
}

function generateWithAI(btn) {
    (window.hbPrompt || function (m) { return Promise.resolve(window.prompt(m)); })(
        'Quel plat souhaitez-vous générer en image (ex: Pizza margherita, Burger gastronomique) ?',
        { title: 'Génération d\'image', placeholder: 'Ex: Pizza margherita' }
    ).then(function (promptText) {
        if (!promptText) {
            return;
        }
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
        btn.disabled = true;
        fetch(COMMUNITY_PAGE, {
            method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=generate_image&ajax=1&prompt=' + encodeURIComponent(promptText)
        }).then(function (r) {
            return r.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('generate_image non-JSON:', text.slice(0, 300));
                    throw e;
                }
            });
        }).then(function (data) {
            if (data && data.success) {
                document.getElementById('imagePreview').src = communityMediaUrl(data.image);
                document.getElementById('imagePreviewWrap').style.display = 'inline-block';
                var aiInput = document.getElementById('aiGeneratedImage');
                if (!aiInput) {
                    aiInput = document.createElement('input');
                    aiInput.type = 'hidden';
                    aiInput.name = 'ai_image';
                    aiInput.id = 'aiGeneratedImage';
                    document.getElementById('addPostForm').appendChild(aiInput);
                }
                aiInput.value = data.image;
            } else {
                (window.hbAlert || alert)('Erreur: ' + (data && data.message ? data.message : 'Génération impossible'));
            }
        }).catch(function () {
            (window.hbAlert || alert)('Erreur de génération d\'image (réponse serveur invalide)');
        }).finally(function () {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
}

function translateComment(commentId, targetLang, btn) {
    if(!targetLang) return;
    // Close the menu
    const menu = document.getElementById('translate-menu-' + commentId);
    if (menu) menu.style.display = 'none';

    const contentEl = document.getElementById('comment-content-' + commentId);
    const originalText = contentEl.dataset.original || (contentEl.innerText || contentEl.textContent);
    // Store original on first translation
    if (!contentEl.dataset.original) contentEl.dataset.original = originalText;

    const spinner = '<i class="fas fa-spinner fa-spin text-muted"></i>';
    contentEl.innerHTML = spinner + ' Traduction en cours...';

    fetch('../Controllers/GeminiController.php', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=translate&lang=' + encodeURIComponent(targetLang) + '&text=' + encodeURIComponent(originalText)
    }).then(r => r.json()).then(data => {
        if(data.success && data.text) {
            contentEl.textContent = data.text;
            // Remove old badge
            const oldBadge = document.getElementById('badge-trans-' + commentId);
            if (oldBadge) oldBadge.remove();
            const badge = document.createElement('span');
            badge.id = 'badge-trans-' + commentId;
            badge.style.cssText = 'font-size:0.72rem;color:var(--green);font-style:italic;margin-left:6px;';
            const langNames = {en:'EN', fr:'FR'};
            badge.textContent = '(' + (langNames[targetLang] || targetLang) + ')';
            contentEl.appendChild(badge);
        } else {
            contentEl.textContent = contentEl.dataset.original || originalText;
        }
    }).catch(() => {
        contentEl.textContent = contentEl.dataset.original || originalText;
    });
}

function restoreOriginalComment(commentId, btn) {
    const menu = document.getElementById('translate-menu-' + commentId);
    if (menu) menu.style.display = 'none';
    const contentEl = document.getElementById('comment-content-' + commentId);
    const original = btn.dataset.original || contentEl.dataset.original;
    if (original) {
        contentEl.textContent = original;
        const badge = document.getElementById('badge-trans-' + commentId);
        if (badge) badge.remove();
    }
}

function translateStoryComment(commentId, targetLang, btn) {
    if (!targetLang) return;
    const menu = document.getElementById('story-translate-menu-' + commentId);
    if (menu) menu.style.display = 'none';
    const contentEl = document.getElementById('story-comment-text-' + commentId);
    if (!contentEl) return;
    const originalText = contentEl.dataset.original || (contentEl.innerText || contentEl.textContent || '').trim();
    if (!contentEl.dataset.original) contentEl.dataset.original = originalText;

    const spinner = '<i class="fas fa-spinner fa-spin"></i>';
    contentEl.innerHTML = spinner + ' Traduction…';

    fetch('../Controllers/GeminiController.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=translate&lang=' + encodeURIComponent(targetLang) + '&text=' + encodeURIComponent(originalText)
    }).then(r => r.json()).then(data => {
        if (data.success && data.text) {
            contentEl.textContent = data.text;
            const oldBadge = document.getElementById('story-badge-trans-' + commentId);
            if (oldBadge) oldBadge.remove();
            const badge = document.createElement('span');
            badge.id = 'story-badge-trans-' + commentId;
            badge.style.cssText = 'font-size:0.68rem;color:rgba(255,255,255,0.75);font-style:italic;margin-left:6px;';
            const langNames = {fr: 'FR', en: 'EN'};
            badge.textContent = '(' + (langNames[targetLang] || targetLang) + ')';
            contentEl.appendChild(badge);
        } else {
            contentEl.textContent = contentEl.dataset.original || originalText;
        }
    }).catch(function() {
        contentEl.textContent = contentEl.dataset.original || originalText;
    });
}

function restoreStoryOriginalComment(commentId, btn) {
    const menu = document.getElementById('story-translate-menu-' + commentId);
    if (menu) menu.style.display = 'none';
    const contentEl = document.getElementById('story-comment-text-' + commentId);
    if (!contentEl) return;
    const original = btn.dataset.original || contentEl.dataset.original;
    if (original) {
        contentEl.textContent = original;
        const badge = document.getElementById('story-badge-trans-' + commentId);
        if (badge) badge.remove();
    }
}

function toggleStoryTranslateMenu(commentId, event) {
    event.stopPropagation();
    document.querySelectorAll('.translate-menu').forEach(function(m) {
        if (m.id !== 'story-translate-menu-' + commentId) m.style.display = 'none';
    });
    const sm = document.getElementById('story-translate-menu-' + commentId);
    if (sm) sm.style.display = sm.style.display === 'none' ? 'block' : 'none';
}

function toggleTranslateMenu(commentId, event) {
    event.stopPropagation();
    // Close all other open menus first
    document.querySelectorAll('.translate-menu').forEach(m => {
        if (m.id !== 'translate-menu-' + commentId) m.style.display = 'none';
    });
    const menu = document.getElementById('translate-menu-' + commentId);
    if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Close translate menus when clicking outside
document.addEventListener('click', () => {
    document.querySelectorAll('.translate-menu').forEach(m => m.style.display = 'none');
});

function exportToPDF() {
    const element = document.getElementById('pdf-export-area');
    const opt = {
      margin:       0.5,
      filename:     'Communaute_HappyBite.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

// Add fadeOut keyframe dynamically
const style = document.createElement('style');
style.textContent = '@keyframes fadeOut { from { opacity:1; transform:translateY(0); } to { opacity:0; transform:translateY(-10px); } }';
document.head.appendChild(style);

function exportToPDF() {
    const btn = document.querySelector('[onclick="exportToPDF()"]');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
    btn.disabled = true;

    const element = document.getElementById('pdf-export-area');
    const opt = {
        margin:      [10, 10, 10, 10],
        filename:    'Communaute_HappyBite.pdf',
        image:       { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 2, useCORS: true, logging: false, ignoreElements: el => el.hasAttribute('data-html2canvas-ignore') },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }).catch(() => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    });
}
</script>

<footer class="site-copyright">© 2026 HappyBite</footer>

<?php
require_once __DIR__ . '/includes/hb_action_toast.php';
$commFlashKeys = ['success', 'updated', 'comment_success', 'comment_updated', 'comment_deleted', 'story_success', 'story_deleted'];
$commStripFlash = false;
foreach ($commFlashKeys as $commFlashKey) {
    if (isset($_GET[$commFlashKey])) {
        $commStripFlash = true;
        break;
    }
}
if ($message !== '') {
    hb_action_toast_script($message, 4000, $commStripFlash, $commFlashKeys);
}
if (!empty($storyError)) {
    hb_action_toast_script((string) $storyError, 5000);
}
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    require __DIR__ . '/includes/guest_login_gate.php';
}
?>
</body>
</html>
