<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bo_session.php';

bo_session_start();

if (!empty($_SESSION['bo_logged_in']) && $_SESSION['bo_logged_in'] === true) {
    header('Location: main.php?page=accueil');
    exit;
}

$error = (string) ($_SESSION['bo_login_error'] ?? '');
unset($_SESSION['bo_login_error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — Connexion administrateur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../FrontOffice/auth/auth-layout.css">
</head>
<body class="auth-page">
<div class="auth-split">
    <aside class="auth-brand" aria-hidden="true">
        <div class="auth-brand__bg"></div>
        <div class="auth-brand__inner">
            <h1>Administration HappyBite</h1>
            <p>
                Espace réservé aux administrateurs. La connexion ici est indépendante du site public :
                vous pouvez être connecté au back-office sans être connecté sur le front-office.
            </p>
        </div>
    </aside>
    <main class="auth-panel">
        <div class="auth-card">
            <h2 class="auth-card__title">Connexion Back-office</h2>

            <details class="auth-demo-guide" open>
                <summary class="auth-demo-guide__title">Guide démo — compte administrateur</summary>
                <p class="auth-demo-guide__intro">Compte fictif pour la démonstration ESPRIT.</p>
                <ul class="auth-demo-guide__list">
                    <li><strong>Administrateur</strong> — <code>admin@happybite.tn</code></li>
                </ul>
                <p class="auth-demo-guide__pwd">Mot de passe : <strong>password</strong></p>
                <p class="auth-demo-guide__warn">Le changement de mot de passe depuis le profil envoie un vrai e-mail de confirmation. Utilisez une adresse Gmail réelle et accessible.</p>
            </details>

            <?php if ($error !== ''): ?>
                <div class="auth-alert auth-alert--error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="bo_auth_process.php">
                <div class="auth-field">
                    <label for="bo-email">Adresse email</label>
                    <input type="email" name="email" required id="bo-email" autocomplete="username" placeholder="admin@exemple.com">
                </div>
                <div class="auth-field">
                    <label for="bo-pwd">Mot de passe</label>
                    <input type="password" name="password" required id="bo-pwd" autocomplete="current-password" placeholder="Mot de passe administrateur">
                </div>
                <button type="submit" class="auth-btn-primary">Se connecter au back-office</button>
            </form>

            <p class="auth-footer-links">
                <a href="../FrontOffice/Home.php">Retour au site public</a>
            </p>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_footer(); ?>
</body>
</html>
