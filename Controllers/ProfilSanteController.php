<?php

require_once __DIR__ . '/../Models/ProfilSante.php';
require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/UserNotificationService.php';

class ProfilSanteController
{
    private $db;

    public function __construct()
    {
        $this->db = (new Config())->getConnexion();
    }

    private function redirectHealth($id_utilisateur)
    {
        header("Location: index.php?action=userHealthSpace&id_utilisateur=" . $id_utilisateur);
        exit();
    }


  
    public function create($id_utilisateur)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $check = $this->db->prepare("SELECT id FROM profil_sante WHERE id_utilisateur = :id");
            $check->execute(['id' => $id_utilisateur]);

            if ($check->fetch()) {
                $this->redirectHealth($id_utilisateur);
            }

            $stmt = $this->db->prepare("
                INSERT INTO profil_sante
                (id_utilisateur, taille, poids_actuel, objectif, allergenes, carences, maladies)
                VALUES
                (:id, :t, :p, :o, :a, :c, :m)
            ");

            $stmt->execute([
                'id' => $id_utilisateur,
                't' => $_POST['taille'] ?? null,
                'p' => $_POST['poids_actuel'] ?? null,
                'o' => $_POST['objectif'] ?? null,
'a' => json_encode($_POST['allergenes'] ?? [], JSON_UNESCAPED_UNICODE),
'c' => json_encode($_POST['carences'] ?? [], JSON_UNESCAPED_UNICODE),
'm' => json_encode($_POST['maladies'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);

            try {
                user_notification_send_sante_welcome($this->db, (int) $id_utilisateur);
            } catch (Throwable $e) {
            }

            $this->redirectHealth($id_utilisateur);
        }

        include __DIR__ . '/../Views/FrontOffice/create.php';
    }

    public function edit($id_utilisateur)
    {
        $stmt = $this->db->prepare("SELECT * FROM profil_sante WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $id_utilisateur]);
        $profil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profil) die("Introuvable");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $stmt = $this->db->prepare("
                UPDATE profil_sante SET
                taille = :t,
                poids_actuel = :p,
                objectif = :o,
                allergenes = :a,
                carences = :c,
                maladies = :m
                WHERE id = :id
            ");

            $stmt->execute([
                't' => $_POST['taille'],
                'p' => $_POST['poids_actuel'],
                'o' => $_POST['objectif'],
'a' => json_encode($_POST['allergenes'] ?? [], JSON_UNESCAPED_UNICODE),
'c' => json_encode($_POST['carences'] ?? [], JSON_UNESCAPED_UNICODE),
'm' => json_encode($_POST['maladies'] ?? [], JSON_UNESCAPED_UNICODE),
                'id' => $profil['id']
            ]);

            $this->redirectHealth($id_utilisateur);
        }

        include __DIR__ . '/../Views/FrontOffice/edit.php';
    }

    public function delete($id_utilisateur)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $this->db->prepare("DELETE FROM profil_sante WHERE id_utilisateur = :id");
            $stmt->execute(['id' => $id_utilisateur]);
        }

        $this->redirectHealth($id_utilisateur);
    }
}