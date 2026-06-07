<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';

require_once __DIR__ . '/../Model/User.php';

require_once __DIR__ . '/includes/bo_user_admin_nav.php';
require_once __DIR__ . '/../Controllers/UtilisateurPhotoSql.php';
require_once __DIR__ . '/../config/Database.php';

use Model\User;

$userModel = new User();
$id = (int) ($_GET['id'] ?? 0);
$user = $id > 0 ? $userModel->findById($id) : null;

if (!$user || strtolower(trim((string) ($user['role'] ?? ''))) === 'admin') {
    header('Location: admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'prenom' => trim((string) ($_POST['prenom'] ?? '')),
        'nom' => trim((string) ($_POST['nom'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'budget' => (float) ($_POST['budget'] ?? 0),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    $userModel->update($id, $data);

    if (!empty($_FILES['profile_photo']['tmp_name']) && is_uploaded_file((string) $_FILES['profile_photo']['tmp_name'])) {
        $path = utilisateur_handle_profile_photo_upload($_FILES['profile_photo']);
        if ($path !== null) {
            $userModel->updateProfileImagePath($id, $path);
        }
    }

    header('Location: admin.php');
    exit();
}

$nomComplet = trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')));
$profileRel = utilisateur_fetch_profile_relative_path(Database::getConnection(), $id);
$profileImgSrc = utilisateur_nav_profile_img_src($profileRel);
if ($profileImgSrc === null) {
    $profileImgSrc = '../FrontOffice/images/profile.png';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — Modifier utilisateur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-bo-edit-user .commande-wrap { padding-top: 8px; }
        .page-bo-edit-user .bo-form-row--2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            align-items: end;
        }
        .page-bo-edit-user .bo-form-row--full {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            margin-top: 6px;
        }
        .page-bo-edit-user .bo-form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .page-bo-edit-user .bo-panel .bo-field input:not([type="file"]),
        .page-bo-edit-user .bo-panel .bo-field textarea,
        .page-bo-edit-user .bo-panel .bo-field select {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        .page-bo-edit-user .edit-user-photo-row {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        .page-bo-edit-user .edit-user-photo-preview {
            flex-shrink: 0;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #d8e8dc;
            background: #f4faf5;
        }
        .page-bo-edit-user .edit-user-photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .page-bo-edit-user .edit-user-photo-file {
            flex: 1 1 220px;
            min-width: 0;
        }
    </style>
</head>
<body class="page-bo page-list-com-liv page-bo-edit-user">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('utilisateur');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1100px; width: 100%;">
        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <?php bo_user_admin_nav('dashboard'); ?>
            </div>
        </div>

        <div class="liste-com-liv-title-row">
            <div>
                <h1 class="liste-com-liv-title">Modifier l'utilisateur</h1>
                <p class="liste-com-liv-subtitle"><?php echo htmlspecialchars($nomComplet !== '' ? $nomComplet : 'Compte', ENT_QUOTES, 'UTF-8'); ?> — ID <?php echo (int) ($user['id'] ?? 0); ?></p>
            </div>
            <div class="liste-com-liv-title-actions">
                <a href="admin.php" class="btn-commande-outline btn-vue-toggle">Retour liste</a>
            </div>
        </div>

        <section class="bo-panel" aria-label="Formulaire utilisateur">
            <form method="post" action="edit_user.php?id=<?php echo (int) $id; ?>" enctype="multipart/form-data">
                <div class="bo-form-row--full">
                    <div class="bo-field">
                        <p id="edit-user-photo-label" class="liste-com-liv-subtitle" style="margin: 0 0 10px;">Photo de profil</p>
                        <div class="edit-user-photo-row" role="group" aria-labelledby="edit-user-photo-label">
                            <div class="edit-user-photo-preview">
                                <img src="<?php echo htmlspecialchars($profileImgSrc, ENT_QUOTES, 'UTF-8'); ?>" width="96" height="96" alt="">
                            </div>
                            <div class="edit-user-photo-file">
                                <label for="profile_photo" class="liste-com-liv-subtitle" style="display:block;margin:0 0 6px;font-size:0.9rem;">Changer l’image</label>
                                <input id="profile_photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp">
                                <p class="liste-com-liv-subtitle" style="margin:8px 0 0;font-size:0.8rem;">JPEG, PNG, GIF ou WebP — max. 2 Mo. Enregistré dans <code>uploads/users pictures/</code>.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bo-form-row--2">
                    <div class="bo-field">
                        <label for="prenom">Prénom</label>
                        <input id="prenom" type="text" name="prenom" value="<?php echo htmlspecialchars((string) ($user['prenom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="given-name">
                    </div>
                    <div class="bo-field">
                        <label for="nom">Nom</label>
                        <input id="nom" type="text" name="nom" value="<?php echo htmlspecialchars((string) ($user['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="family-name">
                    </div>
                </div>

                <div class="bo-form-row--full">
                    <div class="bo-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email">
                    </div>
                </div>

                <div class="bo-form-row--full">
                    <div class="bo-field">
                        <label for="budget">Budget (€)</label>
                        <input id="budget" type="number" name="budget" value="<?php echo htmlspecialchars((string) ($user['budget'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>" step="50" min="0">
                    </div>
                </div>

                <div class="bo-form-row--full">
                    <div class="bo-field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars((string) ($user['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>

                <div class="bo-form-actions">
                    <button type="submit" class="bo-btn-primary">Enregistrer</button>
                    <a href="admin.php" class="btn-commande-outline">Annuler</a>
                </div>
            </form>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
</body>
</html>
