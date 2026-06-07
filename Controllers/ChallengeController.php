<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/ChallengeService.php';
require_once __DIR__ . '/AIValidationService.php';

/**
 * Contrôleur pour la gestion des challenges.
 *
 * Rôles :
 * - nutritionniste : créer/supprimer des challenges
 * - client : participer, liker, gagner des points
 */
class ChallengeController
{
    private PDO $pdo;
    private ChallengeService $challengeService;
    private AIValidationService $aiService;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->challengeService = new ChallengeService();
        $this->aiService = new AIValidationService();
    }

    // ----------------------------------------------------------------
    // NUTRITIONNISTE — Créer un challenge
    // ----------------------------------------------------------------

    public function creerChallenge(
        string $titre,
        string $description,
        int $nutritionnisteId,
        ?array $imageFile = null,
        ?string $regleIa = null
    ): array {
        $titre = trim($titre);
        $description = trim($description);
        $regleIa = trim((string) $regleIa);

        if ($titre === '' || $description === '') {
            return [
                'success' => false,
                'message_key' => 'nutritionist.err_required_fields',
            ];
        }

        if ($nutritionnisteId < 1) {
            return [
                'success' => false,
                'message_key' => 'nutritionist.err_invalid_nutritionist',
            ];
        }

        $imagePath = null;

        if ($imageFile && !empty($imageFile['tmp_name'])) {
            $upload = $this->handleImageUpload($imageFile, 'challenges');

            if (!$upload['success']) {
                return $upload;
            }

            $imagePath = $upload['path'];
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO challenge
                    (titre, description, image, statut, dateCreation, nutritionnisteId, regle_ia)
                 VALUES
                    (:titre, :description, :image, "disponible", NOW(), :nutritionnisteId, :regle_ia)'
            );

            $ok = $stmt->execute([
                'titre' => $titre,
                'description' => $description,
                'image' => $imagePath,
                'nutritionnisteId' => $nutritionnisteId,
                'regle_ia' => $regleIa !== '' ? $regleIa : null,
            ]);

            return $ok
                ? [
                    'success' => true,
                    'message_key' => 'nutritionist.challenge_added',
                ]
                : [
                    'success' => false,
                    'message_key' => 'nutritionist.err_create_failed',
                ];
        } catch (Throwable $e) {
            if ($imagePath !== null) {
                $this->deleteUploadedRelativePath($imagePath);
            }

            return [
                'success' => false,
                'message_key' => 'nutritionist.err_create_db',
            ];
        }
    }

    public function countChallengesNutritionniste(int $nutritionnisteId): int
    {
        if ($nutritionnisteId < 1) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM challenge
                 WHERE nutritionnisteId = :nutritionnisteId'
            );
            $stmt->execute([
                'nutritionnisteId' => $nutritionnisteId,
            ]);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function getChallengesNutritionniste(int $nutritionnisteId, int $page = 1, int $perPage = 4): array
    {
        if ($nutritionnisteId < 1) {
            return [];
        }

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        try {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM challenge
                 WHERE nutritionnisteId = :nutritionnisteId
                 ORDER BY dateCreation DESC
                 LIMIT :limit OFFSET :offset'
            );

            $stmt->bindValue('nutritionnisteId', $nutritionnisteId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getChallengesDisponibles(): array
    {
        try {
            $pk = $this->utilisateurPk();

            $stmt = $this->pdo->query(
                "SELECT c.*, u.prenom AS nutri_prenom, u.nom AS nutri_nom
                 FROM challenge c
                 LEFT JOIN utilisateur u ON c.nutritionnisteId = u.`{$pk}`
                 WHERE c.statut = 'disponible'
                 ORDER BY c.dateCreation DESC"
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function supprimerChallenge(int $id, int $nutritionnisteId): array
    {
        if ($id < 1 || $nutritionnisteId < 1) {
            return [
                'success' => false,
                'message_key' => 'nutritionist.err_invalid_challenge',
            ];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, image
                 FROM challenge
                 WHERE id = :id
                 AND nutritionnisteId = :nutritionnisteId
                 AND statut = "disponible"
                 LIMIT 1'
            );

            $stmt->execute([
                'id' => $id,
                'nutritionnisteId' => $nutritionnisteId,
            ]);

            $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$challenge) {
                return [
                    'success' => false,
                    'message_key' => 'nutritionist.err_delete_not_found',
                ];
            }

            $del = $this->pdo->prepare(
                'DELETE FROM challenge
                 WHERE id = :id
                 AND nutritionnisteId = :nutritionnisteId
                 LIMIT 1'
            );

            $ok = $del->execute([
                'id' => $id,
                'nutritionnisteId' => $nutritionnisteId,
            ]);

            if ($ok && !empty($challenge['image'])) {
                $this->deleteUploadedRelativePath((string) $challenge['image']);
            }

            return $ok
                ? [
                    'success' => true,
                    'message_key' => 'nutritionist.challenge_deleted',
                ]
                : [
                    'success' => false,
                    'message_key' => 'nutritionist.err_delete_failed',
                ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message_key' => 'nutritionist.err_delete_db',
            ];
        }
    }

    // ----------------------------------------------------------------
    // CLIENT — Challenge du jour
    // ----------------------------------------------------------------

    public function getChallengeduJour(): ?array
    {
        return $this->challengeService->tirerChallengeduJour();
    }

    public function getChallengeDuJourSansTirage(): ?array
    {
        return $this->challengeService->getChallengeduJour();
    }

    public function getActiveSelectedChallenge(): ?array
    {
        return $this->challengeService->getActiveSelectedChallenge();
    }

    public function getLastEndedChallenge(): ?array
    {
        return $this->challengeService->getLastEndedChallenge();
    }

    /**
     * @return array{
     *   challengeId: int,
     *   top5: list<array{rank: string, name: string}>,
     *   user: array{rank: string, name: string}
     * }|null
     */
    public function getWinnersLeaderboard(int $challengeId, int $clientId, string $clientPrenom, string $clientNom): ?array
    {
        if ($challengeId < 1) {
            return null;
        }

        $participations = $this->getParticipationsValidees($challengeId);
        $hasJoined = $this->aDejaParticipe($clientId, $challengeId);

        $top5 = [];

        for ($i = 0; $i < min(5, count($participations)); $i++) {
            $p = $participations[$i];
            $top5[] = [
                'rank' => (string) ($i + 1),
                'name' => $this->formatParticipantName($p),
                'isCurrentUser' => ((int) ($p['clientId'] ?? 0) === $clientId),
            ];
        }

        $userRank = null;
        foreach ($participations as $index => $p) {
            if ((int) ($p['clientId'] ?? 0) === $clientId) {
                $userRank = $index + 1;
                break;
            }
        }

        $displayName = trim($clientNom . ' ' . $clientPrenom);
        if ($displayName === '') {
            $displayName = 'Membre HappyBite';
        }

        $userRankLabel = '—';
        if ($userRank !== null) {
            $userRankLabel = $this->ordinalRankLabel($userRank);
        }

        return [
            'challengeId' => $challengeId,
            'top5' => $top5,
            'user' => [
                'rank' => $userRankLabel,
                'name' => $displayName,
                'participated' => $hasJoined,
            ],
        ];
    }

    private function formatParticipantName(array $row): string
    {
        $nom = trim((string) ($row['client_nom'] ?? ''));
        $prenom = trim((string) ($row['client_prenom'] ?? ''));
        $name = trim($nom . ' ' . $prenom);

        return $name !== '' ? $name : 'Membre HappyBite';
    }

    private function ordinalRankLabel(int $rank): string
    {
        $suffix = 'th';
        if ($rank % 100 < 11 || $rank % 100 > 13) {
            $suffix = match ($rank % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return $rank . $suffix;
    }

    public function aDejaParticipe(int $clientId, int $challengeId): bool
    {
        if ($clientId < 1 || $challengeId < 1) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM participation_challenge
                 WHERE clientId = :clientId
                 AND challengeId = :challengeId
                 LIMIT 1'
            );

            $stmt->execute([
                'clientId' => $clientId,
                'challengeId' => $challengeId,
            ]);

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function soumettreparticipation(
        int $clientId,
        int $challengeId,
        string $description,
        ?array $photoFile = null
    ): array {
        $description = trim($description);

        if ($clientId < 1) {
            return [
                'success' => false,
                'message_key' => 'challenge.err_invalid_user',
            ];
        }

        if ($challengeId < 1) {
            return [
                'success' => false,
                'message_key' => 'challenge.err_invalid_challenge',
            ];
        }

        $challenge = $this->challengeService->getChallengeduJour();

        if (!$challenge || (int) $challenge['id'] !== $challengeId) {
            return [
                'success' => false,
                'message_key' => 'challenge.err_challenge_inactive',
            ];
        }

        if ($this->aDejaParticipe($clientId, $challengeId)) {
            return [
                'success' => false,
                'message_key' => 'challenge.err_already_participated',
            ];
        }

        if (!$photoFile || empty($photoFile['tmp_name'])) {
            return [
                'success' => false,
                'message_key' => 'challenge.err_photo_required',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Upload temporaire dans uploads/participations
        |--------------------------------------------------------------------------
        | On a besoin d’un chemin serveur pour envoyer l’image à l’IA.
        | Si l’IA refuse, on supprime immédiatement la photo.
        */

        $upload = $this->handleImageUpload($photoFile, 'participations');

        if (!$upload['success']) {
            return $upload;
        }

        $photoPath = (string) $upload['path'];
        $serverPath = $this->relativeToServerPath($photoPath);

        /*
        |--------------------------------------------------------------------------
        | 2. Validation IA réelle
        |--------------------------------------------------------------------------
        */

        $validation = $this->aiService->validateChallengeWithAI(
            $serverPath,
            $description,
            $challenge
        );

        $validationSuccess = !empty($validation['success']);
        $validationMessage = (string) ($validation['message'] ?? '');
        $validationScore = (int) ($validation['score'] ?? 0);

        if (!$validationSuccess) {
            $this->deleteUploadedRelativePath($photoPath);

            return [
                'success' => false,
                'message' => $validationMessage !== ''
                    ? $validationMessage
                    : '',
                'message_key' => $validationMessage !== ''
                    ? null
                    : 'challenge.err_photo_rejected',
                'statut' => 'refuse',
                'score' => $validationScore,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Enregistrement final seulement si IA validée
        |--------------------------------------------------------------------------
        */

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO participation_challenge
                    (
                        clientId,
                        challengeId,
                        photo,
                        description,
                        statutValidationIA,
                        nombreLikes,
                        dateParticipation,
                        bonus_top1_given,
                        validation_ai_message,
                        validation_ai_score
                    )
                 VALUES
                    (
                        :clientId,
                        :challengeId,
                        :photo,
                        :description,
                        "valide",
                        0,
                        NOW(),
                        0,
                        :validation_ai_message,
                        :validation_ai_score
                    )'
            );

            $stmt->execute([
                'clientId' => $clientId,
                'challengeId' => $challengeId,
                'photo' => $photoPath,
                'description' => $description,
                'validation_ai_message' => $validationMessage,
                'validation_ai_score' => $validationScore,
            ]);

            return [
                'success' => true,
                'message' => $validationMessage !== ''
                    ? $validationMessage
                    : '',
                'message_key' => $validationMessage !== ''
                    ? null
                    : 'challenge.participation_published',
                'statut' => 'valide',
                'score' => $validationScore,
            ];
        } catch (Throwable $e) {
            $this->deleteUploadedRelativePath($photoPath);

            return [
                'success' => false,
                'message_key' => 'challenge.err_participation_save',
            ];
        }
    }

    // ----------------------------------------------------------------
    // Participations & likes
    // ----------------------------------------------------------------

    public function getParticipationsValidees(int $challengeId): array
    {
        if ($challengeId < 1) {
            return [];
        }

        try {
            $pk = $this->utilisateurPk();
            $photoCol = $this->utilisateurPhotoColumn();

            $selectPhoto = $photoCol !== null
                ? ', u.`' . $photoCol . '` AS client_photo'
                : ', NULL AS client_photo';

            $stmt = $this->pdo->prepare(
                "SELECT
                    pc.*,
                    u.prenom AS client_prenom,
                    u.nom AS client_nom
                    {$selectPhoto}
                 FROM participation_challenge pc
                 LEFT JOIN utilisateur u ON pc.clientId = u.`{$pk}`
                 WHERE pc.challengeId = :challengeId
                 AND pc.statutValidationIA = 'valide'
                 ORDER BY pc.nombreLikes DESC, pc.dateParticipation ASC"
            );

            $stmt->execute([
                'challengeId' => $challengeId,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function likerParticipation(int $participationId, int $userId): array
    {
        if ($participationId < 1 || $userId < 1) {
            return [
                'success' => false,
                'message' => 'Like invalide.',
            ];
        }

        try {
            $stParticipation = $this->pdo->prepare(
                'SELECT id, challengeId
                 FROM participation_challenge
                 WHERE id = :id
                 AND statutValidationIA = "valide"
                 LIMIT 1'
            );

            $stParticipation->execute([
                'id' => $participationId,
            ]);

            $participation = $stParticipation->fetch(PDO::FETCH_ASSOC);

            if (!$participation) {
                return [
                    'success' => false,
                    'message' => 'Participation introuvable ou non validée.',
                ];
            }

            $challengeId = (int) $participation['challengeId'];

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM like_participation
                 WHERE participationId = :participationId
                 AND userId = :userId
                 LIMIT 1'
            );

            $stmt->execute([
                'participationId' => $participationId,
                'userId' => $userId,
            ]);

            $alreadyLiked = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($alreadyLiked) {
                $del = $this->pdo->prepare(
                    'DELETE FROM like_participation
                     WHERE participationId = :participationId
                     AND userId = :userId'
                );

                $del->execute([
                    'participationId' => $participationId,
                    'userId' => $userId,
                ]);

                $action = 'unlike';
            } else {
                $ins = $this->pdo->prepare(
                    'INSERT INTO like_participation
                        (participationId, userId)
                     VALUES
                        (:participationId, :userId)'
                );

                $ins->execute([
                    'participationId' => $participationId,
                    'userId' => $userId,
                ]);

                $action = 'like';
            }

            /*
            |--------------------------------------------------------------------------
            | Recalcul réel du nombre de likes
            |--------------------------------------------------------------------------
            */

            $countStmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM like_participation
                 WHERE participationId = :participationId'
            );

            $countStmt->execute([
                'participationId' => $participationId,
            ]);

            $likes = (int) $countStmt->fetchColumn();

            $upd = $this->pdo->prepare(
                'UPDATE participation_challenge
                 SET nombreLikes = :likes
                 WHERE id = :id
                 LIMIT 1'
            );

            $upd->execute([
                'likes' => $likes,
                'id' => $participationId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Recalcul bonus top 1
            |--------------------------------------------------------------------------
            */

            $this->challengeService->recalculerBonusTop1($challengeId);

            return [
                'success' => true,
                'action' => $action,
                'likes' => $likes,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erreur pendant le like.',
            ];
        }
    }

    public function aLike(int $participationId, int $userId): bool
    {
        if ($participationId < 1 || $userId < 1) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM like_participation
                 WHERE participationId = :participationId
                 AND userId = :userId
                 LIMIT 1'
            );

            $stmt->execute([
                'participationId' => $participationId,
                'userId' => $userId,
            ]);

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    // ----------------------------------------------------------------
    // Points & roue
    // ----------------------------------------------------------------

    public function getPointsClient(int $clientId): int
    {
        return $this->challengeService->getPointsClient($clientId);
    }

    public function tournerRoue(int $clientId): array
    {
        return $this->challengeService->tournerRoue($clientId);
    }

    public function getProduitsRoue(): array
    {
        return $this->challengeService->getAllProduitsRoue();
    }

    /** @return list<array<string, mixed>> */
    public function getFortuneWheelSegments(): array
    {
        return $this->challengeService->buildFortuneWheelSegments();
    }

    // ----------------------------------------------------------------
    // Upload helper
    // ----------------------------------------------------------------

    private function handleImageUpload(array $file, string $subDir): array
    {
        require_once __DIR__ . '/UploadStorage.php';

        return UploadStorage::saveUploadedImage($file, $subDir);
    }

    private function relativeToServerPath(string $relativePath): string
    {
        require_once __DIR__ . '/UploadStorage.php';

        return UploadStorage::serverPath($relativePath);
    }

    private function deleteUploadedRelativePath(string $relativePath): void
    {
        $path = $this->relativeToServerPath($relativePath);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    // ----------------------------------------------------------------
    // Helpers utilisateur
    // ----------------------------------------------------------------

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

    private function utilisateurPhotoColumn(): ?string
    {
        static $photoColumn = null;
        static $checked = false;

        if ($checked) {
            return $photoColumn;
        }

        $checked = true;

        $possible = [
            'profil-image',
            'profil_image',
            'photo',
            'image',
            'avatar',
        ];

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
             AND table_name = :tableName
             AND column_name = :columnName'
        );

        foreach ($possible as $column) {
            $stmt->execute([
                'tableName' => 'utilisateur',
                'columnName' => $column,
            ]);

            if ((int) $stmt->fetchColumn() > 0) {
                $photoColumn = $column;
                return $photoColumn;
            }
        }

        return null;
    }
}