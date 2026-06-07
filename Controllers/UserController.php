<?php

require_once __DIR__ . '/../Config.php';

class UserController
{
    private $db;

    public function __construct()
    {
        $database = new Config();
        $this->db = $database->getConnexion();
    }

    public function listUsersHealth()
    {
        $sql = "SELECT id, prenom, nom, email 
                FROM utilisateur 
                ORDER BY id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../Views/FrontOffice/users_dashboard.php';
    }

    public function userHealthSpace($id_utilisateur)
    {
        $sqlUser = "SELECT id, prenom, nom, email 
                    FROM utilisateur 
                    WHERE id = :id 
                    LIMIT 1";

        $stmtUser = $this->db->prepare($sqlUser);
        $stmtUser->execute(['id' => $id_utilisateur]);

        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("Utilisateur introuvable.");
        }

        // Profil santé
        $sqlProfil = "SELECT * FROM profil_sante 
                      WHERE id_utilisateur = :id_utilisateur 
                      LIMIT 1";

        $stmtProfil = $this->db->prepare($sqlProfil);
        $stmtProfil->execute(['id_utilisateur' => $id_utilisateur]);

        $profil = $stmtProfil->fetch(PDO::FETCH_ASSOC);

        if ($profil) {
            $profil['allergenes'] = json_decode($profil['allergenes'], true) ?? [];
            $profil['carences']   = json_decode($profil['carences'], true) ?? [];
            $profil['maladies']   = json_decode($profil['maladies'], true) ?? [];

            $sqlSuivis = "SELECT * FROM suivi_journalier 
                          WHERE id_profil_sante = :id_profil_sante
                          ORDER BY date_jour DESC";

            $stmtSuivis = $this->db->prepare($sqlSuivis);
            $stmtSuivis->execute(['id_profil_sante' => $profil['id']]);

            $suivis = $stmtSuivis->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $suivis = [];
        }

        include __DIR__ . '/../Views/FrontOffice/user_health_space.php';
    }

    public function list($id_utilisateur)
    {
        $id_profil_sante = $this->getProfilIdByUserId($id_utilisateur);

        if (!$id_profil_sante) {
            die("Aucun profil santé trouvé.");
        }

        $sql = "SELECT * FROM suivi_journalier 
                WHERE id_profil_sante = :id_profil_sante
                ORDER BY date_jour DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_profil_sante' => $id_profil_sante]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}