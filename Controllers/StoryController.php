<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/UtilisateurPhotoSql.php';
require_once __DIR__ . '/../Models/Story.php';

class StoryController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function utilisateurPkColumn(): string
    {
        static $col = null;
        if ($col !== null) {
            return $col;
        }
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
        );
        $stmt->execute(['t' => 'utilisateur', 'c' => 'id']);
        if ((int) $stmt->fetchColumn() > 0) {
            $col = 'id';
            return $col;
        }
        $stmt->execute(['t' => 'utilisateur', 'c' => 'id_utilisateur']);
        $col = (int) $stmt->fetchColumn() > 0 ? 'id_utilisateur' : 'id';
        return $col;
    }

    /**
     * Crée une nouvelle story
     */
    public function create(string $image, ?int $idUtilisateur = null): bool
    {
        try {
            $query = "INSERT INTO Story (image, dateCreation, id_utilisateur) VALUES (:image, NOW(), :uid)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':image', $image);
            if ($idUtilisateur !== null && $idUtilisateur > 0) {
                $stmt->bindValue(':uid', $idUtilisateur, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':uid', null, PDO::PARAM_NULL);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère toutes les stories valides (moins de 24h)
     */
    public function getActiveStories(): array
    {
        try {
            $pk = $this->utilisateurPkColumn();
            $photoSel = utilisateur_sql_auteur_photo_expr($this->db);
            $query = "SELECT s.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom, {$photoSel}
                      FROM Story s
                      LEFT JOIN utilisateur u ON s.id_utilisateur = u.`{$pk}`
                      WHERE s.dateCreation >= NOW() - INTERVAL 1 DAY ORDER BY s.dateCreation DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Supprime une story
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM Story WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function countLikes(int $storyId): int
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM StoryLike WHERE story_id = :sid');
            $stmt->bindValue(':sid', $storyId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function hasLiked(int $storyId, string $visitorKey): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT 1 FROM StoryLike WHERE story_id = :sid AND visitor_key = :vk LIMIT 1'
            );
            $stmt->bindValue(':sid', $storyId, PDO::PARAM_INT);
            $stmt->bindValue(':vk', $visitorKey);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @return array{liked: bool, count: int}
     */
    public function toggleLike(int $storyId, string $visitorKey): array
    {
        try {
            if ($this->hasLiked($storyId, $visitorKey)) {
                $del = $this->db->prepare(
                    'DELETE FROM StoryLike WHERE story_id = :sid AND visitor_key = :vk'
                );
                $del->bindValue(':sid', $storyId, PDO::PARAM_INT);
                $del->bindValue(':vk', $visitorKey);
                $del->execute();
                return ['liked' => false, 'count' => $this->countLikes($storyId)];
            }
            $ins = $this->db->prepare(
                'INSERT INTO StoryLike (story_id, visitor_key) VALUES (:sid, :vk)'
            );
            $ins->bindValue(':sid', $storyId, PDO::PARAM_INT);
            $ins->bindValue(':vk', $visitorKey);
            $ins->execute();
            return ['liked' => true, 'count' => $this->countLikes($storyId)];
        } catch (PDOException $e) {
            return ['liked' => false, 'count' => $this->countLikes($storyId)];
        }
    }

    public function countComments(int $storyId): int
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM StoryCommentaire WHERE story_id = :sid'
            );
            $stmt->bindValue(':sid', $storyId, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * @return int|false new comment id
     */
    public function addComment(int $storyId, string $contenu, ?int $idUtilisateur = null)
    {
        try {
            $query = 'INSERT INTO StoryCommentaire (contenu, dateCommentaire, story_id, id_utilisateur)
                      VALUES (:contenu, NOW(), :sid, :uid)';
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindValue(':sid', $storyId, PDO::PARAM_INT);
            if ($idUtilisateur !== null && $idUtilisateur > 0) {
                $stmt->bindValue(':uid', $idUtilisateur, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':uid', null, PDO::PARAM_NULL);
            }
            if ($stmt->execute()) {
                return (int) $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCommentsByStoryId(int $storyId): array
    {
        try {
            $pk = $this->utilisateurPkColumn();
            $photoSel = utilisateur_sql_auteur_photo_expr($this->db);
            $stmt = $this->db->prepare(
                "SELECT c.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom, {$photoSel}
                 FROM StoryCommentaire c
                 LEFT JOIN utilisateur u ON c.id_utilisateur = u.`{$pk}`
                 WHERE c.story_id = :sid ORDER BY c.dateCommentaire ASC"
            );
            $stmt->bindValue(':sid', $storyId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
