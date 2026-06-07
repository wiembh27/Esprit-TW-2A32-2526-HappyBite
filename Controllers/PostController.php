<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/UtilisateurPhotoSql.php';
require_once __DIR__ . '/../Models/Post.php';

class PostController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Crée un nouveau post (id_utilisateur optionnel selon migration communauté).
     */
    public function create(string $contenu, ?string $image = null, ?int $idUtilisateur = null): bool
    {
        try {
            $query = "INSERT INTO Post (contenu, datePublication, image, nombreLikes, id_utilisateur) 
                      VALUES (:contenu, NOW(), :image, 0, :uid)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':contenu', $contenu);
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
     * Récupère tous les posts triés par date décroissante avec pagination
     */
    public function getAll(int $limit = 10, int $offset = 0): array
    {
        try {
            $pk = $this->utilisateurPkColumn();
            $photoSel = utilisateur_sql_auteur_photo_expr($this->db);
            $query = "SELECT p.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom, {$photoSel}
                      FROM Post p
                      LEFT JOIN utilisateur u ON p.id_utilisateur = u.`{$pk}`
                      ORDER BY p.datePublication DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
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
     * Récupère le nombre total de posts pour la pagination
     */
    public function getTotalCount(): int
    {
        try {
            $query = "SELECT COUNT(*) FROM Post";
            $stmt = $this->db->query($query);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Récupère un post par son ID
     */
    public function getById(int $id): ?array
    {
        try {
            $query = "SELECT * FROM Post WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Met à jour un post
     */
    public function update(int $id, string $contenu, ?string $image = null): bool
    {
        try {
            if ($image !== null) {
                $query = "UPDATE Post SET contenu = :contenu, image = :image WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':contenu', $contenu);
                $stmt->bindParam(':image', $image);
            } else {
                $query = "UPDATE Post SET contenu = :contenu WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':contenu', $contenu);
            }
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Supprime un post et ses commentaires associés
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM Post WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Incrémente le nombre de likes
     */
    public function addLike(int $id): bool
    {
        try {
            $query = "UPDATE Post SET nombreLikes = nombreLikes + 1 WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Décrémente le nombre de likes
     */
    public function removeLike(int $id): bool
    {
        try {
            $query = "UPDATE Post SET nombreLikes = GREATEST(nombreLikes - 1, 0) WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère un post avec ses commentaires pour l'admin
     */
    public function getPostWithComments(int $id): ?array
    {
        try {
            $post = $this->getById($id);
            if (!$post) {
                return null;
            }

            // Récupérer les commentaires
            require_once __DIR__ . '/CommentaireController.php';
            $commentController = new CommentaireController();
            $comments = $commentController->getByPostId($id);

            return [
                'post' => $post,
                'comments' => $comments
            ];
        } catch (PDOException $e) {
            return null;
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $controller = new PostController();

    switch ($_GET['action']) {
        case 'get_post':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $data = $controller->getPostWithComments($id);
                if ($data) {
                    echo json_encode(['success' => true] + $data);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Post not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID required']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    exit;
}
