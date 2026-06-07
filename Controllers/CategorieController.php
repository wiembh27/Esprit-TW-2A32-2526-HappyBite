<?php
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../Models/Categorie.php');

class CategorieController
{
    public function addCategorie(Categorie $categorie)
    {
        $sql = "INSERT INTO categorie (nom, description) VALUES (:nom, :description)";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $categorie->getNom(),
                'description' => $categorie->getDescription()
            ]);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
        }
    }

    public function listCategories()
    {
        $sql = "SELECT * FROM categorie ORDER BY id_categorie DESC";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute();
            $categoriesData = $query->fetchAll();

            $categories = [];
            foreach ($categoriesData as $row) {
                $categorie = new Categorie($row['nom'], $row['description']);
                $categorie->setIdCategorie((int)$row['id_categorie']);
                $categories[] = $categorie;
            }

            return $categories;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function deleteCategorie($id)
    {
        $sql = "DELETE FROM categorie WHERE id_categorie = :id";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id' => $id
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function showCategorie($id)
    {
        $sql = "SELECT * FROM categorie WHERE id_categorie = :id";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id' => $id
            ]);

            $data = $query->fetch();

            if ($data) {
                $categorie = new Categorie($data['nom'], $data['description']);
                $categorie->setIdCategorie((int)$data['id_categorie']);
                return $categorie;
            }

            return null;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return null;
        }
    }
public function rechercherCategories($motCle = "")
{
    $sql = "SELECT * FROM categorie 
            WHERE nom LIKE :motCle
            ORDER BY id_categorie DESC";

    $db = Config::getConnexion();

    try {
        $query = $db->prepare($sql);
        $motCle = "%" . $motCle . "%";
        $query->execute([
            'motCle' => $motCle
        ]);

        $categoriesData = $query->fetchAll();
        $categories = [];

        foreach ($categoriesData as $row) {
            $categorie = new Categorie($row['nom'], $row['description']);
            $categorie->setIdCategorie((int)$row['id_categorie']);
            $categories[] = $categorie;
        }

        return $categories;
    } catch (Exception $e) {
        echo 'Erreur : ' . $e->getMessage();
        return [];
    }
}
    public function updateCategorie(Categorie $categorie, $id)
    {
        $sql = "UPDATE categorie 
                SET nom = :nom, description = :description
                WHERE id_categorie = :id";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $categorie->getNom(),
                'description' => $categorie->getDescription(),
                'id' => $id
            ]);
            return true;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function createCategorieIfNotExists(string $nom, string $description = '')
{
    $sql = "SELECT * FROM categorie WHERE nom = :nom LIMIT 1";
    $db = config::getConnexion();
    $query = $db->prepare($sql);
    $query->bindValue(':nom', $nom);
    $query->execute();

    $categorie = $query->fetch();

    if ($categorie) {
        return $categorie;
    }

    $insertSql = "INSERT INTO categorie (nom, description) VALUES (:nom, :description)";
    $insertQuery = $db->prepare($insertSql);
    $insertQuery->bindValue(':nom', $nom);
    $insertQuery->bindValue(':description', $description);
    $insertQuery->execute();

    $id = $db->lastInsertId();

    $query = $db->prepare("SELECT * FROM categorie WHERE id_categorie = :id");
    $query->bindValue(':id', $id, PDO::PARAM_INT);
    $query->execute();

    return $query->fetch();
}
}
?>