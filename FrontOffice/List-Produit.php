<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Controllers/FrigoController.php';
require_once __DIR__ . '/../Controllers/AiRecetteController.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/includes/panier_session.php';

$produitController = new ProduitController();
$categorieController = new CategorieController();
$frigoController = new FrigoController();
panier_ensure_session();

$categories = $categorieController->listCategories();

$action = $_GET['action'] ?? 'normal';
$motCle = trim($_GET['motCle'] ?? '');
$idCategorie = trim($_GET['id_categorie'] ?? '');

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$idUtilisateur = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;
$userRole = $loggedIn ? strtolower(trim((string) ($_SESSION['user_role'] ?? ''))) : '';
$isClient = $loggedIn && $userRole === 'client';
$isFournisseur = $loggedIn && $userRole === 'fournisseur';
$isNutritionniste = $loggedIn && $userRole === 'nutritionniste';

if (!$isClient && $action === 'smart') {
    $action = 'normal';
}

$resultatIA = null;

$profilSante = null;
if ($loggedIn && $idUtilisateur > 0) {
    $db = Config::getConnexion();
    $stmt = $db->prepare('SELECT * FROM profil_sante WHERE id_utilisateur = :id_utilisateur LIMIT 1');
    $stmt->execute(['id_utilisateur' => $idUtilisateur]);
    $rowProfil = $stmt->fetch(PDO::FETCH_ASSOC);
    $profilSante = is_array($rowProfil) ? $rowProfil : null;
}

