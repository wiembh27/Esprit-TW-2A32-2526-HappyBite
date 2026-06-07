<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/FrontOffice/includes/sante_conseil_i18n.php';

final class SanteGamificationService
{
    public static function analyser(array $profil, array $suivi, ?string $locale = null): array
    {
        $objectif = self::clean((string) ($profil['objectif'] ?? ''));
        $objectifType = self::objectifType($objectif);

        $allergenes = self::toArray($profil['allergenes'] ?? []);
        $carences = self::toArray($profil['carences'] ?? []);
        $maladies = self::toArray($profil['maladies'] ?? []);

        $calories = self::floatValue($suivi['calories'] ?? null);
        $sommeil = self::floatValue($suivi['sommeil_heures'] ?? null);
        $pas = self::intValue($suivi['nbr_pas'] ?? null);

        $hydratationRaw = (string) ($suivi['hydratation_litre'] ?? '');
        $hydratation = self::normalizeHydratation($hydratationRaw);

        $sportType = self::clean((string) ($suivi['sport_type'] ?? 'aucune'));
        $sportDuree = self::intValue($suivi['sport_duree_minutes'] ?? 0);
        $sportIntensite = self::clean((string) ($suivi['sport_intensite'] ?? 'aucune'));

        if ($sportType === '') {
            $sportType = 'aucune';
        }

        if ($sportType === 'aucune') {
            $sportDuree = 0;
            $sportIntensite = 'aucune';
        }

        $hasCalories = $calories > 0;
        $hasHydratation = $hydratation !== 'inconnue';
        $hasSport = $sportType !== 'aucune' && $sportDuree > 0;

        $hasGoodWalk = $pas >= 7000;
        $hasLowMovement = $pas < 3000 && !$hasSport;
        $hasMediumMovement = $pas >= 3000 && $pas < 7000;

        $hasHighCalories = $calories >= 2800;
        $hasVeryHighCalories = $calories >= 3500;
        $hasLowCalories = $calories > 0 && $calories < 1200;

        $hasLowSleep = $sommeil > 0 && $sommeil < 6;
        $hasGoodSleep = $sommeil >= 7 && $sommeil <= 9;

        $hasIntenseSport = $hasSport && $sportIntensite === 'elevee';

        $score = 0;
        $conseils = [];

        if (!$hasCalories) {
            $conseils[] = sante_conseil_t('calories_imprecise', $locale);
        } else {
            if ($objectifType === 'perte') {
                if ($hasVeryHighCalories && $hasLowMovement) {
                    $score -= 3;
                    $conseils[] = sante_conseil_t('perte_high_low_move', $locale);
                } elseif ($hasHighCalories && $hasSport) {
                    $score -= 1;
                    $conseils[] = sante_conseil_t('perte_high_sport', $locale);
                } elseif ($hasHighCalories && $hasGoodWalk) {
                    $score += 0;
                    $conseils[] = sante_conseil_t('perte_high_walk', $locale);
                } elseif ($calories >= 1600 && $calories < 2800 && ($hasSport || $pas >= 5000)) {
                    $score += 2;
                    $conseils[] = sante_conseil_t('perte_ok', $locale);
                } elseif ($hasLowCalories) {
                    $score -= 1;
                    $conseils[] = sante_conseil_t('perte_low_cal', $locale);
                } else {
                    $score += 1;
                    $conseils[] = sante_conseil_t('perte_ok2', $locale);
                }
            } elseif ($objectifType === 'prise') {
                if ($hasLowCalories && ($hasSport || $hasGoodWalk)) {
                    $score -= 2;
                    $conseils[] = sante_conseil_t('prise_low_high_act', $locale);
                } elseif ($calories >= 2200 && ($hasSport || $pas >= 4000)) {
                    $score += 2;
                    $conseils[] = sante_conseil_t('prise_ok', $locale);
                } elseif ($calories < 2000) {
                    $score -= 1;
                    $conseils[] = sante_conseil_t('prise_low', $locale);
                } else {
                    $score += 1;
                    $conseils[] = sante_conseil_t('prise_ok2', $locale);
                }
            } else {
                if ($hasVeryHighCalories && $hasLowMovement) {
                    $score -= 2;
                    $conseils[] = sante_conseil_t('gen_high_low', $locale);
                } elseif ($hasHighCalories && ($hasSport || $hasGoodWalk)) {
                    $score += 0;
                    $conseils[] = sante_conseil_t('gen_high_comp', $locale);
                } elseif ($calories < 3000 && ($hasSport || $pas >= 5000)) {
                    $score += 2;
                    $conseils[] = sante_conseil_t('gen_balanced', $locale);
                } else {
                    $score += 1;
                    $conseils[] = sante_conseil_t('gen_ok', $locale);
                }
            }
        }

        if (!$hasHydratation) {
            $conseils[] = sante_conseil_t('hyd_unknown', $locale);
        } elseif ($hydratation === 'moins_1l') {
            $score -= 2;

            if ($hasSport) {
                $conseils[] = sante_conseil_t('hyd_low_sport', $locale);
            } else {
                $conseils[] = sante_conseil_t('hyd_low', $locale);
            }
        } elseif ($hydratation === '1_1_5l') {
            if ($hasIntenseSport) {
                $score -= 1;
                $conseils[] = sante_conseil_t('hyd_mid_intense', $locale);
            } else {
                $score += 0;
                $conseils[] = sante_conseil_t('hyd_mid', $locale);
            }
        } elseif ($hydratation === '1_5_2l') {
            $score += 1;
            $conseils[] = sante_conseil_t('hyd_good', $locale);
        } elseif ($hydratation === 'plus_2l') {
            $score += 1;
            $conseils[] = sante_conseil_t('hyd_high', $locale);
        }

        if ($hasIntenseSport && $hasLowSleep) {
            $score -= 2;
            $conseils[] = sante_conseil_t('recover_bad', $locale);
        } elseif ($hasSport && $hasGoodSleep) {
            $score += 1;
            $conseils[] = sante_conseil_t('recover_good', $locale);
        } elseif ($hasSport && $sommeil > 0 && $sommeil < 7) {
            $score -= 1;
            $conseils[] = sante_conseil_t('sport_sleep_low', $locale);
        }

        if (!$hasSport && $hasGoodWalk) {
            $score += 1;
            $conseils[] = sante_conseil_t('walk_good', $locale);
        } elseif (!$hasSport && $hasMediumMovement) {
            $score += 0;
            $conseils[] = sante_conseil_t('move_medium', $locale);
        } elseif ($hasLowMovement) {
            $score -= 1;
            $conseils[] = sante_conseil_t('move_low', $locale);
        } elseif ($hasSport && $sportDuree >= 20 && !$hasIntenseSport) {
            $score += 1;
            $conseils[] = sante_conseil_t('sport_good', $locale);
        } elseif ($hasSport && $sportDuree < 20) {
            $score += 0;
            $conseils[] = sante_conseil_t('sport_short', $locale);
        }

        if ($sommeil <= 0) {
            $conseils[] = sante_conseil_t('sleep_missing', $locale);
        } elseif ($hasLowSleep && !$hasIntenseSport) {
            $score -= 1;
            $conseils[] = sante_conseil_t('sleep_low', $locale);
        } elseif ($hasGoodSleep) {
            $score += 1;
            $conseils[] = sante_conseil_t('sleep_good', $locale);
        } elseif ($sommeil > 9.5) {
            $conseils[] = sante_conseil_t('sleep_high', $locale);
        }

        if (!empty($allergenes)) {
            $conseils[] = sprintf(sante_conseil_t('allergens', $locale), implode(', ', $allergenes));
        }

        if (!empty($carences)) {
            $conseils[] = sprintf(sante_conseil_t('deficiencies', $locale), implode(', ', $carences));
        }

        if (!empty($maladies)) {
            $conseils[] = sprintf(sante_conseil_t('diseases', $locale), implode(', ', $maladies));
        }

        if ($score >= 2) {
            $points = 10;
            $resultat = 'bonne_journee';
            $titre = sante_conseil_t('titre_good', $locale);
        } else {
            $points = -10;
            $resultat = 'journee_a_ameliorer';
            $titre = sante_conseil_t('titre_improve', $locale);
        }

        $lang = $locale ?? (function_exists('fo_lang') ? fo_lang() : 'fr');
        $motivationLabel = $lang === 'en' ? 'Motivation: ' : 'Motivation : ';

        $commentaire = $titre . "\n\n"
            . implode("\n\n", array_values(array_unique($conseils)))
            . "\n\n" . $motivationLabel . self::motivation($resultat, $locale);

        return [
            'points' => $points,
            'resultat' => $resultat,
            'commentaire' => $commentaire,
        ];
    }

