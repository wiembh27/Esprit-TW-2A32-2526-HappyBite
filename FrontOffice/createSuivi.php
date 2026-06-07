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
fo_sante_redirect_if_standalone('create_suivi', 0);

$uid = sante_require_user_id();
$pdo = Database::getConnection();
fo_sante_db_ensure_schema($pdo);

$stProfil = $pdo->prepare('SELECT id FROM profil_sante WHERE id_utilisateur = :u LIMIT 1');
$stProfil->execute(['u' => $uid]);
$rowProfil = $stProfil->fetch(PDO::FETCH_ASSOC);
$idProfil = $rowProfil ? (int) $rowProfil['id'] : 0;

if ($idProfil < 1) {
    header('Location: user_health_space.php');
    exit;
}

function hb(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normaliser_date(?string $date): ?string
{
    $date = trim((string) $date);

    if ($date === '') {
        return null;
    }

    $d = DateTime::createFromFormat('Y-m-d', $date);

    if (!$d || $d->format('Y-m-d') !== $date) {
        return null;
    }

    return $date;
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

$today = date('Y-m-d');
$errors = [];

$form = [
    'date_jour' => $today,
    'poids' => '',
    'calories' => '',
    'sommeil_heures' => '',
    'nbr_pas' => '',
    'hydratation_litre' => '',
    'sport_type' => 'aucune',
    'sport_duree_minutes' => '0',
    'sport_intensite' => 'aucune',
    'sport_commentaire' => '',
];

$stLast = $pdo->prepare(
    'SELECT sj.* FROM suivi_journalier sj
     INNER JOIN profil_sante ps ON ps.id = sj.id_profil_sante
     WHERE ps.id_utilisateur = :id
     ORDER BY sj.date_jour DESC LIMIT 1'
);
$stLast->execute(['id' => $uid]);
$last = $stLast->fetch(PDO::FETCH_ASSOC) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateJour = normaliser_date($_POST['date_jour'] ?? null);

    $form = [
        'date_jour' => (string) ($_POST['date_jour'] ?? $today),
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

    if ($dateJour === null) {
        $errors[] = 'Veuillez choisir une date valide.';
    } elseif ($dateJour > $today) {
        $errors[] = 'Vous ne pouvez pas créer un suivi pour une date future.';
    }

    if ($form['poids'] !== '' && (float) str_replace(',', '.', $form['poids']) <= 0) {
        $errors[] = 'Le poids doit être supérieur à 0.';
    }

    if ($form['calories'] !== '' && (int) $form['calories'] < 0) {
        $errors[] = 'Les calories ne peuvent pas être négatives.';
    }

    if ($form['sommeil_heures'] !== '') {
        $sommeil = (float) str_replace(',', '.', $form['sommeil_heures']);

        if ($sommeil < 0 || $sommeil > 24) {
            $errors[] = 'Le sommeil doit être compris entre 0 et 24 heures.';
        }
    }

    if ($form['nbr_pas'] !== '' && (int) $form['nbr_pas'] < 0) {
        $errors[] = 'Le nombre de pas ne peut pas être négatif.';
    }

    if (!in_array($form['sport_type'], $allowedSportTypes, true)) {
        $errors[] = 'Le type de séance sportive est invalide.';
    }

    if (!in_array($form['sport_intensite'], $allowedIntensites, true)) {
        $errors[] = 'L’intensité sportive est invalide.';
    }

    $sportDuree = int_or_zero($form['sport_duree_minutes']);

    if ($sportDuree > 600) {
        $errors[] = 'La durée de sport semble trop élevée. Vérifiez la valeur saisie.';
    }

    if ($form['sport_type'] === 'aucune') {
        $sportDuree = 0;
        $form['sport_duree_minutes'] = '0';
        $form['sport_intensite'] = 'aucune';
    } else {
        if ($sportDuree <= 0) {
            $errors[] = 'Veuillez indiquer la durée de la séance sportive.';
        }

        if ($form['sport_intensite'] === 'aucune') {
            $errors[] = 'Veuillez choisir une intensité pour la séance sportive.';
        }
    }

    if ($form['hydratation_litre'] !== '' && !in_array($form['hydratation_litre'], $allowedHydratation, true)) {
        $errors[] = 'Le choix d’hydratation est invalide.';
    }

    if (empty($errors) && $dateJour !== null) {
        $payload = [
            'date_jour' => $dateJour,
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

        $check = $pdo->prepare(
            'SELECT id FROM suivi_journalier
             WHERE id_profil_sante = :p
             AND date_jour = :d
             LIMIT 1'
        );

        $check->execute([
            'p' => $idProfil,
            'd' => $payload['date_jour'],
        ]);

        $existing = $check->fetch(PDO::FETCH_ASSOC);
        $idSuivi = 0;

        try {
            if ($existing && isset($existing['id'])) {
                $idSuivi = (int) $existing['id'];

                $upd = $pdo->prepare(
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
                     AND id_profil_sante = :pid'
                );

                $upd->execute([
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
                    'id' => $idSuivi,
                    'pid' => $idProfil,
                ]);
            } else {
                $ins = $pdo->prepare(
                'INSERT INTO suivi_journalier
                    (
                        id_profil_sante,
                        date_jour,
                        poids,
                        calories,
                        sommeil_heures,
                        nbr_pas,
                        nbr_activites_sport,
                        sport_type,
                        sport_duree_minutes,
                        sport_intensite,
                        sport_commentaire,
                        hydratation_litre,
                        analyse_resultat,
                        points_resultat,
                        analyse_commentaire,
                        analysed_at
                    )
                 VALUES
                    (
                        :id_profil_sante,
                        :date_jour,
                        :poids,
                        :calories,
                        :sommeil_heures,
                        :nbr_pas,
                        :nbr_activites_sport,
                        :sport_type,
                        :sport_duree_minutes,
                        :sport_intensite,
                        :sport_commentaire,
                        :hydratation_litre,
                        NULL,
                        0,
                        NULL,
                        NULL
                    )'
            );

                $ins->execute([
                    'id_profil_sante' => $idProfil,
                    'date_jour' => $payload['date_jour'],
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
                ]);

                $idSuivi = (int) $pdo->lastInsertId();
            }

            if ($idSuivi < 1) {
                $errors[] = fo_t('health.form.error_save_failed');
            }
        } catch (Throwable $e) {
            $errors[] = fo_t('health.form.error_save_failed');
        }

        if ($errors === [] && $idSuivi > 0) {
            $gamif = SanteGamificationService::analyserEtSauvegarder($pdo, $idSuivi);
            $notice = ($existing && isset($existing['id'])) ? 'suivi_updated' : 'suivi_saved';
            if (empty($gamif['success'])) {
                $_SESSION['hb_sante_toast_warn'] = fo_t('health.form.error_gamification_failed');
            }
            if ($foSanteInline) {
                fo_sante_save_redirect(['notice' => $notice]);
            }
            header('Location: user_health_space.php?notice=' . $notice);
            exit;
        }
    }
}

$h = (string) ($last['hydratation_litre'] ?? '');

if (!$foSanteInline):
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — <?php echo fo_e('health.form.page_create_suivi'); ?></title>
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
        .sante-form-card input[type="date"],
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
    <h1><?php echo fo_e('health.form.create_suivi_title'); ?></h1>
    <p class="sante-form-subtitle"><?php echo fo_e('health.form.create_suivi_sub'); ?></p>
    <?php endif; ?>

    <div class="sante-form-card">
        <form method="post" action="createSuivi.php" id="suiviForm">
            <div class="sante-section-title">📅 <?php echo fo_e('health.form.section_date'); ?></div>

            <div class="form-group">
                <label for="date_jour"><?php echo fo_e('health.form.date'); ?></label>
                <input
                    type="date"
                    id="date_jour"
                    name="date_jour"
                    value="<?= hb($form['date_jour']) ?>"
                    max="<?= hb($today) ?>"
                    required
                >
                <span class="sante-muted"><?php echo fo_e('health.form.date_future_hint'); ?></span>
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
                        placeholder="<?= hb(sprintf(fo_t('health.form.last_value'), (string) ($last['poids'] ?? ''))) ?>"
                        data-last="<?= hb((string) ($last['poids'] ?? '')) ?>"
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
                        placeholder="<?= hb(sprintf(fo_t('health.form.last_value'), (string) ($last['calories'] ?? ''))) ?>"
                        data-last="<?= hb((string) ($last['calories'] ?? '')) ?>"
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
                        placeholder="<?= hb(sprintf(fo_t('health.form.last_value'), (string) ($last['sommeil_heures'] ?? ''))) ?>"
                        data-last="<?= hb((string) ($last['sommeil_heures'] ?? '')) ?>"
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
                        placeholder="<?= hb(sprintf(fo_t('health.form.last_value'), (string) ($last['nbr_pas'] ?? ''))) ?>"
                        data-last="<?= hb((string) ($last['nbr_pas'] ?? '')) ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label><?php echo fo_e('health.form.hydration'); ?></label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="hydratation_litre" value="moins_1L" <?= $form['hydratation_litre'] === 'moins_1L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.less_1l'); ?>
                    </label>
                    <label>
                        <input type="radio" name="hydratation_litre" value="1_1.5L" <?= $form['hydratation_litre'] === '1_1.5L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.between_1_1_5l'); ?>
                    </label>
                    <label>
                        <input type="radio" name="hydratation_litre" value="1.5_2L" <?= $form['hydratation_litre'] === '1.5_2L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.between_1_5_2l'); ?>
                    </label>
                    <label>
                        <input type="radio" name="hydratation_litre" value="plus_2L" <?= $form['hydratation_litre'] === 'plus_2L' ? 'checked' : '' ?>>
                        <?php echo fo_e('health.hydration.more_2l'); ?>
                    </label>
                </div>

                <?php if ($h !== ''): ?>
                    <span class="sante-muted"><?php echo htmlspecialchars(sprintf(fo_t('health.form.last_choice'), $h), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
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

            <button type="submit"><?php echo fo_e('health.form.submit_suivi'); ?></button>
        </form>
    </div>

    <?php if (!$foSanteInline): ?>
    <a class="sante-back" href="user_health_space.php"><?php echo fo_e('health.form.back'); ?></a>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('input[data-last]').forEach(function (input) {
    input.addEventListener('focus', function () {
        if (this.value === '' && this.dataset.last) {
            this.value = this.dataset.last;
        }
    });
});

const sportType = document.getElementById('sport_type');
const sportDuree = document.getElementById('sport_duree_minutes');
const sportIntensite = document.getElementById('sport_intensite');
const sportCommentaire = document.getElementById('sport_commentaire');

function syncSportFields() {
    const isNone = sportType.value === 'aucune';

    if (isNone) {
        sportDuree.value = '0';
        sportIntensite.value = 'aucune';
        sportCommentaire.placeholder = 'Aucune séance aujourd’hui.';
    } else {
        if (sportDuree.value === '0') {
            sportDuree.value = '';
        }

        if (sportIntensite.value === 'aucune') {
            sportIntensite.value = 'faible';
        }

        sportCommentaire.placeholder = 'Exemple : séance agréable, effort intense, marche tranquille...';
    }

    sportDuree.readOnly = isNone;
}

sportType.addEventListener('change', syncSportFields);
syncSportFields();

document.getElementById('suiviForm').addEventListener('submit', function (event) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const selectedDateValue = document.getElementById('date_jour').value;
    const selectedDate = new Date(selectedDateValue + 'T00:00:00');

    if (selectedDate > today) {
        event.preventDefault();
        alert('Vous ne pouvez pas créer un suivi pour une date future.');
        return;
    }

    if (sportType.value !== 'aucune') {
        const duree = parseInt(sportDuree.value || '0', 10);

        if (duree <= 0) {
            event.preventDefault();
            alert('Veuillez indiquer la durée de la séance sportive.');
            sportDuree.focus();
            return;
        }

        if (sportIntensite.value === 'aucune') {
            event.preventDefault();
            alert('Veuillez choisir une intensité pour la séance sportive.');
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