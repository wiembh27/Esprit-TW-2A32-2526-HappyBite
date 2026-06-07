<?php
require_once(__DIR__ . '/../config/Database.php');
class FrigoController
{
    public function ajouterAuFrigo($idUtilisateur, $idProduit, $quantite = 1)
    {
        $db = Config::getConnexion();

        try {
            $sqlCheck = "SELECT quantite
                         FROM frigo
                         WHERE id_utilisateur = :id_utilisateur
                         AND id_produit = :id_produit";

            $queryCheck = $db->prepare($sqlCheck);
            $queryCheck->execute([
                'id_utilisateur' => $idUtilisateur,
                'id_produit' => $idProduit
            ]);

            $ligne = $queryCheck->fetch(PDO::FETCH_ASSOC);

            if ($ligne) {
                $sqlUpdate = "UPDATE frigo
                              SET quantite = quantite + :quantite,
                                  date_ajout = NOW()
                              WHERE id_utilisateur = :id_utilisateur
                              AND id_produit = :id_produit";

                $queryUpdate = $db->prepare($sqlUpdate);
                return $queryUpdate->execute([
                    'quantite' => $quantite,
                    'id_utilisateur' => $idUtilisateur,
                    'id_produit' => $idProduit
                ]);
            }

            $sqlInsert = "INSERT INTO frigo (id_utilisateur, id_produit, quantite, date_ajout)
                          VALUES (:id_utilisateur, :id_produit, :quantite, NOW())";

            $queryInsert = $db->prepare($sqlInsert);
            return $queryInsert->execute([
                'id_utilisateur' => $idUtilisateur,
                'id_produit' => $idProduit,
                'quantite' => $quantite
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getFrigoByUtilisateur($idUtilisateur, $motCle = '', $idCategorie = '')
    {
        $db = Config::getConnexion();

        $sql = "SELECT
                    f.id_utilisateur,
                    f.id_produit,
                    f.quantite,
                    f.date_ajout,
                    p.nom,
                    p.prix,
                    p.image,
                    p.allergene,
                    p.benefices,
                    p.calories,
                    p.id_categorie,
                    c.nom AS nom_categorie
                FROM frigo f
                INNER JOIN produit p ON f.id_produit = p.id_produit
                LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                WHERE f.id_utilisateur = :id_utilisateur";

        $params = [
            'id_utilisateur' => $idUtilisateur
        ];

        if (!empty($motCle)) {
            $sql .= " AND p.nom LIKE :motCle";
            $params['motCle'] = '%' . $motCle . '%';
        }

        if (!empty($idCategorie)) {
            $sql .= " AND p.id_categorie = :id_categorie";
            $params['id_categorie'] = $idCategorie;
        }

        $sql .= " ORDER BY f.date_ajout DESC";

        try {
            $query = $db->prepare($sql);
            $query->execute($params);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function updateQuantite($idUtilisateur, $idProduit, $quantite)
    {
        $db = Config::getConnexion();

        try {
            if ($quantite <= 0) {
                return $this->supprimerDuFrigo($idUtilisateur, $idProduit);
            }

            $sql = "UPDATE frigo
                    SET quantite = :quantite
                    WHERE id_utilisateur = :id_utilisateur
                    AND id_produit = :id_produit";

            $query = $db->prepare($sql);

            return $query->execute([
                'quantite' => $quantite,
                'id_utilisateur' => $idUtilisateur,
                'id_produit' => $idProduit
            ]);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function supprimerDuFrigo($idUtilisateur, $idProduit)
    {
        $db = Config::getConnexion();

        try {
            $sql = "DELETE FROM frigo
                    WHERE id_utilisateur = :id_utilisateur
                    AND id_produit = :id_produit";

            $query = $db->prepare($sql);

            return $query->execute([
                'id_utilisateur' => $idUtilisateur,
                'id_produit' => $idProduit
            ]);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function getNombreProduitsDansFrigo($idUtilisateur)
    {
        $db = Config::getConnexion();

        try {
            $sql = "SELECT COUNT(*) AS total
                    FROM frigo
                    WHERE id_utilisateur = :id_utilisateur";

            $query = $db->prepare($sql);
            $query->execute([
                'id_utilisateur' => $idUtilisateur
            ]);

            $result = $query->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return 0;
        }
    }
public function getAllFrigo($motCle = '', $idUtilisateur = '')
{
    $db = Config::getConnexion();

    $sql = "SELECT
                f.id_utilisateur,
                f.id_produit,
                f.quantite,
                f.date_ajout,
                p.nom AS nom_produit,
                p.prix,
                c.nom AS nom_categorie,
                CONCAT(u.nom, ' ', u.prenom) AS nom_utilisateur
            FROM frigo f
            INNER JOIN produit p ON f.id_produit = p.id_produit
            LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
            INNER JOIN utilisateur u ON f.id_utilisateur = u.id_utilisateur
            WHERE 1=1";

    $params = [];

    if (!empty($motCle)) {
        $sql .= " AND (
                    p.nom LIKE :motCle
                    OR u.nom LIKE :motCle
                    OR u.prenom LIKE :motCle
                    OR CONCAT(u.nom, ' ', u.prenom) LIKE :motCle
                  )";
        $params['motCle'] = '%' . $motCle . '%';
    }

    if (!empty($idUtilisateur)) {
        $sql .= " AND f.id_utilisateur = :id_utilisateur";
        $params['id_utilisateur'] = $idUtilisateur;
    }

    $sql .= " ORDER BY f.date_ajout DESC";

    try {
        $query = $db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo 'Erreur : ' . $e->getMessage();
        return [];
    }
}

public function updateQuantiteAdmin($idUtilisateur, $idProduit, $quantite)
{
    return $this->updateQuantite($idUtilisateur, $idProduit, $quantite);
}

public function deleteLigneFrigoAdmin($idUtilisateur, $idProduit)
{
    return $this->supprimerDuFrigo($idUtilisateur, $idProduit);
}
public function getResumeFrigos($motCle = '', $idUtilisateur = '')
{
    $db = Config::getConnexion();

    $sql = "SELECT
                f.id_utilisateur,
                CONCAT(u.nom, ' ', u.prenom) AS nom_utilisateur,
                COUNT(DISTINCT f.id_produit) AS nombre_produits,
                SUM(f.quantite) AS quantite_totale,
                MAX(f.date_ajout) AS derniere_date_ajout,
                GROUP_CONCAT(
                    CONCAT(p.nom, ' (x', f.quantite, ')')
                    ORDER BY p.nom SEPARATOR ' | '
                ) AS liste_produits
            FROM frigo f
            INNER JOIN utilisateur u ON f.id_utilisateur = u.id_utilisateur
            INNER JOIN produit p ON f.id_produit = p.id_produit
            WHERE 1=1";

    $params = [];

    if (!empty($motCle)) {
        $sql .= " AND (
                    p.nom LIKE :motCle
                    OR u.nom LIKE :motCle
                    OR u.prenom LIKE :motCle
                    OR CONCAT(u.nom, ' ', u.prenom) LIKE :motCle
                  )";
        $params['motCle'] = '%' . $motCle . '%';
    }

    if (!empty($idUtilisateur)) {
        $sql .= " AND f.id_utilisateur = :id_utilisateur";
        $params['id_utilisateur'] = $idUtilisateur;
    }

    $sql .= " GROUP BY f.id_utilisateur, u.nom, u.prenom
              ORDER BY derniere_date_ajout DESC";

    try {
        $query = $db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo 'Erreur : ' . $e->getMessage();
        return [];
    }
}
public function getDetailFrigoByUtilisateur($idUtilisateur)
{
    $db = Config::getConnexion();

    $sql = "SELECT
                f.id_utilisateur,
                f.id_produit,
                f.quantite,
                f.date_ajout,
                p.nom,
                p.prix,
                p.image,
                p.allergene,
                p.benefices,
                p.calories,
                c.nom AS nom_categorie,
                CONCAT(u.nom, ' ', u.prenom) AS nom_utilisateur
            FROM frigo f
            INNER JOIN produit p ON f.id_produit = p.id_produit
            INNER JOIN utilisateur u ON f.id_utilisateur = u.id_utilisateur
            LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
            WHERE f.id_utilisateur = :id_utilisateur
            ORDER BY f.date_ajout DESC";

    try {
        $query = $db->prepare($sql);
        $query->execute([
            'id_utilisateur' => $idUtilisateur
        ]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo 'Erreur : ' . $e->getMessage();
        return [];
    }
}
public function getTopProduitsFrigo()
{
    $sql = "SELECT p.nom, COUNT(f.id_produit) AS total
            FROM frigo f
            INNER JOIN produit p ON f.id_produit = p.id_produit
            GROUP BY f.id_produit, p.nom
            ORDER BY total DESC
            LIMIT 3";

    $db = config::getConnexion();

    try {
        $query = $db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die('Erreur: ' . $e->getMessage());
    }
}

public function getCategoriesLesPlusPresentes()
{
    $sql = "SELECT c.nom AS nom_categorie, COUNT(f.id_produit) AS total
            FROM frigo f
            INNER JOIN produit p ON f.id_produit = p.id_produit
            INNER JOIN categorie c ON p.id_categorie = c.id_categorie
            GROUP BY c.id_categorie, c.nom
            ORDER BY total DESC";

    $db = config::getConnexion();

    try {
        $query = $db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die('Erreur: ' . $e->getMessage());
    }
}

//fct ia
public function getProduitsByUser($idUtilisateur)
{
    $db = Config::getConnexion();

    $sql = "SELECT p.nom 
            FROM frigo f
            JOIN produit p ON f.id_produit = p.id_produit
            WHERE f.id_utilisateur = :id";

    $query = $db->prepare($sql);
    $query->execute(['id' => $idUtilisateur]);

    return $query->fetchAll(PDO::FETCH_COLUMN);
}
}