    public static function analyserEtSauvegarder(PDO $pdo, int $idSuivi): array
    {
        $st = $pdo->prepare(
            'SELECT
                sj.id AS suivi_id,
                sj.id_profil_sante,
                sj.calories,
                sj.sommeil_heures,
                sj.nbr_pas,
                sj.hydratation_litre,
                sj.sport_type,
                sj.sport_duree_minutes,
                sj.sport_intensite,
                ps.id AS profil_id,
                ps.objectif,
                ps.allergenes,
                ps.carences,
                ps.maladies
             FROM suivi_journalier sj
             INNER JOIN profil_sante ps ON ps.id = sj.id_profil_sante
             WHERE sj.id = :id
             LIMIT 1'
        );

        $st->execute(['id' => $idSuivi]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'success' => false,
                'points' => 0,
                'resultat' => 'introuvable',
                'commentaire' => 'Suivi introuvable.',
            ];
        }

        $idProfil = (int) ($row['id_profil_sante'] ?? 0);

        $profil = [
            'objectif' => $row['objectif'] ?? '',
            'allergenes' => $row['allergenes'] ?? [],
            'carences' => $row['carences'] ?? [],
            'maladies' => $row['maladies'] ?? [],
        ];

        $suivi = [
            'calories' => $row['calories'] ?? null,
            'sommeil_heures' => $row['sommeil_heures'] ?? null,
            'nbr_pas' => $row['nbr_pas'] ?? null,
            'hydratation_litre' => $row['hydratation_litre'] ?? '',
            'sport_type' => $row['sport_type'] ?? 'aucune',
            'sport_duree_minutes' => $row['sport_duree_minutes'] ?? 0,
            'sport_intensite' => $row['sport_intensite'] ?? 'aucune',
        ];

