<?php
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../Models/Produit.php');
require_once __DIR__ . '/RecetteController.php';
class ProduitController
{
    public function addProduit(Produit $produit)
    {
        $sql = "INSERT INTO produit 
                (nom, prix, promo, image, allergene, benefices, calories, date_ajout, id_utilisateur, id_categorie)
                VALUES 
                (:nom, :prix, :promo, :image, :allergene, :benefices, :calories, :date_ajout, :id_utilisateur, :id_categorie)";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $produit->getNom(),
                'prix' => $produit->getPrix(),
                'promo' => $produit->getPromo(),
                'image' => $produit->getImage(),
                'allergene' => $produit->getAllergene(),
                'benefices' => $produit->getBenefices(),
                'calories' => $produit->getCalories(),
                'date_ajout' => $produit->getDateAjout(),
                'id_utilisateur' => $produit->getIdUtilisateur(),
                'id_categorie' => $produit->getIdCategorie()
            ]);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
        }
    }

    public function listProduits()
    {
        $sql = "SELECT 
                    p.id_produit,
                    p.nom,
                    p.prix,
                    p.promo,
                    p.image,
                    p.allergene,
                    p.benefices,
                    p.calories,
                    p.date_ajout,
                    p.id_utilisateur,
                    p.id_categorie,
                    c.nom AS nom_categorie,
                    CONCAT(u.nom, ' ', u.prenom) AS nom_fournisseur
                FROM produit p
                INNER JOIN categorie c ON p.id_categorie = c.id_categorie
                INNER JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                ORDER BY p.id_produit DESC";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function deleteProduit($id)
    {
        $sql = "DELETE FROM produit WHERE id_produit = :id";
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

    public function showProduit($id)
    {
        $sql = "SELECT * FROM produit WHERE id_produit = :id";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id' => $id
            ]);

            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $produit = new Produit(
                    $data['nom'],
                    (float)$data['prix'],
                    $data['image'],
                    $data['allergene'],
                    $data['benefices'],
                    $data['calories'] !== null ? (int)$data['calories'] : null,
                    $data['date_ajout'],
                    (int)$data['id_utilisateur'],
                    (int)$data['id_categorie']
                );

                $produit->setIdProduit((int)$data['id_produit']);
                $produit->setPromo(isset($data['promo']) && $data['promo'] !== null ? (float)$data['promo'] : null);

                return $produit;
            }

            return null;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return null;
        }
    }

    public function updateProduit(Produit $produit, $id)
    {
        $sql = "UPDATE produit SET
                    nom = :nom,
                    prix = :prix,
                    promo = :promo,
                    image = :image,
                    allergene = :allergene,
                    benefices = :benefices,
                    calories = :calories,
                    date_ajout = :date_ajout,
                    id_utilisateur = :id_utilisateur,
                    id_categorie = :id_categorie
                WHERE id_produit = :id";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $produit->getNom(),
                'prix' => $produit->getPrix(),
                'promo' => $produit->getPromo(),
                'image' => $produit->getImage(),
                'allergene' => $produit->getAllergene(),
                'benefices' => $produit->getBenefices(),
                'calories' => $produit->getCalories(),
                'date_ajout' => $produit->getDateAjout(),
                'id_utilisateur' => $produit->getIdUtilisateur(),
                'id_categorie' => $produit->getIdCategorie(),
                'id' => $id
            ]);
            require_once(__DIR__ . '/RecetteController.php');
            $recetteC = new RecetteController();
            $recetteC->updateCaloriesRecettesByProduit($id);

            return true;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    private function getProfilSanteByUtilisateur($idUtilisateur)
    {
        $sql = "SELECT * FROM profil_sante WHERE id_utilisateur = :id_utilisateur LIMIT 1";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_utilisateur' => $idUtilisateur
            ]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur profil santé : ' . $e->getMessage();
            return null;
        }
    }

    private function convertirTexteEnTableau($texte)
    {
        if (empty($texte)) {
            return [];
        }

        $elements = preg_split('/[,;]+/', $texte);
        $elements = array_map('trim', $elements);
        $elements = array_filter($elements, function ($val) {
            return $val !== '';
        });

        return array_values(array_unique($elements));
    }

    /**
     * profil_sante stores allergenes / carences / maladies as JSON arrays; older rows may use plain text.
     */
    private function listeDepuisChampProfil($valeur)
    {
        if ($valeur === null || $valeur === '') {
            return [];
        }
        if (is_array($valeur)) {
            $out = [];
            foreach ($valeur as $item) {
                $s = is_string($item) ? trim($item) : '';
                if ($s !== '') {
                    $out[] = $s;
                }
            }
            return array_values(array_unique($out));
        }
        if (!is_string($valeur)) {
            return [];
        }
        $trimmed = trim($valeur);
        if ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return $this->listeDepuisChampProfil($decoded);
            }
        }
        return $this->convertirTexteEnTableau($valeur);
    }

    public function rechercherProduitsIntelligents($idUtilisateur, $motCle = "", $idCategorie = "")
    {
        $db = Config::getConnexion();

        try {
            $profil = $this->getProfilSanteByUtilisateur($idUtilisateur);

            $allergenesProfil = [];
            $maladiesProfil = [];
            $carencesProfil = [];
            $objectif = "";

            if ($profil) {
                $allergenesProfil = $this->listeDepuisChampProfil($profil['allergenes'] ?? '');
                $maladiesProfil = $this->listeDepuisChampProfil($profil['maladies'] ?? '');
                $carencesProfil = $this->listeDepuisChampProfil($profil['carences'] ?? '');
                $objectif = trim($profil['objectif'] ?? '');
            }

            $scoreCarencesSql = "0";
            $params = [];

            if (!empty($carencesProfil)) {
                $scoreParts = [];

                foreach ($carencesProfil as $index => $carence) {
                    $paramName = "carence" . $index;
                    $scoreParts[] = "(CASE WHEN p.benefices LIKE :$paramName THEN 1 ELSE 0 END)";
                    $params[$paramName] = "%" . $carence . "%";
                }

                $scoreCarencesSql = implode(" + ", $scoreParts);
            }

            $sql = "SELECT 
                        p.id_produit,
                        p.nom,
                        p.prix,
                        p.promo,
                        p.image,
                        p.allergene,
                        p.benefices,
                        p.calories,
                        p.date_ajout,
                        c.nom AS nom_categorie,
                        CONCAT(u.nom, ' ', u.prenom) AS nom_fournisseur,
                        ($scoreCarencesSql) AS score_carences
                    FROM produit p
                    INNER JOIN categorie c ON p.id_categorie = c.id_categorie
                    INNER JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                    WHERE 1=1";

            if (!empty($motCle)) {
                $motCleNormalise = strtolower(trim($motCle));

                if ($motCleNormalise === 'promo') {
                    $sql .= " AND p.promo IS NOT NULL";
                } else {
                    $sql .= " AND (
                                p.nom LIKE :motCle
                                OR u.nom LIKE :motCle
                                OR u.prenom LIKE :motCle
                                OR CONCAT(u.nom, ' ', u.prenom) LIKE :motCle
                              )";
                    $params['motCle'] = "%" . $motCle . "%";
                }
            }

            if (!empty($idCategorie)) {
                $sql .= " AND p.id_categorie = :idCategorie";
                $params['idCategorie'] = $idCategorie;
            }

            $interdits = $allergenesProfil;

            foreach ($maladiesProfil as $maladie) {
                $maladieNormalisee = strtolower(trim($maladie));

                if ($maladieNormalisee === 'diabete' || $maladieNormalisee === 'diabète') {
                    $interdits[] = 'Sucre élevé';
                }

                if ($maladieNormalisee === 'hypertension') {
                    $interdits[] = 'Sel élevé';
                }
            }

            $interdits = array_unique($interdits);

            foreach ($interdits as $index => $interdit) {
                $paramName = "interdit" . $index;
                $sql .= " AND (
                            p.allergene IS NULL 
                            OR p.allergene = '' 
                            OR p.allergene NOT LIKE :$paramName
                          )";
                $params[$paramName] = "%" . $interdit . "%";
            }

            $calorieMin = null;
            $calorieMax = null;

            $objectifNormalise = strtolower(trim($objectif));

            if ($objectifNormalise === 'perte de poids') {
                $calorieMax = 300;
            } elseif ($objectifNormalise === 'maintien') {
                $calorieMin = 300;
                $calorieMax = 500;
            } elseif ($objectifNormalise === 'gain de poids' || $objectifNormalise === 'prise de masse') {
                $calorieMin = 501;
            }

            foreach ($maladiesProfil as $maladie) {
                $maladieNormalisee = strtolower(trim($maladie));

                if ($maladieNormalisee === 'cholesterol' || $maladieNormalisee === 'cholestérol') {
                    if ($calorieMax === null || $calorieMax > 350) {
                        $calorieMax = 350;
                    }
                }
            }

            if ($calorieMin !== null) {
                $sql .= " AND p.calories >= :calorieMin";
                $params['calorieMin'] = $calorieMin;
            }

            if ($calorieMax !== null) {
                $sql .= " AND p.calories <= :calorieMax";
                $params['calorieMax'] = $calorieMax;
            }

            $sql .= " ORDER BY score_carences DESC, p.calories ASC, p.prix ASC";

            $query = $db->prepare($sql);
            $query->execute($params);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function rechercherProduits($motCle = "", $idCategorie = "")
    {
        $sql = "SELECT 
                    p.id_produit,
                    p.nom,
                    p.prix,
                    p.promo,
                    p.image,
                    p.allergene,
                    p.benefices,
                    p.calories,
                    p.date_ajout,
                    p.id_utilisateur,
                    p.id_categorie,
                    c.nom AS nom_categorie,
                    CONCAT(u.nom, ' ', u.prenom) AS nom_fournisseur
                FROM produit p
                INNER JOIN categorie c ON p.id_categorie = c.id_categorie
                INNER JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                WHERE 1=1";

        $params = [];

        if (!empty($motCle)) {
            $motCleNormalise = strtolower(trim($motCle));

            if ($motCleNormalise === 'promo') {
                $sql .= " AND p.promo IS NOT NULL";
            } else {
                $sql .= " AND (
                            p.nom LIKE :motCle
                            OR u.nom LIKE :motCle
                            OR u.prenom LIKE :motCle
                            OR CONCAT(u.nom, ' ', u.prenom) LIKE :motCle
                          )";
                $params['motCle'] = "%" . $motCle . "%";
            }
        }

        if (!empty($idCategorie)) {
            $sql .= " AND p.id_categorie = :idCategorie";
            $params['idCategorie'] = $idCategorie;
        }

        $sql .= " ORDER BY p.id_produit DESC";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute($params);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function getProduitById($id)
    {
        $sql = "SELECT * FROM produit WHERE id_produit = :id";
        $db = Config::getConnexion();
        $query = $db->prepare($sql);
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function showProduitDetails($id)
    {
        $sql = "SELECT 
                    p.id_produit,
                    p.nom,
                    p.prix,
                    p.promo,
                    p.image,
                    p.allergene,
                    p.benefices,
                    p.calories,
                    p.date_ajout,
                    p.id_utilisateur,
                    p.id_categorie,
                    c.nom AS nom_categorie,
                    CONCAT(u.nom, ' ', u.prenom) AS nom_fournisseur
                FROM produit p
                INNER JOIN categorie c ON p.id_categorie = c.id_categorie
                INNER JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                WHERE p.id_produit = :id";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id' => $id
            ]);

            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function reassignProduitsToCategorie(int $ancienneCategorie, int $nouvelleCategorie): void
    {
        $sql = "UPDATE produit 
                SET id_categorie = :nouvelleCategorie 
                WHERE id_categorie = :ancienneCategorie";

        $db = Config::getConnexion();
        $query = $db->prepare($sql);
        $query->bindValue(':nouvelleCategorie', $nouvelleCategorie, PDO::PARAM_INT);
        $query->bindValue(':ancienneCategorie', $ancienneCategorie, PDO::PARAM_INT);
        $query->execute();
    }

    public function getCategorieByNom(string $nom)
    {
        $sql = "SELECT * FROM categorie WHERE nom = :nom LIMIT 1";
        $db = Config::getConnexion();
        $query = $db->prepare($sql);
        $query->bindValue(':nom', $nom);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function listProduitsByUtilisateur($idUtilisateur, $search = '', $idCategorie = null)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom AS nom_categorie
                FROM produit p
                LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                WHERE p.id_utilisateur = :id_utilisateur";

        $params = [
            'id_utilisateur' => $idUtilisateur
        ];

        if (!empty($search)) {
            $searchNormalise = strtolower(trim($search));

            if ($searchNormalise === 'promo') {
                $sql .= " AND p.promo IS NOT NULL";
            } else {
                $sql .= " AND p.nom LIKE :search";
                $params['search'] = '%' . $search . '%';
            }
        }

        if (!empty($idCategorie)) {
            $sql .= " AND p.id_categorie = :id_categorie";
            $params['id_categorie'] = $idCategorie;
        }

        $sql .= " ORDER BY p.id_produit DESC";

        $db = Config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute($params);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProduitByIdAndUtilisateur($idProduit, $idUtilisateur)
    {
        $sql = "SELECT * FROM produit
                WHERE id_produit = :id_produit
                AND id_utilisateur = :id_utilisateur";

        $db = Config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'id_produit' => $idProduit,
            'id_utilisateur' => $idUtilisateur
        ]);

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteProduitByIdAndUtilisateur($idProduit, $idUtilisateur)
    {
        $sql = "DELETE FROM produit
                WHERE id_produit = :id_produit
                AND id_utilisateur = :id_utilisateur";

        $db = Config::getConnexion();
        $query = $db->prepare($sql);

        return $query->execute([
            'id_produit' => $idProduit,
            'id_utilisateur' => $idUtilisateur
        ]);
    }

    public function updateProduitByUtilisateur($produit, $idProduit, $idUtilisateur)
{
    $sql = "UPDATE produit
            SET nom = :nom,
                prix = :prix,
                promo = :promo,
                image = :image,
                allergene = :allergene,
                benefices = :benefices,
                calories = :calories,
                date_ajout = :date_ajout,
                id_categorie = :id_categorie
            WHERE id_produit = :id_produit
            AND id_utilisateur = :id_utilisateur";

    $db = Config::getConnexion();
    $query = $db->prepare($sql);

    $result = $query->execute([
        'nom' => $produit->getNom(),
        'prix' => $produit->getPrix(),
        'promo' => $produit->getPromo(),
        'image' => $produit->getImage(),
        'allergene' => $produit->getAllergene(),
        'benefices' => $produit->getBenefices(),
        'calories' => $produit->getCalories(),
        'date_ajout' => $produit->getDateAjout(),
        'id_categorie' => $produit->getIdCategorie(),
        'id_produit' => $idProduit,
        'id_utilisateur' => $idUtilisateur
    ]);

    // ðŸ”¥ Mise à jour automatique des recettes
    if ($result) {
        require_once(__DIR__ . '/RecetteController.php');
        $recetteC = new RecetteController();
        $recetteC->updateCaloriesRecettesByProduit($idProduit);
    }

    return $result;
}

    public function getProduitDetailsByIdAndUtilisateur($idProduit, $idUtilisateur)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom AS nom_categorie
                FROM produit p
                LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                WHERE p.id_produit = :id_produit
                AND p.id_utilisateur = :id_utilisateur";

        $db = Config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'id_produit' => $idProduit,
            'id_utilisateur' => $idUtilisateur
        ]);

        return $query->fetch(PDO::FETCH_ASSOC);
    }

