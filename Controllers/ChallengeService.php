<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../FrontOffice/includes/panier_session.php';
require_once __DIR__ . '/RecetteController.php';

/**
 * Service métier pour les challenges :
 * - tirage du challenge du jour
 * - classement / bonus Top 1
 * - points santé
 * - roue de la fortune
 */
class ChallengeService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `challenge` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `titre` VARCHAR(255) NOT NULL,
              `description` TEXT NOT NULL,
              `image` VARCHAR(255) DEFAULT NULL,
              `statut` ENUM('disponible','selectionne','termine') NOT NULL DEFAULT 'disponible',
              `dateCreation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `dateSelection` DATE DEFAULT NULL,
              `nutritionnisteId` INT(11) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `participation_challenge` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `clientId` INT(11) NOT NULL,
              `challengeId` INT(11) NOT NULL,
              `photo` VARCHAR(255) DEFAULT NULL,
              `description` TEXT DEFAULT NULL,
              `statutValidationIA` ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
              `nombreLikes` INT(11) NOT NULL DEFAULT 0,
              `dateParticipation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_participation` (`clientId`, `challengeId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `like_participation` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `participationId` INT(11) NOT NULL,
              `userId` INT(11) NOT NULL,
              `dateLike` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_like_participation` (`participationId`, `userId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `produit_roue` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `nomProduit` VARCHAR(255) NOT NULL,
              `image` VARCHAR(255) DEFAULT NULL,
              `actif` TINYINT(1) NOT NULL DEFAULT 1,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `recompense` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `clientId` INT(11) NOT NULL,
              `produitRoueId` INT(11) NOT NULL DEFAULT 0,
              `typeGain` VARCHAR(50) DEFAULT NULL,
              `nomGain` VARCHAR(255) DEFAULT NULL,
              `pointsUtilises` INT(11) NOT NULL DEFAULT 300,
              `pointsGagnes` INT(11) NOT NULL DEFAULT 0,
              `dateGain` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `statut` ENUM('en_attente','utilisee') NOT NULL DEFAULT 'en_attente',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->addColumnIfMissing(
            'profil_sante',
            'points',
            'ALTER TABLE profil_sante ADD COLUMN `points` INT(11) NOT NULL DEFAULT 0'
        );

        $this->addColumnIfMissing(
            'challenge',
            'regle_ia',
            'ALTER TABLE challenge ADD COLUMN `regle_ia` TEXT NULL'
        );

        $this->addColumnIfMissing(
            'participation_challenge',
            'bonus_top1_given',
            'ALTER TABLE participation_challenge ADD COLUMN `bonus_top1_given` TINYINT(1) NOT NULL DEFAULT 0'
        );

        $this->addColumnIfMissing(
            'participation_challenge',
            'validation_ai_message',
            'ALTER TABLE participation_challenge ADD COLUMN `validation_ai_message` TEXT NULL'
        );

        $this->addColumnIfMissing(
            'participation_challenge',
            'validation_ai_score',
            'ALTER TABLE participation_challenge ADD COLUMN `validation_ai_score` INT NULL'
        );

        $this->addColumnIfMissing(
            'recompense',
            'typeGain',
            'ALTER TABLE recompense ADD COLUMN `typeGain` VARCHAR(50) DEFAULT NULL'
        );

        $this->addColumnIfMissing(
            'recompense',
            'nomGain',
            'ALTER TABLE recompense ADD COLUMN `nomGain` VARCHAR(255) DEFAULT NULL'
        );

        $this->addColumnIfMissing(
            'recompense',
            'pointsGagnes',
            'ALTER TABLE recompense ADD COLUMN `pointsGagnes` INT(11) NOT NULL DEFAULT 0'
        );

        $this->seedProduitsRoue();
    }

    private function addColumnIfMissing(string $table, string $column, string $alterSql): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                 AND table_name = :table
                 AND column_name = :column'
            );

            $stmt->execute([
                'table' => $table,
                'column' => $column,
            ]);

            if ((int) $stmt->fetchColumn() === 0) {
                $this->pdo->exec($alterSql);
            }
        } catch (Throwable $e) {
            // On ne bloque pas l'application si MySQL refuse un ALTER déjà existant.
        }
    }

    private function seedProduitsRoue(): void
    {
        try {
            $this->pdo->exec('DELETE FROM produit_roue');

            $stmt = $this->pdo->prepare(
                'INSERT INTO produit_roue (nomProduit, actif) VALUES (:nom, 1)'
            );

            foreach ($this->getFixedWheelLabels() as $label) {
                $stmt->execute(['nom' => $label]);
            }
        } catch (Throwable $e) {
            // Rien.
        }
    }

    private function getFixedWheelLabels(): array
    {
        return [
            'Pomme gratuite',
            'Brocoli gratuit',
            'Salade de fruit gratuite',
            '+10 points santé',
            '-10 points santé',
        ];
    }

    // ----------------------------------------------------------------
    // Challenge du jour
    // ----------------------------------------------------------------

    public function getChallengeduJour(): ?array
    {
        $today = date('Y-m-d');

        $stmt = $this->pdo->prepare(
            "SELECT c.*, u.prenom AS nutri_prenom, u.nom AS nutri_nom
             FROM challenge c
             LEFT JOIN utilisateur u ON c.nutritionnisteId = u.id_utilisateur
             WHERE c.statut = 'selectionne'
             AND c.dateSelection = :today
             LIMIT 1"
        );

        $stmt->execute(['today' => $today]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($challenge) ? $challenge : null;
    }

    /**
     * Challenge sélectionné dont la période n'est pas encore terminée (fin = lendemain 9h00 locale).
     */
    public function getActiveSelectedChallenge(): ?array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT c.*, u.prenom AS nutri_prenom, u.nom AS nutri_nom
                 FROM challenge c
                 LEFT JOIN utilisateur u ON c.nutritionnisteId = u.id_utilisateur
                 WHERE c.statut = 'selectionne'
                 AND c.dateSelection IS NOT NULL
                 ORDER BY c.dateSelection DESC, c.dateCreation DESC"
            );

            $now = new DateTimeImmutable();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!is_array($row)) {
                    continue;
                }

                $sel = trim((string) ($row['dateSelection'] ?? ''));
                if ($sel === '') {
                    continue;
                }

                $endAt = (new DateTimeImmutable($sel))
                    ->setTime(9, 0, 0)
                    ->modify('+1 day');

                if ($now < $endAt) {
                    return $row;
                }
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    private function terminateEndedSelectedChallenges(): void
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT id, dateSelection
                 FROM challenge
                 WHERE statut = 'selectionne'
                 AND dateSelection IS NOT NULL"
            );

            $now = new DateTimeImmutable();
            $upd = $this->pdo->prepare(
                "UPDATE challenge SET statut = 'termine' WHERE id = :id"
            );

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!is_array($row)) {
                    continue;
                }

                $sel = trim((string) ($row['dateSelection'] ?? ''));
                if ($sel === '') {
                    continue;
                }

                $endAt = (new DateTimeImmutable($sel))
                    ->setTime(9, 0, 0)
                    ->modify('+1 day');

                if ($now >= $endAt) {
                    $upd->execute(['id' => (int) ($row['id'] ?? 0)]);
                }
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function tirerChallengeduJour(): ?array
    {
        $active = $this->getActiveSelectedChallenge();
        if ($active !== null) {
            return $active;
        }

        $existing = $this->getChallengeduJour();

        if ($existing !== null) {
            return $existing;
        }

        $this->terminateEndedSelectedChallenges();

        $stmt = $this->pdo->query(
            "SELECT id
             FROM challenge
             WHERE statut = 'disponible'
             ORDER BY RAND()
             LIMIT 1"
        );

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $today = date('Y-m-d');

        $upd = $this->pdo->prepare(
            "UPDATE challenge
             SET statut = 'selectionne',
                 dateSelection = :today
             WHERE id = :id"
        );

        $upd->execute([
            'today' => $today,
            'id' => (int) $row['id'],
        ]);

        return $this->getChallengeduJour();
    }

    /**
     * Dernier challenge terminé (jour précédent ou statut terminé).
     */
    public function getLastEndedChallenge(): ?array
    {
        try {
            $pk = 'id_utilisateur';
            $stmt = $this->pdo->query(
                "SELECT c.*, u.prenom AS nutri_prenom, u.nom AS nutri_nom
                 FROM challenge c
                 LEFT JOIN utilisateur u ON c.nutritionnisteId = u.`{$pk}`
                 WHERE (
                    c.statut = 'termine'
                    OR (c.statut = 'selectionne' AND c.dateSelection IS NOT NULL AND c.dateSelection < CURDATE())
                 )
                 ORDER BY c.dateSelection DESC, c.dateCreation DESC
                 LIMIT 1"
            );

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    // ----------------------------------------------------------------
    // Points santé
    // ----------------------------------------------------------------

    public function getPointsClient(int $clientId): int
    {
        if ($clientId < 1) {
            return 0;
        }

        $this->ensureProfilSanteForClient($clientId);

        $stmt = $this->pdo->prepare(
            'SELECT points
             FROM profil_sante
             WHERE id_utilisateur = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $clientId]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function ensureProfilSanteForClient(int $clientId): void
    {
        if ($clientId < 1) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM profil_sante
             WHERE id_utilisateur = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $clientId]);

        if ($stmt->fetchColumn()) {
            return;
        }

        $ins = $this->pdo->prepare(
            'INSERT INTO profil_sante (id_utilisateur, points)
             VALUES (:id, 0)'
        );
        $ins->execute(['id' => $clientId]);
    }

    public function ajouterPoints(int $clientId, int $points): bool
    {
        if ($clientId < 1 || $points === 0) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE profil_sante
             SET points = points + :points
             WHERE id_utilisateur = :id
             LIMIT 1'
        );

        return $stmt->execute([
            'points' => $points,
            'id' => $clientId,
        ]);
    }

    public function modifierPointsRoue(int $clientId, int $delta): bool
    {
        if ($clientId < 1) {
            return false;
        }

        $this->ensureProfilSanteForClient($clientId);

        $stmt = $this->pdo->prepare(
            'UPDATE profil_sante
             SET points = points + :delta
             WHERE id_utilisateur = :id
             LIMIT 1'
        );

        return $stmt->execute([
            'delta' => $delta,
            'id' => $clientId,
        ]);
    }

    private function appliquerCoutEtBonusPoints(int $clientId, array $gain): bool
    {
        if (!$this->modifierPointsRoue($clientId, -100)) {
            return false;
        }

        if ((string) ($gain['type'] ?? '') === 'points') {
            $bonus = max(0, (int) ($gain['points_bonus'] ?? 0));

            if ($bonus > 0 && !$this->modifierPointsRoue($clientId, $bonus)) {
                return false;
            }
        }

        return true;
    }

    private function resolveRecompenseProduitRoueId(array $gain): int
    {
        $type = (string) ($gain['type'] ?? '');

        if ($type === 'produit') {
            return (int) ($gain['produit']['id_produit'] ?? 0);
        }

        if ($type === 'recette') {
            return (int) ($gain['recette']['id_recette'] ?? 0);
        }

        return 0;
    }

    // ----------------------------------------------------------------
    // Top 1 challenge
    // ----------------------------------------------------------------

    public function calculerEtAttribuerGagnant(int $challengeId): ?array
    {
        if ($challengeId < 1) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT pc.*
             FROM participation_challenge pc
             WHERE pc.challengeId = :challengeId
             AND pc.statutValidationIA = 'valide'
             ORDER BY pc.nombreLikes DESC, pc.dateParticipation ASC
             LIMIT 1"
        );

        $stmt->execute([
            'challengeId' => $challengeId,
        ]);

        $top = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$top || (int) ($top['nombreLikes'] ?? 0) <= 0) {
            return null;
        }

        if ((int) ($top['bonus_top1_given'] ?? 0) === 1) {
            return $top;
        }

        $this->pdo->beginTransaction();

        try {
            $lock = $this->pdo->prepare(
                "SELECT *
                 FROM participation_challenge
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );

            $lock->execute([
                'id' => (int) $top['id'],
            ]);

            $lockedTop = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$lockedTop || (int) ($lockedTop['bonus_top1_given'] ?? 0) === 1) {
                $this->pdo->commit();
                return $lockedTop ?: $top;
            }

            $upd = $this->pdo->prepare(
                'UPDATE participation_challenge
                 SET bonus_top1_given = 1
                 WHERE id = :id
                 LIMIT 1'
            );

            $upd->execute([
                'id' => (int) $lockedTop['id'],
            ]);

            $this->ajouterPoints((int) $lockedTop['clientId'], 20);

            $this->pdo->commit();

            $lockedTop['bonus_top1_given'] = 1;

            return $lockedTop;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return null;
        }
    }

    public function recalculerBonusTop1(int $challengeId): void
    {
        if ($challengeId < 1) {
            return;
        }

        $this->calculerEtAttribuerGagnant($challengeId);
    }

    // ----------------------------------------------------------------
    // Roue de la fortune
    // ----------------------------------------------------------------

    /**
     * 8 segments : 2 recettes, 3 produits, 3 paliers de points (100 / 50 / 10).
     *
     * @return list<array<string, mixed>>
     */
    public function buildFortuneWheelSegments(): array
    {
        $recipes = $this->fetchWheelRecipes(2);
        $products = $this->fetchWheelProducts(3);

        $recipeSlots = [];
        foreach ($recipes as $recipe) {
            $recipeSlots[] = [
                'type' => 'recette',
                'code' => 'recette_' . (int) ($recipe['id_recette'] ?? 0),
                'label' => (string) ($recipe['nom'] ?? 'Recette'),
                'image' => $this->wheelImageUrl($recipe['image'] ?? null),
                'id_recette' => (int) ($recipe['id_recette'] ?? 0),
            ];
        }

        while (count($recipeSlots) < 2) {
            $recipeSlots[] = [
                'type' => 'recette',
                'code' => 'recette_fallback_' . count($recipeSlots),
                'label' => 'Recette surprise',
                'image' => null,
                'id_recette' => 0,
                'nom_recherche' => 'salade',
            ];
        }

        $productSlots = [];
        foreach ($products as $product) {
            $productSlots[] = [
                'type' => 'produit',
                'code' => 'produit_' . (int) ($product['id_produit'] ?? 0),
                'label' => (string) ($product['nom'] ?? 'Produit'),
                'image' => $this->wheelImageUrl($product['image'] ?? null),
                'id_produit' => (int) ($product['id_produit'] ?? 0),
            ];
        }

        while (count($productSlots) < 3) {
            $fallbackNames = ['pomme', 'brocoli', 'carotte'];
            $idx = count($productSlots);
            $productSlots[] = [
                'type' => 'produit',
                'code' => 'produit_fallback_' . $idx,
                'label' => 'Produit surprise',
                'image' => null,
                'id_produit' => 0,
                'nom_recherche' => $fallbackNames[$idx] ?? 'pomme',
            ];
        }

        $pointSlots = [];
        foreach ([100, 50, 10] as $points) {
            $pointSlots[] = [
                'type' => 'points',
                'code' => 'points_' . $points,
                'label' => '+' . $points . ' points santé',
                'image' => null,
                'points' => $points,
            ];
        }

        return [
            $recipeSlots[0],
            $productSlots[0],
            $pointSlots[0],
            $recipeSlots[1],
            $productSlots[1],
            $pointSlots[1],
            $productSlots[2],
            $pointSlots[2],
        ];
    }

    public function tournerRoue(int $clientId): array
    {
        panier_ensure_session();

        $pointsAvant = $this->getPointsClient($clientId);

        if ($pointsAvant < 100) {
            return [
                'success' => false,
                'message' => "Vous n'avez pas assez de points. Vous avez {$pointsAvant} points, il en faut 100.",
                'points_avant' => $pointsAvant,
                'points_apres' => $pointsAvant,
            ];
        }

        $segments = $_SESSION['fortune_wheel_segments'] ?? null;
        if (!is_array($segments) || count($segments) !== 8) {
            $segments = $this->buildFortuneWheelSegments();
        }
        unset($_SESSION['fortune_wheel_segments']);

        $winIndex = random_int(0, count($segments) - 1);
        $segment = $segments[$winIndex];

        $gain = $this->resolveWheelSegmentGain($segment);

        if ($gain === null) {
            return [
                'success' => false,
                'message' => 'Impossible de préparer ce gain pour la roue.',
                'points_avant' => $pointsAvant,
                'points_apres' => $pointsAvant,
            ];
        }

        $this->ensureProfilSanteForClient($clientId);

        if (!$this->appliquerCoutEtBonusPoints($clientId, $gain)) {
            return [
                'success' => false,
                'message' => 'Impossible de mettre à jour vos points santé.',
                'points_avant' => $pointsAvant,
                'points_apres' => $pointsAvant,
            ];
        }

        $recompenseId = 0;

        try {
            $ins = $this->pdo->prepare(
                'INSERT INTO recompense
                    (
                        clientId,
                        produitRoueId,
                        typeGain,
                        nomGain,
                        pointsUtilises,
                        pointsGagnes,
                        statut
                    )
                 VALUES
                    (
                        :clientId,
                        :produitRoueId,
                        :typeGain,
                        :nomGain,
                        100,
                        :pointsGagnes,
                        "en_attente"
                    )'
            );

            $ins->execute([
                'clientId' => $clientId,
                'produitRoueId' => $this->resolveRecompenseProduitRoueId($gain),
                'typeGain' => (string) $gain['type'],
                'nomGain' => (string) $gain['label'],
                'pointsGagnes' => (int) ($gain['points_bonus'] ?? 0),
            ]);

            $recompenseId = (int) $this->pdo->lastInsertId();

            if ($recompenseId > 0 && (string) ($gain['type'] ?? '') === 'points') {
                $markPoints = $this->pdo->prepare(
                    "UPDATE recompense
                     SET statut = 'utilisee'
                     WHERE id = :id
                     AND statut = 'en_attente'
                     LIMIT 1"
                );
                $markPoints->execute(['id' => $recompenseId]);
            }
        } catch (Throwable $e) {
            // Le gain est déjà appliqué sur profil_sante ; l'historique recompense est secondaire.
        }

        $cartApplied = 0;

        if ($recompenseId > 0) {
            $cartApplied = fortune_wheel_apply_db_rewards($clientId);
        } elseif (in_array((string) ($gain['type'] ?? ''), ['produit', 'recette'], true)) {
            fortune_wheel_store_gain($gain);
            $_SESSION['fortune_wheel_last_gain'] = $gain;
            fortune_wheel_apply_pending();
            $cartApplied = count(panier_get_items()) > 0 ? 1 : 0;
        }

        $pointsApres = $this->getPointsClient($clientId);

        return [
            'success' => true,
            'message' => 'Résultat : ' . (string) $gain['label'],
            'segment_index' => $winIndex,
            'segment' => $segment,
            'produit' => [
                'nomProduit' => (string) $gain['label'],
                'typeGain' => (string) $gain['type'],
                'code' => (string) $gain['code'],
            ],
            'gain' => [
                'type' => (string) $gain['type'],
                'code' => (string) $gain['code'],
                'label' => (string) $gain['label'],
                'points_delta' => -100 + (int) ($gain['points_bonus'] ?? 0),
                'points_bonus' => (int) ($gain['points_bonus'] ?? 0),
            ],
            'points_avant' => $pointsAvant,
            'points_apres' => $pointsApres,
            'recompense_id' => $recompenseId,
            'cart_applied' => $cartApplied,
            'panier_count' => count(panier_get_items()),
        ];
    }

    /**
     * @param array<string, mixed> $segment
     * @return array<string, mixed>|null
     */
    private function resolveWheelSegmentGain(array $segment): ?array
    {
        $type = (string) ($segment['type'] ?? '');

        if ($type === 'points') {
            $bonus = max(0, (int) ($segment['points'] ?? 0));

            return [
                'type' => 'points',
                'code' => (string) ($segment['code'] ?? ('points_' . $bonus)),
                'label' => '+' . $bonus . ' points santé',
                'points_delta' => -100 + $bonus,
                'points_bonus' => $bonus,
            ];
        }

        if ($type === 'produit') {
            $produit = ((int) ($segment['id_produit'] ?? 0)) > 0
                ? $this->findProduitById((int) $segment['id_produit'])
                : $this->findProduitByName((string) ($segment['nom_recherche'] ?? $segment['label'] ?? ''));

            if (!$produit) {
                return null;
            }

            return [
                'type' => 'produit',
                'code' => (string) ($segment['code'] ?? ('produit_' . (int) $produit['id_produit'])),
                'label' => (string) ($produit['nom'] ?? 'Produit gratuit'),
                'points_delta' => -100,
                'produit' => $produit,
            ];
        }

        if ($type === 'recette') {
            $recette = ((int) ($segment['id_recette'] ?? 0)) > 0
                ? $this->findRecetteById((int) $segment['id_recette'])
                : $this->findRecetteByName((string) ($segment['nom_recherche'] ?? 'salade'));

            if (!$recette) {
                return null;
            }

            $produitsRecette = $this->getProduitsRecette((int) $recette['id_recette']);

            if ($produitsRecette === []) {
                return null;
            }

            return [
                'type' => 'recette',
                'code' => (string) ($segment['code'] ?? ('recette_' . (int) $recette['id_recette'])),
                'label' => (string) ($recette['nom'] ?? 'Recette gratuite'),
                'points_delta' => -100,
                'recette' => $recette,
                'produits_recette' => $produitsRecette,
            ];
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function fetchWheelRecipes(int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT r.*
                 FROM recette r
                 WHERE r.image IS NOT NULL
                 AND TRIM(r.image) <> \'\'
                 AND EXISTS (
                     SELECT 1 FROM recette_produit rp WHERE rp.id_recette = r.id_recette
                 )
                 ORDER BY r.mise_en_avant DESC, r.id_recette ASC
                 LIMIT :lim'
            );
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return is_array($rows) ? $rows : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    private function fetchWheelProducts(int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT p.*
                 FROM produit p
                 WHERE p.image IS NOT NULL
                 AND TRIM(p.image) <> \'\'
                 ORDER BY p.id_produit ASC
                 LIMIT :lim'
            );
            $stmt->bindValue(':lim', max($limit * 4, 12), PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!is_array($rows)) {
                return [];
            }

            $picked = [];
            $seen = [];

            foreach ($rows as $row) {
                $id = (int) ($row['id_produit'] ?? 0);
                if ($id < 1 || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $picked[] = $row;

                if (count($picked) >= $limit) {
                    break;
                }
            }

            return $picked;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function wheelImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return '/uploads/' . ltrim(str_replace('\\', '/', $path), '/');
    }

    public function getAllProduitsRoue(): array
    {
        $out = [];
        $i = 1;

        foreach ($this->getFixedWheelLabels() as $label) {
            $out[] = [
                'id' => $i++,
                'nomProduit' => $label,
                'actif' => 1,
            ];
        }

        return $out;
    }

    // ----------------------------------------------------------------
    // Recherche produits / recette cadeaux
    // ----------------------------------------------------------------

    private function findProduitById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM produit WHERE id_produit = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function findProduitByName(string $name): ?array
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM produit
                 WHERE LOWER(nom) LIKE LOWER(:name)
                 ORDER BY id_produit ASC
                 LIMIT 1'
            );

            $stmt->execute([
                'name' => '%' . $name . '%',
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function findRecetteById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM recette WHERE id_recette = :id LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function findRecetteByName(string $name): ?array
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM recette
                 WHERE LOWER(nom) LIKE LOWER(:name)
                 ORDER BY id_recette ASC
                 LIMIT 1'
            );

            $stmt->execute([
                'name' => '%' . $name . '%',
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function getProduitsRecette(int $idRecette): array
    {
        if ($idRecette < 1) {
            return [];
        }

        try {
            $recetteController = new RecetteController();

            return $recetteController->getProduitsByRecette($idRecette) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function utilisateurPk(): string
    {
        static $pk = null;

        if ($pk !== null) {
            return $pk;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
             AND table_name = :tableName
             AND column_name = :columnName'
        );

        $stmt->execute([
            'tableName' => 'utilisateur',
            'columnName' => 'id',
        ]);

        $pk = ((int) $stmt->fetchColumn() > 0) ? 'id' : 'id_utilisateur';

        return $pk;
    }
}