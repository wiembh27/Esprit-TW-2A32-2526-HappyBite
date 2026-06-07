<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sante_session.php';
require_once __DIR__ . '/includes/fo_i18n.php';
require_once __DIR__ . '/includes/fo_sante_inline.php';
require_once __DIR__ . '/includes/sante_db_ensure.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Controllers/SanteGamificationService.php';

fo_init_i18n_for_request();
$foSanteInline = fo_sante_inline_active();
fo_sante_redirect_if_standalone('edit_suivi', (int) ($_GET['id'] ?? 0));

$uid = sante_require_user_id();
$pdo = Database::getConnection();
fo_sante_db_ensure_schema($pdo);

$id = (int) ($_GET['id'] ?? 0);

if ($id < 1) {
    header('Location: user_health_space.php');
    exit;
}

function hb(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function empty_or_null(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}

function int_or_zero(string $value): int
{
    $value = trim($value);

    if ($value === '') {
        return 0;
    }

    return max(0, (int) $value);
}

function float_or_null(string $value): ?string
{
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    return (string) (float) str_replace(',', '.', $value);
}

$st = $pdo->prepare(
    'SELECT sj.* FROM suivi_journalier sj
     INNER JOIN profil_sante ps ON ps.id = sj.id_profil_sante
     WHERE sj.id = :sid
     AND ps.id_utilisateur = :uid
     LIMIT 1'
);

$st->execute([
    'sid' => $id,
    'uid' => $uid,
]);

$suivi = $st->fetch(PDO::FETCH_ASSOC);

if (!$suivi) {
    http_response_code(404);
    exit(fo_t('health.form.not_found'));
}

$errors = [];

$form = [
    'poids' => (string) ($suivi['poids'] ?? ''),
    'calories' => (string) ($suivi['calories'] ?? ''),
    'sommeil_heures' => (string) ($suivi['sommeil_heures'] ?? ''),
    'nbr_pas' => (string) ($suivi['nbr_pas'] ?? ''),
    'hydratation_litre' => (string) ($suivi['hydratation_litre'] ?? ''),
    'sport_type' => (string) ($suivi['sport_type'] ?? 'aucune'),
    'sport_duree_minutes' => (string) ($suivi['sport_duree_minutes'] ?? '0'),
    'sport_intensite' => (string) ($suivi['sport_intensite'] ?? 'aucune'),
    'sport_commentaire' => (string) ($suivi['sport_commentaire'] ?? ''),
];

if ($form['sport_type'] === '') {
    $form['sport_type'] = ((int) ($suivi['nbr_activites_sport'] ?? 0) > 0) ? 'autre' : 'aucune';
}

if ($form['sport_intensite'] === '') {
    $form['sport_intensite'] = $form['sport_type'] === 'aucune' ? 'aucune' : 'moyenne';
}

if ($form['sport_duree_minutes'] === '') {
    $form['sport_duree_minutes'] = '0';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'poids' => trim((string) ($_POST['poids'] ?? '')),
        'calories' => trim((string) ($_POST['calories'] ?? '')),
        'sommeil_heures' => trim((string) ($_POST['sommeil_heures'] ?? '')),
        'nbr_pas' => trim((string) ($_POST['nbr_pas'] ?? '')),
        'hydratation_litre' => trim((string) ($_POST['hydratation_litre'] ?? '')),
        'sport_type' => trim((string) ($_POST['sport_type'] ?? 'aucune')),
        'sport_duree_minutes' => trim((string) ($_POST['sport_duree_minutes'] ?? '0')),
        'sport_intensite' => trim((string) ($_POST['sport_intensite'] ?? 'aucune')),
        'sport_commentaire' => trim((string) ($_POST['sport_commentaire'] ?? '')),
    ];

    $allowedSportTypes = [
        'aucune',
        'marche',
        'course',
        'natation',
        'danse',
        'escalade',
        'velo',
        'cardio',
        'musculation',
        'yoga',
        'autre',
    ];

    $allowedIntensites = [
        'aucune',
        'faible',
        'moyenne',
        'elevee',
    ];

    $allowedHydratation = [
        'moins_1L',
        '1_1.5L',
        '1.5_2L',
        'plus_2L',
    ];

    if ($form['poids'] !== '' && (float) str_replace(',', '.', $form['poids']) <= 0) {
        $errors[] = fo_t('health.form.val.weight_positive');
    }

    if ($form['calories'] !== '' && (int) $form['calories'] < 0) {
        $errors[] = fo_t('health.form.val.calories_negative');
    }

    if ($form['sommeil_heures'] !== '') {
        $sommeil = (float) str_replace(',', '.', $form['sommeil_heures']);

        if ($sommeil < 0 || $sommeil > 24) {
            $errors[] = fo_t('health.form.val.sleep_range');
        }
    }

    if ($form['nbr_pas'] !== '' && (int) $form['nbr_pas'] < 0) {
        $errors[] = fo_t('health.form.val.steps_negative');
    }

    if (!in_array($form['sport_type'], $allowedSportTypes, true)) {
        $errors[] = fo_t('health.form.val.sport_type_invalid');
    }

    if (!in_array($form['sport_intensite'], $allowedIntensites, true)) {
        $errors[] = fo_t('health.form.val.sport_intensity_invalid');
    }

    if ($form['hydratation_litre'] !== '' && !in_array($form['hydratation_litre'], $allowedHydratation, true)) {
        $errors[] = fo_t('health.form.val.hydration_invalid');
    }

    $sportDuree = int_or_zero($form['sport_duree_minutes']);

    if ($sportDuree > 600) {
        $errors[] = fo_t('health.form.val.sport_duration_high');
    }

    if ($form['sport_type'] === 'aucune') {
        $sportDuree = 0;
        $form['sport_duree_minutes'] = '0';
        $form['sport_intensite'] = 'aucune';
    } else {
        if ($sportDuree <= 0) {
            $errors[] = fo_t('health.form.val.sport_duration_required');
        }

        if ($form['sport_intensite'] === 'aucune') {
            $errors[] = fo_t('health.form.val.sport_intensity_required');
        }
    }

    if (empty($errors)) {
        $payload = [
            'poids' => float_or_null($form['poids']),
            'calories' => empty_or_null($form['calories']),
            'sommeil_heures' => float_or_null($form['sommeil_heures']),
            'nbr_pas' => empty_or_null($form['nbr_pas']),
            'nbr_activites_sport' => $form['sport_type'] === 'aucune' ? 0 : 1,
            'sport_type' => $form['sport_type'],
            'sport_duree_minutes' => $sportDuree,
            'sport_intensite' => $form['sport_intensite'],
            'sport_commentaire' => empty_or_null($form['sport_commentaire']),
            'hydratation_litre' => empty_or_null($form['hydratation_litre']),
        ];

        try {
            $up = $pdo->prepare(
                'UPDATE suivi_journalier SET
                    poids = :poids,
                    calories = :calories,
                    sommeil_heures = :sommeil_heures,
                    nbr_pas = :nbr_pas,
                    nbr_activites_sport = :nbr_activites_sport,
                    sport_type = :sport_type,
                    sport_duree_minutes = :sport_duree_minutes,
                    sport_intensite = :sport_intensite,
                    sport_commentaire = :sport_commentaire,
                    hydratation_litre = :hydratation_litre,
                    analyse_resultat = NULL,
                    points_resultat = 0,
                    analyse_commentaire = NULL,
                    analysed_at = NULL
                 WHERE id = :id
                 AND id_profil_sante = :pid
                 LIMIT 1'
            );

            $up->execute([
                'poids' => $payload['poids'],
                'calories' => $payload['calories'],
                'sommeil_heures' => $payload['sommeil_heures'],
                'nbr_pas' => $payload['nbr_pas'],
                'nbr_activites_sport' => $payload['nbr_activites_sport'],
                'sport_type' => $payload['sport_type'],
                'sport_duree_minutes' => $payload['sport_duree_minutes'],
                'sport_intensite' => $payload['sport_intensite'],
                'sport_commentaire' => $payload['sport_commentaire'],
                'hydratation_litre' => $payload['hydratation_litre'],
                'id' => $id,
                'pid' => (int) $suivi['id_profil_sante'],
            ]);

            $gamif = SanteGamificationService::analyserEtSauvegarder($pdo, $id);
            if (empty($gamif['success'])) {
                $_SESSION['hb_sante_toast_warn'] = fo_t('health.form.error_gamification_failed');
            }

            if ($foSanteInline) {
                fo_sante_save_redirect(['notice' => 'suivi_updated']);
            }
            header('Location: user_health_space.php?notice=suivi_updated');
            exit;
        } catch (Throwable $e) {
            $errors[] = fo_t('health.form.error_save_failed');
        }
    }
}