// IA BUDGET + SANTE (clients uniquement)
if ($isClient && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action_ia_budget'] ?? '') === 'alternative_budget') {
    $produitCher = trim($_POST['produit_cher'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    if (!empty($produitCher)) {
        $ai = new AiRecetteController();
        $resultatIA = $ai->proposerAlternativeBudgetSante($produitCher, $budget, is_array($profilSante) ? $profilSante : []);
    } else {
        $resultatIA = "Veuillez saisir un produit.";
    }
}


if ($action === 'smart' && $isClient && $profilSante) {
    $produits = $produitController->rechercherProduitsIntelligents(
        $idUtilisateur,
        $motCle,
        $idCategorie
    );
} else {
    $produits = (!empty($motCle) || !empty($idCategorie))
        ? $produitController->rechercherProduits($motCle, $idCategorie)
        : $produitController->listProduits();
}
?>

<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title>HappyBite — <?php echo fo_e('products.title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/Views/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style-original-views.css">

    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
        }

        .promo-card {
            border: 2px solid #f0ad4e !important;
            background: #fff8e1 !important;
        }

        .promo-badge {
            display: inline-block;
            background: #f0ad4e;
            color: #fff;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
        }

        .promo-old-price {
            color: #8c8c8c;
            text-decoration: line-through;
            margin-right: 8px;
        }

        .promo-new-price {
            color: #d97706;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .btn-hb-details {
            font-weight: 700 !important;
            font-size: 0.95rem;
            padding: 0.5rem 1.35rem !important;
        }

        /* Même style que le bouton « Demandez-moi » (Ai.php) / CaloryEye */
        .caloryeye-analyse-btn {
            width: 100%;
            min-height: 52px;
            border: 2px solid #43a047;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 34px rgba(19, 30, 23, 0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            font-family: inherit;
            color: inherit;
            text-decoration: none;
        }

        .caloryeye-analyse-btn:hover {
            filter: brightness(0.98);
        }

        .caloryeye-analyse-btn__label {
            background: linear-gradient(90deg, #e53935 0%, #fb8c00 52%, #43a047 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .caloryeye-analyse-btn__icon {
            width: 22px;
            height: 22px;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
        }

        /* Bloc AltBite uniquement — même style que ChefBot (Frigo) */
        .commande-wrap .hb-ai-section.altbite-section {
            background: linear-gradient(135deg, #e8f8ef, #ffffff) !important;
            border-radius: 24px !important;
            padding: 28px !important;
            margin-bottom: 1.5rem;
            border: none !important;
            border-left: 6px solid #43a047 !important;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        .hb-ai-section-title {
            margin-bottom: 12px;
        }

        .hb-gradient-title {
            margin: 0;
            font-weight: 700;
            font-size: 1.55rem;
            line-height: 1.25;
            display: inline-block;
            background: linear-gradient(90deg, #e53935 0%, #fb8c00 52%, #43a047 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
    </style>
</head>
<body>

<?php
$nav_active = 'produits';
require __DIR__ . '/includes/nav_front.php';
?>

<?php require_once __DIR__ . '/includes/hb_action_toast.php'; hb_action_toast_render(); ?>

<main class="commande-wrap">
<div class="container py-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold"><?php echo fo_e('products.heading'); ?></h2>
        <p class="text-muted"><?php echo fo_e('products.subheading'); ?></p>
    </div>

    <div class="d-flex justify-content-center gap-3 mb-4 flex-wrap">
        <a href="?action=normal" class="btn btn-outline-secondary rounded-pill px-4">
            <?php echo fo_e('products.all_products_mode'); ?>
        </a>
        <?php if ($loggedIn && $isFournisseur): ?>
            <a href="List-Produit-Fournisseur.php" class="btn btn-success rounded-pill px-4">
                <?php echo fo_e('products.add_product'); ?>
            </a>
        <?php elseif ($isClient): ?>
            <a href="?action=smart" class="btn btn-success rounded-pill px-4">
                <?php echo fo_e('products.personalized_btn'); ?>
            </a>
        <?php elseif (!$loggedIn): ?>
            <span class="btn btn-success rounded-pill px-4 disabled" style="opacity:0.65;cursor:not-allowed;" title="<?php echo fo_e('recipes.login_personalized'); ?>">
                <?php echo fo_e('products.personalized_btn'); ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ($action === 'smart' && $isClient && $profilSante) { ?>
        <div class="alert alert-success text-center shadow-sm">
            <strong><?php echo fo_e('products.personalized_active'); ?></strong><br>
            <?php
            $infos = [];
            foreach (['allergenes' => fo_t('products.label_allergens'), 'carences' => fo_t('products.label_deficits'), 'maladies' => fo_t('products.label_diseases')] as $field => $label) {
                $raw = $profilSante[$field] ?? '';
                $items = [];
                if (is_string($raw) && $raw !== '') {
                    $decoded = json_decode($raw, true);
                    $items = is_array($decoded)
                        ? array_values(array_filter(array_map('trim', array_map('strval', $decoded))))
                        : array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $raw))));
                }
                if (!empty($items)) {
                    $infos[] = $label . ' : ' . htmlspecialchars(implode(', ', $items), ENT_QUOTES, 'UTF-8');
                }
            }

            if (!empty($profilSante['objectif'])) {
                $infos[] = fo_t('products.label_goal') . ' : ' . htmlspecialchars((string) $profilSante['objectif'], ENT_QUOTES, 'UTF-8');
            }

            echo !empty($infos) ? implode(' | ', $infos) : fo_e('products.smart_filtered');
            ?>
        </div>
    <?php } elseif ($action === 'smart' && $isClient) { ?>
        <div class="alert alert-warning text-center shadow-sm">
            <?php echo fo_e('products.no_health_profile'); ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-secondary text-center shadow-sm">
            <?php if ($loggedIn): ?>
                <?php echo fo_e('products.all_available'); ?>
            <?php else: ?>
                <?php echo fo_e('products.guest_hint'); ?>
            <?php endif; ?>
        </div>
    <?php } ?>

    <?php if ($isClient): ?>
    <!-- BLOC IA BUDGET + SANTE (style ChefBot — titre + formulaire seulement) -->
    <div class="hb-ai-section altbite-section shadow-sm">
        <div class="hb-ai-section-title">
            <h5 class="hb-gradient-title"><?php echo fo_e('products.altbite_title'); ?></h5>
        </div>

        <form method="POST">
            <input type="hidden" name="action_ia_budget" value="alternative_budget">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?php echo fo_e('products.expensive_label'); ?></label>
                    <input
                        type="text"
                        name="produit_cher"
                        class="form-control"
                        placeholder="<?php echo fo_e('products.expensive_ph'); ?>"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?php echo fo_e('products.budget_label'); ?></label>
                    <input
                        type="number"
                        name="budget"
                        class="form-control"
                        placeholder="<?php echo fo_e('products.budget_ph'); ?>"
                        min="0"
                        step="0.1"
                    >
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="caloryeye-analyse-btn w-100">
                        <img src="images/analyse.png" alt="" class="caloryeye-analyse-btn__icon">
                        <span class="caloryeye-analyse-btn__label"><?php echo fo_e('products.find_alternative'); ?></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($resultatIA)) { ?>
        <div class="alert alert-info shadow-sm mb-4">
            <?php echo nl2br(htmlspecialchars($resultatIA)); ?>
        </div>
    <?php } ?>
    <?php endif; ?>

    <div class="rayons-section mb-4">
        <div class="rayons-header mb-3">
            <h4 class="mb-2"><?php echo fo_e('products.rayons'); ?></h4>
            <p class="mb-0"><?php echo fo_e('products.rayons_desc'); ?></p>
        </div>

        <?php if (!empty($categories)) { ?>
            <div class="rayons-scroll">
                <?php foreach ($categories as $categorie) { ?>
                    <div class="rayon-card-mini">
                        <h5><?php echo fo_db_e($categorie->getNom()); ?></h5>
                        <p>
                            <?php
                            $description = trim($categorie->getDescription() ?? '');
                            echo !empty($description)
                                ? fo_db_e($description)
                                : fo_e('products.category_default_desc');
                            ?>
                        </p>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET">
                <input type="hidden" name="action" value="<?php echo htmlspecialchars($action); ?>">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="motCle" class="form-label"><?php echo fo_e('products.search_full'); ?></label>
                        <input
                            type="text"
                            class="form-control"
                            id="motCle"
                            name="motCle"
                            placeholder="<?php echo fo_e('products.search_ph'); ?>"
                            value="<?php echo htmlspecialchars($motCle); ?>"
                        >
                    </div>

                    <div class="col-md-5">
                        <label for="id_categorie" class="form-label"><?php echo fo_e('products.search_category'); ?></label>
                        <select class="form-select" id="id_categorie" name="id_categorie">
                            <option value=""><?php echo fo_e('products.all_categories'); ?></option>
                            <?php foreach ($categories as $categorie) { ?>
                                <option
                                    value="<?php echo $categorie->getIdCategorie(); ?>"
                                    <?php echo ($idCategorie == $categorie->getIdCategorie()) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($categorie->getNom()); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100"><?php echo fo_e('products.filter_btn'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($produits)) { ?>
        <div class="alert alert-info text-center shadow-sm">
            <?php echo fo_e('products.none_found'); ?>
        </div>
    <?php } else { ?>
        <div class="row">
            <?php foreach ($produits as $produit) { ?>
                <?php
                $allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
                $benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
                $isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm rounded-4 <?php echo $isPromo ? 'promo-card' : 'border-0'; ?>" id="produit-<?php echo $produit['id_produit']; ?>">
                        <div class="card-body d-flex flex-column">

                            <?php if ($isPromo) { ?>
                                <div class="mb-2">
                                    <span class="promo-badge"><?php echo fo_e('products.promo_badge'); ?></span>
                                </div>
                            <?php } ?>

                            <div class="text-center mb-3">
                                <?php if (!empty($produit['image'])) { ?>
                                    <img
                                        src="/uploads/<?php echo htmlspecialchars($produit['image']); ?>"
                                        alt="<?php echo htmlspecialchars($produit['nom']); ?>"
                                        style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 15px;"
                                    >
                                <?php } else { ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center rounded-4"
                                         style="height: 200px;">
                                        <span class="text-muted"><?php echo fo_e('product.no_image'); ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <h5 class="fw-bold mb-1"><?php echo fo_db_e($produit['nom']); ?></h5>
                                <span class="badge bg-light text-dark">
                                    <?php echo fo_db_e($produit['nom_categorie']); ?>
                                </span>
                            </div>

                            <p class="mb-2">
                                <strong><?php echo fo_e('product.supplier'); ?></strong>
                                <span class="fw-semibold text-dark">
                                    <?php echo fo_db_e($produit['nom_fournisseur'] ?? fo_t('product.supplier_unknown')); ?>
                                </span>
                            </p>

                            <p class="mb-2">
                                <strong><?php echo fo_e('product.price'); ?></strong>
                                <?php if ($isPromo) { ?>
                                    <span class="promo-old-price">
                                        <?php echo htmlspecialchars($produit['prix']); ?> DT
                                    </span>
                                    <span class="promo-new-price">
                                        <?php echo htmlspecialchars($produit['promo']); ?> DT
                                    </span>
                                <?php } else { ?>
                                    <span class="text-success fw-bold">
                                        <?php echo htmlspecialchars($produit['prix']); ?> DT
                                    </span>
                                <?php } ?>
                            </p>

                            <p class="mb-2">
                                <strong><?php echo fo_e('product.calories'); ?></strong>
                                <?php echo htmlspecialchars($produit['calories'] ?? fo_t('fridge.undefined')); ?> <?php echo fo_e('products.cal_unit'); ?>
                            </p>

                            <div class="mb-3">
                                <strong><?php echo fo_e('fridge.allergens'); ?></strong><br>
                                <?php if (!empty($allergenes)) { ?>
                                    <?php foreach ($allergenes as $item) { ?>
                                        <span class="badge bg-danger me-1 mb-1">
                                            <?php echo htmlspecialchars($item); ?>
                                        </span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span class="text-muted"><?php echo fo_e('fridge.none'); ?></span>
                                <?php } ?>
                            </div>

                            <div class="mb-3">
                                <strong><?php echo fo_e('fridge.benefits'); ?></strong><br>
                                <?php if (!empty($benefices)) { ?>
                                    <?php foreach ($benefices as $item) { ?>
                                        <span class="badge bg-success me-1 mb-1">
                                            <?php echo htmlspecialchars($item); ?>
                                        </span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span class="text-muted"><?php echo fo_e('fridge.not_specified'); ?></span>
                                <?php } ?>
                            </div>

                            <div class="mt-auto">
                                <div class="row g-2 align-items-end">

                                    <div class="<?php echo $isFournisseur ? 'col-12' : 'col-4'; ?>">
                                        <button type="button"
                                                class="btn btn-outline-success w-100 rounded-pill btn-hb-details js-fo-catalog-detail"
                                                data-detail-url="Detail-Produit.php?id=<?php echo (int) $produit['id_produit']; ?>&fragment=1"
                                                data-detail-title="<?php echo fo_e('products.details'); ?>">
                                            <?php echo fo_e('products.details'); ?>
                                        </button>
                                    </div>

                                    <?php if ($isClient): ?>
                                    <div class="col-4">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="action_panier" value="ajouter_panier">
                                            <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
                                            <button type="button"
                                                    class="btn btn-hb-orange w-100 rounded-pill btn-sm js-toast-btn js-panier-btn"
                                                    data-product-id="<?php echo (int) $produit['id_produit']; ?>"
                                                    data-toast-message="<?php echo fo_e('cart.added'); ?>">
                                                <?php echo fo_e('products.cart_btn'); ?>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="col-4">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="action_frigo" value="ajouter_frigo">
                                            <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">

                                            <input
                                                type="number"
                                                name="quantite"
                                                min="1"
                                                value="1"
                                                class="form-control form-control-sm text-center rounded-pill mb-1"
                                            >

                                            <button type="button"
                                                    class="btn btn-success w-100 rounded-pill btn-sm js-toast-btn js-frigo-btn"
                                                    data-toast-message="<?php echo fo_e('products.fridge_added'); ?>">
                                                <?php echo fo_e('products.fridge_btn'); ?>
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

</div>
</main>

<footer>
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<script>
    window.HB_LIST_PRODUIT_TOAST = <?php echo json_encode([
        'productNotFound' => fo_t('toast.product_not_found'),
        'addFailed' => fo_t('toast.add_failed'),
        'networkError' => fo_t('toast.network_error'),
        'formNotFound' => fo_t('toast.form_not_found'),
        'fridgeAddFailed' => fo_t('toast.fridge_add_failed'),
        'add' => fo_t('toast.add'),
        'fridgeAdded' => fo_t('products.fridge_added'),
        'cartAdded' => fo_t('cart.added'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var buttons = document.querySelectorAll('.js-toast-btn');
        var toastMsg = window.HB_LIST_PRODUIT_TOAST || {};

        if (buttons.length === 0) {
            return;
        }

        function showToast(message) {
            if (typeof window.hbShowActionToast === 'function') {
                window.hbShowActionToast(message, 3000);
            }
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                if (button.classList.contains('js-panier-btn')) {
                    var productId = button.dataset.productId || '';
                    if (!productId) {
                        showToast(toastMsg.productNotFound || 'Product');
                        return;
                    }
                    button.disabled = true;
                    fetch('ajouter_panier.php?ajax=1&id=' + encodeURIComponent(productId), {
                        method: 'GET',
                        credentials: 'same-origin'
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data && data.ok) {
                                showToast(button.dataset.toastMessage || toastMsg.add || 'Add');
                            } else {
                                showToast((data && data.message) ? data.message : (toastMsg.addFailed || 'Error'));
                            }
                        })
                        .catch(function () {
                            showToast(toastMsg.networkError || 'Error');
                        })
                        .finally(function () {
                            button.disabled = false;
                        });
                    return;
                }
                if (button.classList.contains('js-frigo-btn')) {
                    var form = button.closest('form');
                    if (!form) {
                        showToast(toastMsg.formNotFound || 'Error');
                        return;
                    }
                    var pidInput = form.querySelector('[name="id_produit"]');
                    var qtyInput = form.querySelector('[name="quantite"]');
                    var pid = pidInput ? String(pidInput.value || '').trim() : '';
                    var qty = qtyInput ? parseInt(String(qtyInput.value || '1'), 10) : 1;
                    if (!pid || parseInt(pid, 10) < 1) {
                        showToast(toastMsg.productNotFound || 'Product');
                        return;
                    }
                    if (!qty || qty < 1) {
                        qty = 1;
                    }
                    button.disabled = true;
                    var body = new URLSearchParams();
                    body.set('ajax', '1');
                    body.set('id_produit', pid);
                    body.set('quantite', String(qty));
                    fetch('ajouter_frigo.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data && data.ok) {
                                showToast(button.dataset.toastMessage || toastMsg.fridgeAdded || 'OK');
                            } else {
                                showToast((data && data.message) ? data.message : (toastMsg.fridgeAddFailed || 'Error'));
                            }
                        })
                        .catch(function () {
                            showToast(toastMsg.networkError || 'Error');
                        })
                        .finally(function () {
                            button.disabled = false;
                        });
                    return;
                }
                showToast(button.dataset.toastMessage || toastMsg.add || 'Add');
            });
        });
    })();
</script>
<?php
$foDetailModalTitle = fo_t('products.details');
require_once __DIR__ . '/includes/fo_catalog_detail_modal.php';
?>
</body>
</html>



