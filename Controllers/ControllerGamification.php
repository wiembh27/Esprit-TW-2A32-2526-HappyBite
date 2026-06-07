<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

class ControllerGamification
{
    private PDO $pdo;
    private string $apiKey;

    public function __construct()
    {
        $this->pdo = Database::getConnection();

        $this->apiKey = hb_openai_api_key();
    }

    public function analyserSuiviDuJour(int $idProfilSante, int $idUtilisateur): array
    {
        $this->ensureGamificationSchema();

        $dateJour = date('Y-m-d');

        $profil = $this->getProfilSante($idProfilSante, $idUtilisateur);

        if (!$profil) {
            return [
                'success' => false,
                'code' => 'profile_not_found'
            ];
        }

        if ($this->analyseExisteDeja($idProfilSante, $dateJour)) {
            return [
                'success' => false,
                'code' => 'already_done'
            ];
        }

        $suivi = $this->getSuiviJournalier($idProfilSante, $dateJour);

        if (!$suivi) {
            return [
                'success' => false,
                'code' => 'suivi_not_found'
            ];
        }

        $analyse = $this->analyserAvecOpenAI($profil, $suivi);

        if (!$analyse['success']) {
            // Repli : l’API a échoué (clé invalide, réseau, JSON…) — évite un faux « clé manquante »
            // (l’ancien test sur le mot « manquante » attrapait trop d’erreurs OpenAI).
            $analyse = $this->analyserSuiviSansApi($profil, $suivi);
            $note = ' — API OpenAI non utilisée (erreur ou clé invalide) ; score calculé en local.';
            $analyse['commentaire'] = ($analyse['commentaire'] ?? '') . $note;
        }

        $points = (int) ($analyse['points'] ?? -10);

        // Sécurité : on force seulement +10 ou -10.
        if ($points !== 10 && $points !== -10) {
            $points = -10;
        }

        $resultat = $points > 0 ? 'positif' : 'negatif';
        $commentaire = (string) ($analyse['commentaire'] ?? '');
        $score = (int) ($analyse['score'] ?? 0);

        $this->modifierPoints($idProfilSante, $points);

        $this->enregistrerAnalyse(
            $idProfilSante,
            $dateJour,
            $points,
            $resultat,
            $commentaire
        );

        return [
            'success' => true,
            'points' => $points,
            'resultat' => $resultat,
            'score' => $score,
            'commentaire' => $commentaire
        ];
    }

