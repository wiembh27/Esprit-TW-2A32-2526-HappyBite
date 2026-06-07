<?php

require_once __DIR__ . '/../config.php';

class ControllerIA
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = config::getConnexion();
    }

    public function analyserSuiviJournalier($id_profil_sante, $date_jour)
    {
        // 1. Vérifier si l'analyse du jour existe déjà
        if ($this->analyseExisteDeja($id_profil_sante, $date_jour)) {
            return [
                "success" => false,
                "message" => "L'analyse IA de ce jour a déjà été effectuée."
            ];
        }

        // 2. Récupérer le profil santé
        $profil = $this->getProfilSante($id_profil_sante);

        if (!$profil) {
            return [
                "success" => false,
                "message" => "Profil santé introuvable."
            ];
        }

        // 3. Récupérer le suivi journalier
        $suivi = $this->getSuiviJournalier($id_profil_sante, $date_jour);

        if (!$suivi) {
            return [
                "success" => false,
                "message" => "Aucun suivi journalier trouvé pour cette date."
            ];
        }

        // 4. Analyse intelligente simple
        $score = 0;
        $commentaires = [];

        $objectif = strtolower($profil['objectif']);

        $calories = (int)$suivi['calories'];
        $sommeil = (float)$suivi['sommeil_heures'];
        $pas = (int)$suivi['nbr_pas'];
        $activites = (int)$suivi['nbr_activites_sport'];
        $hydratation = (float)$suivi['hydratation_litre'];

        // Analyse selon objectif
        if ($objectif === "perte de poids") {
            if ($calories <= 300) {
                $score++;
                $commentaires[] = "Calories cohérentes avec l'objectif de perte de poids.";
            } else {
                $commentaires[] = "Calories un peu élevées pour l'objectif de perte de poids.";
            }
        } elseif ($objectif === "maintien") {
            if ($calories >= 300 && $calories <= 500) {
                $score++;
                $commentaires[] = "Calories cohérentes avec l'objectif de maintien.";
            } else {
                $commentaires[] = "Calories pas totalement alignées avec l'objectif de maintien.";
            }
        } elseif ($objectif === "gain de poids") {
            if ($calories >= 500) {
                $score++;
                $commentaires[] = "Calories cohérentes avec l'objectif de gain de poids.";
            } else {
                $commentaires[] = "Calories un peu faibles pour l'objectif de gain de poids.";
            }
        }

        // Hydratation
        if ($hydratation >= 1.5) {
            $score++;
            $commentaires[] = "Hydratation correcte.";
        } else {
            $commentaires[] = "Hydratation insuffisante.";
        }

        // Sommeil
        if ($sommeil >= 7) {
            $score++;
            $commentaires[] = "Sommeil suffisant.";
        } else {
            $commentaires[] = "Sommeil insuffisant.";
        }

        // Activité physique
        if ($pas >= 5000 || $activites >= 1) {
            $score++;
            $commentaires[] = "Activité physique satisfaisante.";
        } else {
            $commentaires[] = "Activité physique faible.";
        }

        // Décision finale
        if ($score >= 3) {
            $points = 10;
            $resultat = "positif";
            $message = "Bravo ! Le suivi journalier respecte globalement le profil santé.";
        } else {
            $points = -10;
            $resultat = "negatif";
            $message = "Le suivi journalier n'est pas assez aligné avec le profil santé.";
        }

        // 5. Mettre à jour les points
        $this->modifierPoints($id_profil_sante, $points);

        // 6. Enregistrer dans gamification_log
        $this->enregistrerAnalyse(
            $id_profil_sante,
            $date_jour,
            $points,
            $resultat,
            implode(" ", $commentaires)
        );

        return [
            "success" => true,
            "message" => $message,
            "points" => $points,
            "score" => $score,
            "commentaires" => $commentaires
        ];
    }

    private function analyseExisteDeja($id_profil_sante, $date_jour)
    {
        $sql = "SELECT id FROM gamification_log 
                WHERE id_profil_sante = :id_profil_sante 
                AND date_analyse = :date_jour";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_profil_sante' => $id_profil_sante,
            ':date_jour' => $date_jour
        ]);

        return $stmt->fetch() ? true : false;
    }

    private function getProfilSante($id_profil_sante)
    {
        $sql = "SELECT * FROM profil_sante WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id_profil_sante
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getSuiviJournalier($id_profil_sante, $date_jour)
    {
        $sql = "SELECT * FROM suivi_journalier 
                WHERE id_profil_sante = :id_profil_sante 
                AND date_jour = :date_jour";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_profil_sante' => $id_profil_sante,
            ':date_jour' => $date_jour
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function modifierPoints($id_profil_sante, $points)
    {
        $sql = "UPDATE profil_sante 
                SET points = points + :points 
                WHERE id = :id_profil_sante";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':points' => $points,
            ':id_profil_sante' => $id_profil_sante
        ]);
    }

    private function enregistrerAnalyse($id_profil_sante, $date_jour, $points, $resultat, $commentaire)
    {
        $sql = "INSERT INTO gamification_log 
                (id_profil_sante, date_analyse, points_attribues, resultat, commentaire)
                VALUES 
                (:id_profil_sante, :date_analyse, :points_attribues, :resultat, :commentaire)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_profil_sante' => $id_profil_sante,
            ':date_analyse' => $date_jour,
            ':points_attribues' => $points,
            ':resultat' => $resultat,
            ':commentaire' => $commentaire
        ]);
    }
}