        $analyse = self::analyser($profil, $suivi, null);

        $pdo->beginTransaction();

        try {
            $updSuivi = $pdo->prepare(
                'UPDATE suivi_journalier
                 SET analyse_resultat = :analyse_resultat,
                     points_resultat = :points_resultat,
                     analyse_commentaire = :analyse_commentaire,
                     analysed_at = NOW()
                 WHERE id = :id
                 LIMIT 1'
            );

            $updSuivi->execute([
                'analyse_resultat' => $analyse['resultat'],
                'points_resultat' => $analyse['points'],
                'analyse_commentaire' => $analyse['commentaire'],
                'id' => $idSuivi,
            ]);

            self::recalculerPointsProfil($pdo, $idProfil);

            $pdo->commit();

            return [
                'success' => true,
                'points' => $analyse['points'],
                'resultat' => $analyse['resultat'],
                'commentaire' => $analyse['commentaire'],
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();

            return [
                'success' => false,
                'points' => 0,
                'resultat' => 'erreur',
                'commentaire' => 'Erreur pendant le calcul automatique des points.',
            ];
        }
    }

    public static function recalculerPointsProfil(PDO $pdo, int $idProfil): void
    {
        if ($idProfil < 1) {
            return;
        }

        $userId = self::resolveUtilisateurIdFromProfil($pdo, $idProfil);
        $totalSuivi = self::calculerPointsSuivi($pdo, $idProfil);
        $totalBonus = $userId > 0 ? self::calculerPointsBonusExternes($pdo, $userId) : 0;

        $upd = $pdo->prepare(
            'UPDATE profil_sante
             SET points = :points
             WHERE id = :id
             LIMIT 1'
        );

        $upd->execute([
            'points' => $totalSuivi + $totalBonus,
            'id' => $idProfil,
        ]);
    }

