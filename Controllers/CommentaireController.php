<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/UtilisateurPhotoSql.php';
require_once __DIR__ . '/../Models/Commentaire.php';

class CommentaireController
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
     * Crée un nouveau commentaire
     * Retourne l'ID du commentaire inséré ou false en cas d'erreur.
     */
    public function create(string $contenu, int $post_id, ?int $idUtilisateur = null)
    {
        try {
            $query = "INSERT INTO Commentaire (contenu, dateCommentaire, post_id, id_utilisateur) 
                      VALUES (:contenu, NOW(), :post_id, :uid)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
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
     * Récupère tous les commentaires d'un post
     */
    public function getByPostId(int $post_id): array
    {
        try {
            $pk = $this->utilisateurPkColumn();
            $photoSel = utilisateur_sql_auteur_photo_expr($this->db);
            $query = "SELECT c.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom, {$photoSel}
                      FROM Commentaire c
                      LEFT JOIN utilisateur u ON c.id_utilisateur = u.`{$pk}`
                      WHERE c.post_id = :post_id ORDER BY c.dateCommentaire ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère un commentaire par son ID
     */
    public function getById(int $id): ?array
    {
        try {
            $query = "SELECT * FROM Commentaire WHERE id = :id";
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
     * Met à jour un commentaire
     */
    public function update(int $id, string $contenu): bool
    {
        try {
            $query = "UPDATE Commentaire SET contenu = :contenu WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Supprime un commentaire
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM Commentaire WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Supprime tous les commentaires d'un post
     */
    public function deleteByPostId(int $post_id): bool
    {
        try {
            $query = "DELETE FROM Commentaire WHERE post_id = :post_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère tous les commentaires
     */
    public function getAll(): array
    {
        try {
            $query = "SELECT * FROM Commentaire ORDER BY dateCommentaire DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