public function setProduitPromo($idProduit, $nouveauPrix)
{
    $db = Config::getConnexion();

    try {
        $sqlGet = "SELECT prix FROM produit WHERE id_produit = :id";
        $stmtGet = $db->prepare($sqlGet);
        $stmtGet->execute([
            'id' => $idProduit
        ]);

        $produit = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if (!$produit) {
            return "Produit introuvable.";
        }

        $prixNormal = (float)$produit['prix'];
        $nouveauPrix = (float)$nouveauPrix;

        if ($nouveauPrix <= 0) {
            return "Le prix promo doit être supérieur à 0.";
        }

        if ($nouveauPrix >= $prixNormal) {
            return "Le prix promo doit être inférieur au prix normal (" . number_format($prixNormal, 2, '.', ' ') . " DT).";
        }

        $sql = "UPDATE produit SET promo = :promo WHERE id_produit = :id_produit";
        $stmt = $db->prepare($sql);

        $result = $stmt->execute([
            'promo' => $nouveauPrix,
            'id_produit' => $idProduit
        ]);

        

        return true;

    } catch (Exception $e) {
        return "Erreur : " . $e->getMessage();
    }
}

public function annulerPromoProduit($idProduit)
{
    $sql = "UPDATE produit SET promo = NULL WHERE id_produit = :id_produit";
    $db = Config::getConnexion();
    $stmt = $db->prepare($sql);

    return $stmt->execute([
        'id_produit' => $idProduit
    ]);
}


}
?>
