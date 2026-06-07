<?php
require_once(__DIR__ . '/../config/Database.php');
require_once(__DIR__ . '/../Models/Recette.php');

class RecetteController
{
    public function addRecette(Recette $recette)
    {
        $sql = "INSERT INTO recette (nom, description, calories, image, mise_en_avant) 
                VALUES (:nom, :description, :calories, :image, :mise_en_avant)";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $recette->getNom(),
                'description' => $recette->getDescription(),
                'calories' => $recette->getCalories(),
                'image' => $recette->getImage(),
                'mise_en_avant' => $recette->getMiseEnAvant()
            ]);

            return $db->lastInsertId();
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function listRecettes()
    {
        $sql = "SELECT * FROM recette ORDER BY id_recette DESC";
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

    public function showRecette($id)
    {
        $sql = "SELECT * FROM recette WHERE id_recette = :id";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id' => $id
            ]);

            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $recette = new Recette(
                    $data['nom'],
                    $data['description'],
                    isset($data['calories']) ? (int)$data['calories'] : 0,
                    $data['image'] ?? null
                );
                $recette->setIdRecette((int)$data['id_recette']);
                $recette->setMiseEnAvant((int)($data['mise_en_avant'] ?? 0));
                return $recette;
            }

            return null;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return null;
        }
    }

    public function getRecetteById($id)
    {
        $sql = "SELECT * FROM recette WHERE id_recette = :id";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->bindValue(':id', $id, PDO::PARAM_INT);
            $query->execute();
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function updateRecette(Recette $recette, $id)
    {
        $sql = "UPDATE recette
                SET nom = :nom,
                    description = :description,
                    calories = :calories,
                    image = :image,
                    mise_en_avant = :mise_en_avant
                WHERE id_recette = :id";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $recette->getNom(),
                'description' => $recette->getDescription(),
                'calories' => $recette->getCalories(),
                'image' => $recette->getImage(),
                'mise_en_avant' => $recette->getMiseEnAvant(),
                'id' => $id
            ]);
            return true;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function deleteRecette($id)
    {
        $sql = "DELETE FROM recette WHERE id_recette = :id";
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

    public function rechercherRecettes($motCle = "")
    {
        $sql = "SELECT * FROM recette WHERE nom LIKE :motCle ORDER BY id_recette DESC";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $motCle = "%" . $motCle . "%";
            $query->execute([
                'motCle' => $motCle
            ]);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function showRecetteDetails($id)
    {
        $sql = "SELECT * FROM recette WHERE id_recette = :id";
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

    public function ajouterProduitsRecette($idRecette, $produits)
    {
        $sql = "INSERT INTO recette_produit (id_recette, id_produit) VALUES (:id_recette, :id_produit)";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);

            foreach ($produits as $idProduit) {
                $query->execute([
                    'id_recette' => $idRecette,
                    'id_produit' => $idProduit
                ]);
            }

            return true;
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return false;
        }
    }

    public function supprimerProduitsRecette($idRecette)
    {
        $sql = "DELETE FROM recette_produit WHERE id_recette = :id_recette";
        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_recette' => $idRecette
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getProduitsByRecette($idRecette)
    {
        $sql = "SELECT p.*
                FROM produit p
                INNER JOIN recette_produit rp ON p.id_produit = rp.id_produit
                WHERE rp.id_recette = :id_recette";

        $db = Config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_recette' => $idRecette
            ]);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function calculerCaloriesRecette($produitsIds)
    {
        if (empty($produitsIds)) {
            return 0;
        }

        $db = Config::getConnexion();
        $placeholders = implode(',', array_fill(0, count($produitsIds), '?'));
        $sql = "SELECT SUM(calories) AS total_calories FROM produit WHERE id_produit IN ($placeholders)";

        try {
            $query = $db->prepare($sql);
            $query->execute($produitsIds);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            return (int)($result['total_calories'] ?? 0);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return 0;
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

    public function rechercherRecettesIntelligentes($idUtilisateur, $motCle = "")
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
                    $scoreParts[] = "(CASE WHEN EXISTS (
                        SELECT 1 FROM recette_produit rp_sc
                        INNER JOIN produit p_sc ON rp_sc.id_produit = p_sc.id_produit
                        WHERE rp_sc.id_recette = r.id_recette
                        AND p_sc.benefices LIKE :$paramName
                    ) THEN 1 ELSE 0 END)";
                    $params[$paramName] = "%" . $carence . "%";
                }

                $scoreCarencesSql = implode(" + ", $scoreParts);
            }

            $sql = "SELECT 
                        r.*,
                        ($scoreCarencesSql) AS score_carences
                    FROM recette r
                    WHERE 1=1";

            if (!empty($motCle)) {
                $sql .= " AND r.nom LIKE :motCle";
                $params['motCle'] = "%" . $motCle . "%";
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
                $sql .= " AND NOT EXISTS (
                    SELECT 1 FROM recette_produit rp_bad
                    INNER JOIN produit p_bad ON rp_bad.id_produit = p_bad.id_produit
                    WHERE rp_bad.id_recette = r.id_recette
                    AND p_bad.allergene IS NOT NULL
                    AND p_bad.allergene != ''
                    AND p_bad.allergene LIKE :$paramName
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
                $sql .= " AND r.calories >= :calorieMin";
                $params['calorieMin'] = $calorieMin;
            }

            if ($calorieMax !== null) {
                $sql .= " AND r.calories <= :calorieMax";
                $params['calorieMax'] = $calorieMax;
            }

            $sql .= " ORDER BY score_carences DESC, r.calories ASC, r.id_recette DESC";

            $query = $db->prepare($sql);
            $query->execute($params);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            return [];
        }
    }

    public function setRecetteMiseEnAvant($idRecette, $valeur = 1)
    {
        $sql = "UPDATE recette SET mise_en_avant = :mise_en_avant WHERE id_recette = :id_recette";
        $db = Config::getConnexion();
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            'mise_en_avant' => $valeur,
            'id_recette' => $idRecette
        ]);
   }
public function updateCaloriesRecettesByProduit($idProduit)
{
    $db = Config::getConnexion();

    $sql = "SELECT DISTINCT id_recette 
            FROM recette_produit 
            WHERE id_produit = :id_produit";

    $query = $db->prepare($sql);
    $query->execute(['id_produit' => $idProduit]);
    $recettes = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recettes as $recette) {

        $sqlTotal = "SELECT SUM(p.calories) AS total
                     FROM recette_produit rp
                     JOIN produit p ON rp.id_produit = p.id_produit
                     WHERE rp.id_recette = :id_recette";

        $q = $db->prepare($sqlTotal);
        $q->execute(['id_recette' => $recette['id_recette']]);
        $total = $q->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $sqlUpdate = "UPDATE recette 
                      SET calories = :calories 
                      WHERE id_recette = :id_recette";

        $u = $db->prepare($sqlUpdate);
        $u->execute([
            'calories' => $total,
            'id_recette' => $recette['id_recette']
        ]);
    }
}
}
?>