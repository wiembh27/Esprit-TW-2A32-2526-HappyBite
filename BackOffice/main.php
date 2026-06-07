<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/includes/bo_sidebar_icons.php';

$pages = [
    'accueil' => 'home.php',
    'utilisateur' => 'admin.php',
    'produits' => 'List-Produit.php',
    'categories' => 'List-Categorie.php',
    'recettes' => 'List-Recette.php',
    'commandes' => 'list-com-liv.php',
    'post' => 'list_posts.php',
    'sante' => 'affiche.php',
];

$active = isset($_GET['page']) ? (string) $_GET['page'] : 'accueil';
if (!isset($pages[$active])) {
    $active = 'accueil';
}

$iframeSrc = $pages[$active] . '?embed=1';

$logoSrc = is_file(dirname(__DIR__) . '/images/logo.png')
    ? '../FrontOffice/images/logo.png'
    : 'images/logo.png';

$navItems = [
    ['page' => 'accueil', 'label' => 'Accueil', 'icon' => 'home'],
    ['page' => 'utilisateur', 'label' => 'Gestion utilisateur', 'icon' => 'users'],
    ['page' => 'produits', 'label' => 'Gestion Produits', 'icon' => 'box'],
    ['page' => 'commandes', 'label' => 'Gestion Commande', 'icon' => 'truck'],
    ['page' => 'post', 'label' => 'Gestion Post', 'icon' => 'comments'],
    ['page' => 'sante', 'label' => 'Gestion Santé', 'icon' => 'health'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite - BackOffice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo-main">
<div class="page-bo">
    <aside class="bo-sidebar" aria-label="Menu principal">
        <a class="bo-sidebar-brand" href="main.php?page=accueil">
            <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="" class="bo-sidebar-logo" width="100" height="100">
        </a>
        <nav class="bo-sidebar-nav">
            <?php foreach ($navItems as $item) {
                $isActive = $active === $item['page'];
                $href = 'main.php?page=' . rawurlencode($item['page']);
                ?>
            <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" class="bo-sidebar-link<?php echo $isActive ? ' is-active' : ''; ?>" target="_self">
                <?php echo bo_sidebar_icon_markup($item['icon']); ?>
                <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <?php } ?>
        </nav>
        <a href="logout.php" class="bo-sidebar-logout" target="_top">
            <?php echo bo_sidebar_icon_markup('logout'); ?>
            <span>Se déconnecter</span>
        </a>
    </aside>
    <main class="bo-main-frame-wrap">
        <iframe
            title="BackOffice Content"
            class="bo-main-frame"
            src="<?php echo htmlspecialchars($iframeSrc); ?>"
            name="bo-content-frame"
        ></iframe>
    </main>
</div>
<?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_footer(); ?>
</body>
</html>