    /**
     * Somme des points gagnés/perdus via le suivi journalier (+10 / -10 par jour).
     */
    public static function calculerPointsSuivi(PDO $pdo, int $idProfil): int
    {
        if ($idProfil < 1) {
            return 0;
        }

        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(points_resultat), 0)
             FROM suivi_journalier
             WHERE id_profil_sante = :id'
        );

        $st->execute(['id' => $idProfil]);

        return (int) $st->fetchColumn();
    }

    /**
     * Points hors suivi : roue de fortune (table recompense) + bonus Top 1 challenge.
     */
    public static function calculerPointsBonusExternes(PDO $pdo, int $userId): int
    {
        if ($userId < 1) {
            return 0;
        }

        return self::calculerPointsBonusRoue($pdo, $userId)
            + self::calculerPointsBonusTop1($pdo, $userId);
    }

    private static function resolveUtilisateurIdFromProfil(PDO $pdo, int $idProfil): int
    {
        $st = $pdo->prepare(
            'SELECT id_utilisateur
             FROM profil_sante
             WHERE id = :id
             LIMIT 1'
        );

        $st->execute(['id' => $idProfil]);

        return (int) ($st->fetchColumn() ?: 0);
    }

    private static function calculerPointsBonusRoue(PDO $pdo, int $userId): int
    {
        if (!self::tableExists($pdo, 'recompense')) {
            return 0;
        }

        try {
            $st = $pdo->prepare(
                "SELECT COALESCE(SUM(
                    CASE
                        WHEN LOWER(COALESCE(typeGain, '')) = 'points'
                            THEN COALESCE(pointsGagnes, 0) - COALESCE(pointsUtilises, 100)
                        ELSE -COALESCE(pointsUtilises, 100)
                    END
                ), 0)
                 FROM recompense
                 WHERE clientId = :userId"
            );

            $st->execute(['userId' => $userId]);

            return (int) $st->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function calculerPointsBonusTop1(PDO $pdo, int $userId): int
    {
        if (!self::tableExists($pdo, 'participation_challenge')) {
            return 0;
        }

        try {
            $st = $pdo->prepare(
                'SELECT COALESCE(SUM(
                    CASE WHEN bonus_top1_given = 1 THEN 20 ELSE 0 END
                ), 0)
                 FROM participation_challenge
                 WHERE clientId = :userId'
            );

            $st->execute(['userId' => $userId]);

            return (int) $st->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        static $cache = [];

        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        try {
            $st = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                 AND table_name = :table'
            );

            $st->execute(['table' => $table]);
            $cache[$table] = ((int) $st->fetchColumn()) > 0;
        } catch (Throwable $e) {
            $cache[$table] = false;
        }

        return $cache[$table];
    }

    private static function objectifType(string $objectif): string
    {
        $objectif = self::clean($objectif);

        if (self::contains($objectif, ['perte', 'perdre', 'maigrir', 'mincir', 'diminuer'])) {
            return 'perte';
        }

        if (self::contains($objectif, ['prise', 'prendre', 'masse', 'muscle', 'grossir'])) {
            return 'prise';
        }

        if (self::contains($objectif, ['maintien', 'maintenir', 'stable', 'équilibre', 'equilibre'])) {
            return 'maintien';
        }

        return 'general';
    }

    private static function normalizeHydratation(string $value): string
    {
        $value = self::clean($value);
        $value = str_replace(',', '.', $value);

        if (in_array($value, ['moins_1l', 'moins_de_1l', '<1l', '0_1l'], true)) {
            return 'moins_1l';
        }

        if (in_array($value, ['1_1.5l', '1_1_5l', '1l_1.5l', '1l_1_5l'], true)) {
            return '1_1_5l';
        }

        if (in_array($value, ['1.5_2l', '1_5_2l', '1.5l_2l', '1_5l_2l'], true)) {
            return '1_5_2l';
        }

        if (in_array($value, ['plus_2l', 'plus_de_2l', '>2l', '2l_plus'], true)) {
            return 'plus_2l';
        }

        return 'inconnue';
    }

    private static function motivation(string $resultat, ?string $locale = null): string
    {
        if ($resultat === 'bonne_journee') {
            return sante_conseil_t('motivation_good', $locale);
        }

        return sante_conseil_t('motivation_improve', $locale);
    }

    private static function clean(string $value): string
    {
        return trim(mb_strtolower($value));
    }

    private static function contains(string $text, array $words): bool
    {
        foreach ($words as $word) {
            if ($word !== '' && mb_strpos($text, self::clean((string) $word)) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function floatValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    private static function intValue(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return max(0, (int) $value);
    }

    private static function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(
                array_filter(
                    array_map(static fn($v): string => trim((string) $v), $value)
                )
            );
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(
                    array_filter(
                        array_map(static fn($v): string => trim((string) $v), $decoded)
                    )
                );
            }

            if (trim($value) !== '') {
                return [trim($value)];
            }
        }

        return [];
    }
}