$hydratation = (string) ($form['hydratation_litre'] ?? '');
$dateJour = (string) ($suivi['date_jour'] ?? '');
$dateLabel = '—';
if ($dateJour !== '') {
    $dateTs = strtotime($dateJour) ?: time();
    $dateLabel = fo_lang() === 'en'
        ? date('m/d/Y', $dateTs)
        : date('d/m/Y', $dateTs);
}
?>

<?php if (!$foSanteInline): ?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — <?php echo fo_e('health.form.page_edit_suivi'); ?></title>
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-original-views.css">
<?php endif; ?>

    <style>
        .sante-form-wrap {
            max-width: min(920px, 100%);
            width: 100%;
            margin: 0 auto;
            padding: 2rem clamp(1rem, 3vw, 2rem) 3.75rem;
            box-sizing: border-box;
            background: linear-gradient(180deg, #eef8f1 0%, #f4fbf7 40%, #f7fcf9 100%);
            min-height: calc(100vh - 120px);
            font-family: var(--hb-font-main, "Poppins", sans-serif);
        }

        .fo-sante-inline-form.sante-form-wrap {
            min-height: 0;
            padding: 0 0 0.5rem;
            background: transparent;
        }

        .sante-form-wrap h1 {
            text-align: center;
            font-size: clamp(1.75rem, 3vw, 2rem);
            font-weight: 700;
            color: #2C7E34;
            margin-bottom: 0.5rem;
        }

        .sante-form-subtitle {
            text-align: center;
            color: #5c6b62;
            margin-bottom: 1.65rem;
            font-size: 0.98rem;
        }

        .sante-form-card {
            background: #fff;
            border: 1px solid rgba(227, 235, 230, 0.95);
            border-radius: 20px;
            padding: clamp(2rem, 4vw, 2.65rem) clamp(1.85rem, 4vw, 2.75rem);
            box-shadow: 0 10px 36px rgba(15, 42, 28, 0.08), 0 4px 14px rgba(0, 0, 0, 0.05);
        }

        .sante-section-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 1.12rem;
            font-weight: 700;
            color: #2C7E34;
            margin: 1.4rem 0 1rem;
            padding-top: 1.1rem;
            border-top: 1px solid #eef2ef;
        }

        .sante-section-title:first-child {
            border-top: none;
            padding-top: 0;
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .sante-form-card label {
            font-weight: 500;
            display: block;
            margin-bottom: 10px;
            color: #1a1a1a;
            font-size: 1rem;
        }

        .sante-form-card input[type="number"],
        .sante-form-card input[type="text"],
        .sante-form-card select,
        .sante-form-card textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid #e3ebe6;
            margin-bottom: 6px;
            font-family: inherit;
            font-size: 1rem;
            box-sizing: border-box;
            background: #fff;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sante-form-card input:disabled {
            background: #f3f4f6;
            color: #4b5563;
        }

        .sante-form-card textarea {
            min-height: 95px;
            resize: vertical;
        }

        .sante-form-card input:focus,
        .sante-form-card select:focus,
        .sante-form-card textarea:focus {
            border-color: #2C7E34;
            box-shadow: 0 0 0 4px rgba(44, 126, 52, 0.10);
        }

        .radio-group {
            display: grid;
            gap: 0.5rem;
            background: #f8fbf9;
            border: 1px solid #e3ebe6;
            border-radius: 14px;
            padding: 0.95rem;
        }

        .radio-group label {
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0;
            color: #26352b;
        }

        .sante-form-card button[type="submit"] {
            width: 100%;
            margin-top: 16px;
            padding: 16px 18px;
            border: none;
            border-radius: 16px;
            background: #2C7E34;
            color: #fff;
            font-weight: 600;
            font-size: 1.08rem;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .sante-form-card button[type="submit"]:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .sante-back {
            display: inline-block;
            margin-top: 1rem;
            color: #2C7E34;
            font-weight: 500;
            text-decoration: none;
        }

        .sante-muted {
            font-size: 0.88rem;
            color: #5c6b62;
            margin-top: 6px;
            display: block;
        }

        .sante-alert {
            border-radius: 16px;
            padding: 1rem 1.1rem;
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
        }

        .sante-alert.error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
        }

        .sante-alert ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        .sport-card {
            background: linear-gradient(180deg, #f8fbf9 0%, #ffffff 100%);
            border: 1px solid #e3ebe6;
            border-radius: 18px;
            padding: 1.15rem;
        }

        .two-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 720px) {
            .two-cols {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
<?php if (!$foSanteInline): ?>
</head>

<body>
<?php
$nav_active = 'sante';
require __DIR__ . '/includes/nav_front.php';
?>
<?php endif; ?>

<main class="sante-form-wrap<?php echo $foSanteInline ? ' fo-sante-inline-form' : ''; ?>">
    <?php if (!$foSanteInline): ?>
    <h1><?php echo fo_e('health.form.edit_suivi_title'); ?></h1>
    <p class="sante-form-subtitle">
        <?php echo htmlspecialchars(sprintf(fo_t('health.form.edit_suivi_sub'), $dateLabel), ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <?php endif; ?>

    <div class="sante-form-card">
        <form method="post" action="editSuivi.php?id=<?= (int) $id ?>" id="suiviForm">
            <div class="sante-section-title">📅 <?php echo fo_e('health.form.section_date'); ?></div>

            <div class="form-group">
                <label><?php echo fo_e('health.form.date'); ?></label>
                <input type="text" value="<?= hb($dateLabel) ?>" disabled>
                <span class="sante-muted"><?php echo fo_e('health.form.date_not_editable_hint'); ?></span>
            </div>

            <div class="sante-section-title">🧍 <?php echo fo_e('health.form.section_daily'); ?></div>

            <div class="two-cols">
                <div class="form-group">
                    <label for="poids"><?php echo fo_e('health.form.weight_kg_short'); ?></label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="poids"
                        name="poids"
                        value="<?= hb($form['poids']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="calories"><?php echo fo_e('health.form.calories'); ?></label>
                    <input
                        type="number"
                        min="0"
                        id="calories"
                        name="calories"
                        value="<?= hb($form['calories']) ?>"
                    >
                </div>
            </div>

            <div class="two-cols">
                <div class="form-group">
                    <label for="sommeil_heures"><?php echo fo_e('health.form.sleep_hours'); ?></label>
                    <input
                        type="number"
                        step="0.1"
                        min="0"
                        max="24"
                        id="sommeil_heures"
                        name="sommeil_heures"
                        value="<?= hb($form['sommeil_heures']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="nbr_pas"><?php echo fo_e('health.form.steps'); ?></label>
                    <input
                        type="number"
                        min="0"
                        step="100"
                        id="nbr_pas"
                        name="nbr_pas"
                        value="<?= hb($form['nbr_pas']) ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label><?php echo fo_e('health.form.hydration'); ?></label>

                <div class="radio-group">
                    <label>
                        <input type="radio" name="hydratation_litre" value="moins_1L" <?= $hydratation === 'moins_1L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.less_1l'); ?>
                    </label>

                    <label>
                        <input type="radio" name="hydratation_litre" value="1_1.5L" <?= $hydratation === '1_1.5L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.between_1_1_5l'); ?>
                    </label>

                    <label>
                        <input type="radio" name="hydratation_litre" value="1.5_2L" <?= $hydratation === '1.5_2L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.between_1_5_2l'); ?>
                    </label>

                    <label>
                        <input type="radio" name="hydratation_litre" value="plus_2L" <?= $hydratation === 'plus_2L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.more_2l'); ?>
                    </label>
                </div>
            </div>

            <div class="sante-section-title">🏃 <?php echo fo_e('health.form.section_sport'); ?></div>

            <div class="sport-card">
                <div class="form-group">
                    <label for="sport_type"><?php echo fo_e('health.form.sport_type_label'); ?></label>

                    <select id="sport_type" name="sport_type">
                        <option value="aucune" <?= $form['sport_type'] === 'aucune' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_none'); ?></option>
                        <option value="marche" <?= $form['sport_type'] === 'marche' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_walk'); ?></option>
                        <option value="course" <?= $form['sport_type'] === 'course' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_run'); ?></option>
                        <option value="natation" <?= $form['sport_type'] === 'natation' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_swim'); ?></option>
                        <option value="danse" <?= $form['sport_type'] === 'danse' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_dance'); ?></option>
                        <option value="escalade" <?= $form['sport_type'] === 'escalade' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_climb'); ?></option>
                        <option value="velo" <?= $form['sport_type'] === 'velo' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_bike'); ?></option>
                        <option value="cardio" <?= $form['sport_type'] === 'cardio' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_cardio'); ?></option>
                        <option value="musculation" <?= $form['sport_type'] === 'musculation' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_gym'); ?></option>
                        <option value="yoga" <?= $form['sport_type'] === 'yoga' ? 'selected' : '' ?>><?php echo fo_e('health.sport_type_yoga'); ?></option>
                        <option value="autre" <?= $form['sport_type'] === 'autre' ? 'selected' : '' ?>><?php echo fo_e('health.form.sport_other_opt'); ?></option>
                    </select>
                </div>

                <div class="two-cols">
                    <div class="form-group">
                        <label for="sport_duree_minutes"><?php echo fo_e('health.form.sport_duration'); ?></label>

                        <input
                            type="number"
                            min="0"
                            max="600"
                            step="5"
                            id="sport_duree_minutes"
                            name="sport_duree_minutes"
                            value="<?= hb($form['sport_duree_minutes']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="sport_intensite"><?php echo fo_e('health.form.sport_intensity_label'); ?></label>

                        <select id="sport_intensite" name="sport_intensite">
                            <option value="aucune" <?= $form['sport_intensite'] === 'aucune' ? 'selected' : '' ?>><?php echo fo_e('health.form.intensity_none_opt'); ?></option>
                            <option value="faible" <?= $form['sport_intensite'] === 'faible' ? 'selected' : '' ?>><?php echo fo_e('health.form.intensity_low_opt'); ?></option>
                            <option value="moyenne" <?= $form['sport_intensite'] === 'moyenne' ? 'selected' : '' ?>><?php echo fo_e('health.form.intensity_medium_opt'); ?></option>
                            <option value="elevee" <?= $form['sport_intensite'] === 'elevee' ? 'selected' : '' ?>><?php echo fo_e('health.form.intensity_high_opt'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="sport_commentaire"><?php echo fo_e('health.form.sport_comment'); ?></label>

                    <textarea
                        id="sport_commentaire"
                        name="sport_commentaire"
                        placeholder="<?php echo fo_e('health.form.sport_comment_ph'); ?>"
                    ><?= hb($form['sport_commentaire']) ?></textarea>
                </div>
            </div>

            <button type="submit"><?php echo fo_e('health.form.submit_suivi_update'); ?></button>
        </form>
    </div>

    <?php if (!$foSanteInline): ?>
    <a class="sante-back" href="user_health_space.php"><?php echo fo_e('health.form.back'); ?></a>
    <?php endif; ?>
</main>

<script>
window.HB_SANTE_EDIT = <?= json_encode([
    'sportNonePh' => fo_t('health.form.sport_none_today_ph'),
    'sportCommentPh' => fo_t('health.form.sport_comment_ph'),
    'alertDuration' => fo_t('health.form.val.sport_duration_required'),
    'alertIntensity' => fo_t('health.form.val.sport_intensity_required'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const sportType = document.getElementById('sport_type');
const sportDuree = document.getElementById('sport_duree_minutes');
const sportIntensite = document.getElementById('sport_intensite');
const sportCommentaire = document.getElementById('sport_commentaire');
const santeEditI18n = window.HB_SANTE_EDIT || {};

function syncSportFields() {
    const isNone = sportType.value === 'aucune';

    if (isNone) {
        sportDuree.value = '0';
        sportIntensite.value = 'aucune';
        sportCommentaire.placeholder = santeEditI18n.sportNonePh || '';
    } else {
        if (sportDuree.value === '0') {
            sportDuree.value = '';
        }

        if (sportIntensite.value === 'aucune') {
            sportIntensite.value = 'faible';
        }

        sportCommentaire.placeholder = santeEditI18n.sportCommentPh || '';
    }

    sportDuree.readOnly = isNone;
}

sportType.addEventListener('change', syncSportFields);
syncSportFields();

document.getElementById('suiviForm').addEventListener('submit', function (event) {
    if (sportType.value !== 'aucune') {
        const duree = parseInt(sportDuree.value || '0', 10);

        if (duree <= 0) {
            event.preventDefault();
            alert(santeEditI18n.alertDuration || '');
            sportDuree.focus();
            return;
        }

        if (sportIntensite.value === 'aucune') {
            event.preventDefault();
            alert(santeEditI18n.alertIntensity || '');
            sportIntensite.focus();
        }
    }
});
</script>
<?php
require_once __DIR__ . '/includes/hb_action_toast.php';
hb_action_toast_render();
if (!empty($errors)) {
    $toastErr = implode(' ', $errors);
    ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.hbShowActionToast === 'function') {
        window.hbShowActionToast(<?php echo json_encode($toastErr, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 5000);
    }
});
</script>
<?php } ?>
<?php if (!$foSanteInline): ?>
</body>
</html>
<?php endif; ?>