    private function getProfilSante(int $idProfilSante, int $idUtilisateur): ?array
    {
        $sql = 'SELECT * FROM profil_sante
                WHERE id = :id_profil_sante
                AND id_utilisateur = :id_utilisateur
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_profil_sante' => $idProfilSante,
            'id_utilisateur' => $idUtilisateur
        ]);

        $profil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($profil)) {
            return null;
        }

        foreach (['allergenes', 'carences', 'maladies'] as $key) {
            if (isset($profil[$key]) && is_string($profil[$key])) {
                $decoded = json_decode($profil[$key], true);
                if (is_array($decoded)) {
                    $profil[$key] = implode(', ', $decoded);
                }
            }
        }

        return $profil;
    }

    /**
     * Crée la table gamification_log et la colonne profil_sante.points si besoin (évite erreurs SQL silencieuses).
     */
    private function ensureGamificationSchema(): void
    {
        try {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS gamification_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_profil_sante INT NOT NULL,
                    date_analyse DATE NOT NULL,
                    points_attribues INT NOT NULL,
                    resultat VARCHAR(32) NOT NULL,
                    commentaire TEXT,
                    UNIQUE KEY uq_profil_date (id_profil_sante, date_analyse)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (Throwable $e) {
            // schéma géré manuellement ou autre moteur
        }

        try {
            $dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
            if (!is_string($dbName) || $dbName === '') {
                return;
            }
            $chk = $this->pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
            );
            $chk->execute([
                'db' => $dbName,
                'tbl' => 'profil_sante',
                'col' => 'points',
            ]);
            if ((int) $chk->fetchColumn() === 0) {
                $this->pdo->exec('ALTER TABLE profil_sante ADD COLUMN points INT NOT NULL DEFAULT 0');
            }
        } catch (Throwable $e) {
            // pas de droits ALTER : l’appelant verra l’erreur SQL sur UPDATE
        }
    }

    private function getSuiviJournalier(int $idProfilSante, string $dateJour): ?array
    {
        $sql = 'SELECT * FROM suivi_journalier
                WHERE id_profil_sante = :id_profil_sante
                AND date_jour = :date_jour
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_profil_sante' => $idProfilSante,
            'date_jour' => $dateJour
        ]);

        $suivi = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($suivi) ? $suivi : null;
    }

    private function analyseExisteDeja(int $idProfilSante, string $dateJour): bool
    {
        $sql = 'SELECT id FROM gamification_log
                WHERE id_profil_sante = :id_profil_sante
                AND date_analyse = :date_analyse
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_profil_sante' => $idProfilSante,
            'date_analyse' => $dateJour
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    private function analyserAvecOpenAI(array $profil, array $suivi): array
    {
        if ($this->apiKey === '') {
            return $this->analyserSuiviSansApi($profil, $suivi);
        }

        $objectif = (string) ($profil['objectif'] ?? 'non précisé');
        $allergenes = (string) ($profil['allergenes'] ?? 'aucun');
        $carences = (string) ($profil['carences'] ?? 'aucune');
        $maladies = (string) ($profil['maladies'] ?? 'aucune');

        $poids = (string) ($suivi['poids'] ?? 'non précisé');
        $calories = (string) ($suivi['calories'] ?? 'non précisé');
        $sommeil = (string) ($suivi['sommeil_heures'] ?? 'non précisé');
        $pas = (string) ($suivi['nbr_pas'] ?? 'non précisé');
        $sport = (string) ($suivi['nbr_activites_sport'] ?? 'non précisé');
        $hydratation = (string) ($suivi['hydratation_litre'] ?? 'non précisé');
        $dateJour = (string) ($suivi['date_jour'] ?? date('Y-m-d'));

        $prompt = "Tu es HealthQuest AI, une IA de gamification santé pour l'application HappyBite.

Ta mission :
Évaluer le suivi journalier d'un utilisateur par rapport à son profil santé et à son objectif.
Tu dois décider si l'utilisateur gagne +10 points ou perd -10 points.

Important :
- Tu ne donnes pas de diagnostic médical.
- Tu ne remplaces pas un médecin.
- Tu analyses seulement la cohérence générale des habitudes quotidiennes.
- Tu restes motivant, prudent et simple.
- Tu dois répondre uniquement en JSON valide, sans texte avant ni après.

Profil santé :
Objectif : $objectif
Allergènes : $allergenes
Carences : $carences
Maladies : $maladies

Suivi journalier du $dateJour :
Poids : $poids kg
Calories : $calories kcal
Sommeil : $sommeil heures
Nombre de pas : $pas
Nombre d'activités sportives : $sport
Hydratation : $hydratation litres

Critères d'analyse :
1. Vérifie si les calories sont cohérentes avec l'objectif.
2. Vérifie si le sommeil est suffisant.
3. Vérifie si l'hydratation est correcte.
4. Vérifie si l'activité physique est satisfaisante.
5. Prends en compte les maladies, allergènes et carences de façon prudente.
6. Si le suivi est globalement cohérent, attribue +10 points.
7. Si le suivi est insuffisant ou incohérent, attribue -10 points.

Format exact attendu :
{
  \"respecte_objectif\": true,
  \"points\": 10,
  \"score\": 8,
  \"niveau\": \"excellent\",
  \"commentaire\": \"Message court, positif et motivant.\",
  \"details\": {
    \"calories\": \"...\",
    \"sommeil\": \"...\",
    \"hydratation\": \"...\",
    \"activite\": \"...\",
    \"sante\": \"...\"
  }
}";

        $reponse = $this->appelOpenAI($prompt);

        if (str_starts_with($reponse, 'Erreur')) {
            return [
                'success' => false,
                'message' => $reponse
            ];
        }

        $clean = trim($reponse);
        $clean = preg_replace('/^```json\s*/', '', $clean);
        $clean = preg_replace('/^```\s*/', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim((string) $clean);

        $data = json_decode($clean, true);

        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => 'Réponse IA invalide : JSON impossible à lire.'
            ];
        }

        if (!isset($data['points'])) {
            return [
                'success' => false,
                'message' => 'Réponse IA invalide : points manquants.'
            ];
        }

        return [
            'success' => true,
            'respecte_objectif' => (bool) ($data['respecte_objectif'] ?? false),
            'points' => (int) $data['points'],
            'score' => (int) ($data['score'] ?? 0),
            'niveau' => (string) ($data['niveau'] ?? ''),
            'commentaire' => (string) ($data['commentaire'] ?? 'Analyse IA terminée.'),
            'details' => $data['details'] ?? []
        ];
    }

    /**
     * Sans clé OpenAI : règles simples sur le suivi du jour (démo / XAMPP). Pas un avis médical.
     */
    private function analyserSuiviSansApi(array $profil, array $suivi): array
    {
        $objectif = strtolower((string) ($profil['objectif'] ?? ''));
        $cal = (float) ($suivi['calories'] ?? 0);
        $som = (float) ($suivi['sommeil_heures'] ?? 0);
        $pas = (float) ($suivi['nbr_pas'] ?? 0);
        $hyd = (float) ($suivi['hydratation_litre'] ?? 0);
        $sport = (int) ($suivi['nbr_activites_sport'] ?? 0);

        $checks = [];
        $checks['sommeil'] = $som >= 6.0 && $som <= 10.5;
        $checks['hydratation'] = $hyd >= 1.2;
        $checks['pas'] = $pas >= 4000;
        $checks['sport'] = $sport >= 1;

        $calOk = true;
        if ($cal > 0) {
            if (str_contains($objectif, 'perte')) {
                $calOk = $cal <= 2800;
            } elseif (str_contains($objectif, 'prise') || str_contains($objectif, 'masse')) {
                $calOk = $cal >= 1500;
            } elseif (str_contains($objectif, 'maintien')) {
                $calOk = $cal >= 1400 && $cal <= 3200;
            }
        } else {
            $calOk = false;
        }
        $checks['calories'] = $calOk;

        $good = count(array_filter($checks));
        $positive = $good >= 3;

        $points = $positive ? 10 : -10;
        $commentaire = $positive
            ? 'Analyse automatique (sans OpenAI) : l’ensemble de vos indicateurs du jour est plutôt cohérent. Continuez ainsi.'
            : 'Analyse automatique (sans OpenAI) : plusieurs axes (sommeil, hydratation, pas, sport ou calories) méritent un petit réajustement.';

        return [
            'success' => true,
            'respecte_objectif' => $positive,
            'points' => $points,
            'score' => $positive ? 7 : 4,
            'niveau' => $positive ? 'correct' : 'a_ameliorer',
            'commentaire' => $commentaire,
            'details' => $checks,
        ];
    }

    private function appelOpenAI(string $prompt): string
    {
        $data = [
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es une IA de gamification santé. Tu réponds uniquement en JSON valide.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.2
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return 'Erreur cURL : ' . $error;
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response) || $response === '') {
            return 'Erreur : réponse OpenAI vide.';
        }

        $result = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $result['error']['message'] ?? 'Erreur API OpenAI.';
            return 'Erreur OpenAI : ' . $message;
        }

        if (isset($result['error']['message'])) {
            return 'Erreur OpenAI : ' . $result['error']['message'];
        }

        return (string) ($result['choices'][0]['message']['content'] ?? 'Erreur : réponse vide.');
    }

    private function modifierPoints(int $idProfilSante, int $points): void
    {
        $sql = 'UPDATE profil_sante
                SET points = COALESCE(points, 0) + :delta
                WHERE id = :id_profil_sante';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'delta' => $points,
            'id_profil_sante' => $idProfilSante
        ]);
    }

    private function enregistrerAnalyse(
        int $idProfilSante,
        string $dateJour,
        int $points,
        string $resultat,
        string $commentaire
    ): void {
        $sql = 'INSERT INTO gamification_log
                (id_profil_sante, date_analyse, points_attribues, resultat, commentaire)
                VALUES
                (:id_profil_sante, :date_analyse, :points_attribues, :resultat, :commentaire)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id_profil_sante' => $idProfilSante,
            'date_analyse' => $dateJour,
            'points_attribues' => $points,
            'resultat' => $resultat,
            'commentaire' => $commentaire
        ]);
    }
}