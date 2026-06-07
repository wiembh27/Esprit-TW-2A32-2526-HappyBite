<?php

require_once __DIR__ . '/../config/Database.php';

class SuiviJournalierController
{
    private $db;
//Ouvre la connexion à la base de données.
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
//Redirige vers la page principale du suivi santé.
    private function redirect($id_utilisateur)
    {
        header("Location: index.php?action=userHealthSpace&id_utilisateur=" . $id_utilisateur);
        exit();
    }
//Récupère l’ID du profil santé lié à un utilisateur.
    private function getProfilId($id_utilisateur)
    {
        $stmt = $this->db->prepare("SELECT id FROM profil_sante WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $id_utilisateur]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['id'] : null;
    }

    /* =========================
       CREATE
    ========================= */
//Crée un suivi journalier.
public function create($id_utilisateur)
{
    $id_profil = $this->getProfilId($id_utilisateur);

    if (!$id_profil) {
        die("Crée un profil santé d'abord.");
    }

    // dernier suivi
    $lastSuivi = $this->getLastSuivi($id_utilisateur);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $date = date('Y-m-d');

        $check = $this->db->prepare("
            SELECT id FROM suivi_journalier
            WHERE id_profil_sante = :p AND date_jour = :d
        ");

        $check->execute([
            'p' => $id_profil,
            'd' => $date
        ]);

        if ($check->fetch()) {
            $this->redirect($id_utilisateur);
        }

        $stmt = $this->db->prepare("
            INSERT INTO suivi_journalier
            (id_profil_sante, date_jour, poids, calories, sommeil_heures, nbr_pas, nbr_activites_sport, hydratation_litre)
            VALUES
            (:p, :d, :poids, :cal, :som, :pas, :sport, :hydr)
        ");

        $stmt->execute([
            'p' => $id_profil,
            'd' => $date,
            'poids' => $_POST['poids'] ?? null,
            'cal' => $_POST['calories'] ?? null,
            'som' => $_POST['sommeil_heures'] ?? null,
            'pas' => $_POST['nbr_pas'] ?? null,
            'sport' => $_POST['nbr_activites_sport'] ?? null,
            'hydr' => $_POST['hydratation_litre'] ?? null
        ]);

        $this->redirect($id_utilisateur);
    }

    include __DIR__ . '/../Views/FrontOffice/createSuivi.php';
}

    /* =========================
       LIST USER
    ========================= */
//Récupère un utilisateur.
public function getUser($id)
{
    $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Utilisateur introuvable");
    }

    return $user;
}
//recupere tout les suivis d'un utilisateur
    public function getSuiviUser($id_utilisateur)
    {
        $stmt = $this->db->prepare("
            SELECT sj.*
            FROM suivi_journalier sj
            JOIN profil_sante ps ON sj.id_profil_sante = ps.id
            WHERE ps.id_utilisateur = :id
            ORDER BY sj.date_jour DESC
        ");

        $stmt->execute(['id' => $id_utilisateur]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       EDIT (AFFICHAGE FORM)
    ========================= */
    //affiche le formulaire d'edition d'un suivi
    public function edit($id)
{
    $stmt = $this->db->prepare("SELECT * FROM suivi_journalier WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $suivi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$suivi) {
        die("Suivi introuvable");
    }

    $id_utilisateur = $_GET['id_utilisateur'] ?? null;
    

    include __DIR__ . '/../Views/FrontOffice/editSuivi.php';
}
//lister tout les utilisateur avec leur profil sante
public function listUsersBackoffice()
{
    
  $stmt = $this->db->query("
SELECT 
    u.id_utilisateur AS id_utilisateur,
    ps.id AS id_profil_sante,
    u.prenom,
    u.nom,
    u.email,
    ps.taille,
    ps.poids_actuel,
    ps.objectif,
    ps.allergenes,
    ps.carences,
    ps.maladies,
    ps.date_mise_a_jour
FROM utilisateur u
INNER JOIN profil_sante ps 
    ON ps.id_utilisateur = u.id_utilisateur

    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    /* =========================
       UPDATE (POST)
    ========================= */
//met a jour un suivi
public function update($id)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $stmt = $this->db->prepare("
            UPDATE suivi_journalier
            SET 
                poids = :p,
                calories = :c,
                sommeil_heures = :s,
                nbr_pas = :n,
                nbr_activites_sport = :a,
                hydratation_litre = :h
            WHERE id = :id
        ");

        $stmt->execute([
            
            'p' => $_POST['poids'],
            'c' => $_POST['calories'],
            's' => $_POST['sommeil_heures'],
            'n' => $_POST['nbr_pas'],
            'a' => $_POST['nbr_activites_sport'],
            'h' => $_POST['hydratation_litre'],
            'id' => $id
        ]);

        $id_utilisateur = $_GET['id_utilisateur'] ?? null;

        header("Location: index.php?action=userHealthSpace&id_utilisateur=" . $id_utilisateur);
        exit();
    }
}
//supprimer un suivi
 public function delete($id, $id_utilisateur)
{
    $sql = "DELETE FROM suivi_journalier WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id]);

   header("Location: index.php?action=userHealthSpace&id_utilisateur=" . $id_utilisateur);
    exit();
}  
//page principale du suivi utilisateur
public function list($id_utilisateur)
{
    $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$id_utilisateur]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $this->db->prepare("SELECT * FROM profil_sante WHERE id_utilisateur = ?");
    $stmt->execute([$id_utilisateur]);
    $profil = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($profil) {
        foreach (['allergenes', 'carences', 'maladies'] as $key) {
            $data = json_decode($profil[$key], true);
            if (!is_array($data)) $data = [];
            $profil[$key] = implode(', ', $data);
        }
    }

    $suivis = $this->getSuiviUser($id_utilisateur);

    // 🔥 FILTER DATE (ICI IMPORTANT)
    $date = $_GET['date'] ?? null;

    if ($date) {
        $suivis = array_filter($suivis, fn($s) => $s['date_jour'] === $date);
    }

    // 🔥 SORT
    $suivis = $this->sortSuivis($suivis, [
        'poids' => $_GET['sort_poids'] ?? null,
        'calories' => $_GET['sort_calories'] ?? null,
        'sommeil' => $_GET['sort_sommeil'] ?? null,
        'pas' => $_GET['sort_pas'] ?? null,
    ]);

    require __DIR__ . '/../Views/FrontOffice/user_health_space.php';
}

    public function searchUsersBackoffice($search)
{
    $search = strtolower(trim($search));

$stmt = $this->db->prepare("
    SELECT 
        u.id_utilisateur AS id_utilisateur,
        ps.id AS id_profil_sante,
        u.prenom,
        u.nom,
        u.email,
        ps.taille,
        ps.poids_actuel,
        ps.objectif,
        ps.allergenes,
        ps.carences,
        ps.maladies,
        ps.date_mise_a_jour
    FROM utilisateur u
    INNER JOIN profil_sante ps 
        ON ps.id_utilisateur = u.id_utilisateur
    WHERE 
        LOWER(u.prenom) LIKE :search
        OR LOWER(u.nom) LIKE :search
        OR LOWER(u.email) LIKE :search
        OR CAST(ps.id AS CHAR) LIKE :search
");

    $stmt->execute([
        'search' => '%' . $search . '%'
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getStatsProfilsVsNon()
{
    // utilisateurs avec profil
    $stmt1 = $this->db->query("
        SELECT COUNT(DISTINCT id_utilisateur) as total 
        FROM profil_sante
    ");
    $avec = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];

    // total utilisateurs
    $stmt2 = $this->db->query("
        SELECT COUNT(*) as total 
        FROM utilisateur
    ");
    $total = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];

    $sans = $total - $avec;

    return [
        'avec' => $avec,
        'sans' => $sans
    ];
}

//récupère le dernier suivi
public function getLastSuivi($id_utilisateur)
{
    $stmt = $this->db->prepare("
        SELECT sj.*
        FROM suivi_journalier sj
        JOIN profil_sante ps ON sj.id_profil_sante = ps.id
        WHERE ps.id_utilisateur = :id
        ORDER BY sj.date_jour DESC
        LIMIT 1
    ");

    $stmt->execute(['id' => $id_utilisateur]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
private function sortSuivis(array $suivis, array $params)
{
    $field = null;
    $direction = 'desc';

    if (!empty($params['poids'])) {
        $field = 'poids';
        $direction = $params['poids'];
    } elseif (!empty($params['calories'])) {
        $field = 'calories';
        $direction = $params['calories'];
    } elseif (!empty($params['sommeil'])) {
        $field = 'sommeil_heures';
        $direction = $params['sommeil'];
    } elseif (!empty($params['pas'])) {
        $field = 'nbr_pas';
        $direction = $params['pas'];
    }

    // TRI PAR DEFAUT = date DESC
    if (!$field) {
        usort($suivis, fn($a, $b) =>
            strtotime($b['date_jour']) <=> strtotime($a['date_jour'])
        );
        return $suivis;
    }

    usort($suivis, function ($a, $b) use ($field, $direction) {
        return $direction === 'asc'
            ? $a[$field] <=> $b[$field]
            : $b[$field] <=> $a[$field];
    });

    return $suivis;
}
public function getConseil($id)
{
    header('Content-Type: application/json; charset=utf-8');

    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

    try {
        $stmt = $this->db->prepare("SELECT * FROM suivi_journalier WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            echo json_encode(['error' => 'Aucune donnée'], $jsonFlags);
            exit();
        }

        $id_profil = $data['id_profil_sante'] ?? null;
        if ($id_profil === null || $id_profil === '') {
            echo json_encode(['error' => 'Suivi incomplet'], $jsonFlags);
            exit();
        }

        $stmt = $this->db->prepare('SELECT * FROM profil_sante WHERE id = ?');
        $stmt->execute([$id_profil]);
        $profil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profil) {
            echo json_encode(['error' => 'Profil introuvable'], $jsonFlags);
            exit();
        }

        $objectif = (string) ($profil['objectif'] ?? 'maintenir');

        $aiConseil = $this->getAIConseil($data, $objectif);

        echo json_encode([
            'conseil_ai' => nl2br($aiConseil, false),
            'score' => 70,
            'niveau' => 'Correct',
        ], $jsonFlags);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur technique'], $jsonFlags);
    }

    exit();
}
private function getHistorique($id_profil)
{
    $stmt = $this->db->prepare("
        SELECT * FROM suivi_journalier
        WHERE id_profil_sante = ?
        ORDER BY date_jour DESC
        LIMIT 7
    ");

    $stmt->execute([$id_profil]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Conseils sans appel API si aucune clé OpenAI n’est configurée.
 */
private function getFallbackFitnessConseil(array $data, string $objectif): string
{
    $cal = $data['calories'] ?? '—';
    $som = $data['sommeil_heures'] ?? '—';
    $pas = $data['nbr_pas'] ?? '—';
    $sport = $data['nbr_activites_sport'] ?? '—';
    $hyd = $data['hydratation_litre'] ?? '—';

    return <<<TXT
- Conseil 1 : Objectif « {$objectif} » — ajustez progressivement calories et activité pour rester régulier.
- Conseil 2 : Hydratation ({$hyd}) — visez une eau répartie sur la journée ; évitez de tout boire d’un coup.
- Conseil 3 : Activité — pas : {$pas}, séances sport : {$sport} ; ajoutez une courte marche si la journée était calme.
- Conseil 4 : Sommeil ({$som} h) — gardez des horaires stables et limitez les écrans avant le coucher.
- Motivation : Un jour à la fois : la régularité bat la perfection.
TXT;
}

private function getAIConseil($data, $objectif)
{
    $apiKey = trim((string) (getenv('OPENAI_API_KEY') ?: ''));
    if ($apiKey === '' && defined('OPENAI_API_KEY')) {
        $apiKey = trim((string) constant('OPENAI_API_KEY'));
    }
    if ($apiKey === '') {
        return $this->getFallbackFitnessConseil($data, $objectif);
    }

    if (!function_exists('curl_init')) {
        return $this->getFallbackFitnessConseil($data, $objectif);
    }

    $cal = (string) ($data['calories'] ?? '');
    $som = (string) ($data['sommeil_heures'] ?? '');
    $pas = (string) ($data['nbr_pas'] ?? '');
    $sport = (string) ($data['nbr_activites_sport'] ?? '');
    $hyd = (string) ($data['hydratation_litre'] ?? '');

$prompt = <<<EOT
Tu es un coach fitness intelligent.

OBJECTIF UTILISATEUR : {$objectif}

MISSION :
Analyse les données et compare avec l’objectif.
Si une valeur ne correspond pas à l’objectif → donne un conseil clair pour corriger.

RÈGLES :
- Maximum 4 conseils
- Chaque conseil doit être COURT et PRÉCIS
- Utilise des chiffres (ex: 2L eau, 8000 pas)
- Ignore les données correctes
- Ne parle que des problèmes
- Ton simple, direct

FORMAT OBLIGATOIRE :

- Conseil 1 : ...

- Conseil 2 : ...

- Conseil 3 : ...

- Conseil 4 : ...

- Motivation : phrase courte motivante

DONNÉES :
- Calories : {$cal}
- Sommeil : {$som} heures
- Pas : {$pas}
- Sport : {$sport}
- Hydratation : {$hyd} L

RAPPEL OBJECTIF :
- Perte de poids → réduire calories + augmenter activité
- Prise de masse → augmenter calories + sport
- Maintien → équilibre général
EOT;
    $url = "https://api.openai.com/v1/chat/completions";

    $postData = [
        "model" => "gpt-4o-mini",
        "messages" => [
            [
                "role" => "system",
                "content" => "Tu es un coach fitness utile et motivant."
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.7
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return $this->getFallbackFitnessConseil($data, $objectif);
    }

    curl_close($ch);

    $result = json_decode($response, true);

    $content = $result['choices'][0]['message']['content'] ?? null;
    if (is_string($content) && $content !== '') {
        return $content;
    }

    return $this->getFallbackFitnessConseil($data, $objectif);
}

}