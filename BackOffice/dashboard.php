<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Dashboard - BackOffice HappyBite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body class="page-bo">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <img src="images/logo.png" alt="HappyBite Logo" height="40" class="me-2">
            Dashboard
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarBack">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarBack">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="List-Produit.php">
                        <i class="fas fa-box me-1"></i>Produits
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="List-Recette.php">
                        <i class="fas fa-utensils me-1"></i>Recettes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="List-Categorie.php">
                        <i class="fas fa-tags me-1"></i>Catégories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="list_posts.php">
                        <i class="fas fa-comments me-1"></i>Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="list-com-liv.php">
                        <i class="fas fa-shopping-cart me-1"></i>Commandes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../Home.php" title="Retourner au site public">
                        <i class="fas fa-globe me-1"></i>Site Public
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="fas fa-tachometer-alt text-success me-2"></i>
                Dashboard Administrateur
            </h1>
            <p class="text-muted mb-0">Bienvenue dans le panneau d'administration HappyBite</p>
        </div>
        <div class="text-end">
            <div class="text-muted small">Date du jour</div>
            <div class="fw-bold"><?php echo date('d/m/Y'); ?></div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 text-center">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-box fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Produits</h4>
                    <p class="text-muted mb-0">Gérer le catalogue</p>
                    <a href="List-Produit.php" class="bo-img-link mt-3" title="Voir les produits" aria-label="Voir les produits"><img src="images/details.png" width="24" height="24" alt=""></a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 text-center">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-utensils fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Recettes</h4>
                    <p class="text-muted mb-0">Gérer les recettes</p>
                    <a href="List-Recette.php" class="bo-img-link mt-3" title="Voir les recettes" aria-label="Voir les recettes"><img src="images/details.png" width="24" height="24" alt=""></a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 text-center">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-tags fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Catégories</h4>
                    <p class="text-muted mb-0">Organiser les produits</p>
                    <a href="List-Categorie.php" class="bo-img-link mt-3" title="Voir les catégories" aria-label="Voir les catégories"><img src="images/details.png" width="24" height="24" alt=""></a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 text-center">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-comments fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Posts</h4>
                    <p class="text-muted mb-0">Modérer les publications</p>
                    <a href="list_posts.php" class="bo-img-link mt-3" title="Voir les posts" aria-label="Voir les posts"><img src="images/details.png" width="24" height="24" alt=""></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-muted me-2"></i>
                        Activité récente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Statistiques détaillées</h6>
                        <p class="text-muted small mb-0">Les métriques détaillées seront affichées ici</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-cog text-muted me-2"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="Add-Produit.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plus me-1"></i>Ajouter Produit
                        </a>
                        <a href="Add-Recette.php" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-plus me-1"></i>Ajouter Recette
                        </a>
                        <a href="Add-Categorie.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-plus me-1"></i>Ajouter Catégorie
                        </a>
                        <a href="list_posts.php" class="bo-img-link" title="Voir les posts" aria-label="Voir les posts"><img src="images/details.png" width="24" height="24" alt=""></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">HappyBite - BackOffice</h6>
                <p class="text-muted small mb-0">Panneau d'administration pour la gestion du site</p>
            </div>
            <div class="col-md-6 text-end">
                <p class="text-muted small mb-0">© 2026 HappyBite. Tous droits réservés.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="js/controles.js"></script>

<?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_footer(); ?>
</body>
</html>