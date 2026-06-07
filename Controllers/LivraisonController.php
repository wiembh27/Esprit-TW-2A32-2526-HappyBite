<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

class LivraisonController
{
    private PDO $pdo;

    /** @var string|null cache du nom de colonne date dans `livraison` */
    private static ?string $colonneDate = null;

    /** @var bool|null */
    private static ?bool $hasCreatedAt = null;

    /** @var bool|null */
    private static ?bool $hasTransitColumns = null;

    public const PREPARATION_JOURS = 2;

    private const STATUT_PREPARATION = 'En préparation';
    private const STATUT_EN_COURS = 'En cours';
    private const STATUT_LIVREE = 'Livrée';
    private const STATUT_ANNULEE = 'Annulée';

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function nomColonneDateLivraison(): string
    {
        if (self::$colonneDate !== null) {
            return self::$colonneDate;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM livraison');
        if ($stmt === false) {
            throw new RuntimeException('Impossible de lire la structure de la table livraison.');
        }
        $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($fields === []) {
            throw new RuntimeException('Table livraison introuvable ou vide.');
        }

        $priorite = ['livraison_date', 'date_prevue', 'date_livraison', 'date'];
        foreach ($priorite as $nom) {
            if (in_array($nom, $fields, true)) {
                self::$colonneDate = $nom;
                return self::$colonneDate;
            }
        }

        foreach ($fields as $nom) {
            if (stripos((string) $nom, 'date') !== false) {
                self::$colonneDate = (string) $nom;
                return self::$colonneDate;
            }
        }

        throw new RuntimeException(
            'Table livraison : aucune colonne date reconnue. Colonnes trouvées : ' . implode(', ', $fields)
        );
    }

    private function hasCreatedAtColumn(): bool
    {
        if (self::$hasCreatedAt !== null) {
            return self::$hasCreatedAt;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
        );
        $stmt->execute(['t' => 'livraison', 'c' => 'created_at']);
        if ((int) $stmt->fetchColumn() > 0) {
            self::$hasCreatedAt = true;
            return true;
        }

        try {
            $this->pdo->exec(
                'ALTER TABLE livraison ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP'
            );
            self::$hasCreatedAt = true;
        } catch (Throwable $e) {
            self::$hasCreatedAt = false;
        }

        return self::$hasCreatedAt;
    }

    private function ensureTransitColumns(): bool
    {
        if (self::$hasTransitColumns !== null) {
            return self::$hasTransitColumns;
        }

        $this->hasCreatedAtColumn();
        $needed = ['transit_seconds', 'arrival_at'];
        foreach ($needed as $col) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
            );
            $stmt->execute(['t' => 'livraison', 'c' => $col]);
            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }
            try {
                if ($col === 'transit_seconds') {
                    $this->pdo->exec('ALTER TABLE livraison ADD COLUMN transit_seconds INT NULL AFTER created_at');
                } else {
                    $this->pdo->exec('ALTER TABLE livraison ADD COLUMN arrival_at DATETIME NULL AFTER transit_seconds');
                }
            } catch (Throwable $e) {
                self::$hasTransitColumns = false;

                return false;
            }
        }

        self::$hasTransitColumns = true;

        return true;
    }

    private function resolveArrivalAt(array $row): ?DateTimeImmutable
    {
        if (empty($row['arrival_at'])) {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $row['arrival_at']);
        if ($dt instanceof DateTimeImmutable) {
            return $dt;
        }
        try {
            return new DateTimeImmutable((string) $row['arrival_at']);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Enregistre la durée de trajet (OSRM) et calcule l'heure d'arrivée à la maison.
     *
     * @return array<string, mixed>
     */
    public function enregistrerDureeTransit(int $idLivraison, int $transitSeconds, ?array $commande = null): array
    {
        $this->ensureTransitColumns();
        $transitSeconds = max(60, min($transitSeconds, 86400));

        $stmt = $this->pdo->prepare('SELECT * FROM livraison WHERE id_livraison = ?');
        $stmt->execute([$idLivraison]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Livraison introuvable.');
        }

        if (!empty($row['arrival_at']) && (int) ($row['transit_seconds'] ?? 0) > 0) {
            return $this->buildTimelineState($row, $commande);
        }

        $created = $this->resolveCreatedAt($row, $commande);
        $prepEnd = $created->modify('+' . self::PREPARATION_JOURS . ' days');
        $arrival = $prepEnd->modify('+' . $transitSeconds . ' seconds');

        $colDate = $this->nomColonneDateLivraison();
        $sql = 'UPDATE livraison SET transit_seconds = ?, arrival_at = ?, `' . $colDate . '` = ? WHERE id_livraison = ?';
        $stmtU = $this->pdo->prepare($sql);
        $stmtU->execute([
            $transitSeconds,
            $arrival->format('Y-m-d H:i:s'),
            $arrival->format('Y-m-d'),
            $idLivraison,
        ]);

        $row['transit_seconds'] = $transitSeconds;
        $row['arrival_at'] = $arrival->format('Y-m-d H:i:s');
        $row[$colDate] = $arrival->format('Y-m-d');

        $timeline = $this->buildTimelineState($row, $commande);
        $newStatut = (string) $timeline['statut'];
        if ((string) ($row['statut'] ?? '') !== $newStatut && $newStatut !== self::STATUT_ANNULEE) {
            $this->setStatutLivraison($idLivraison, $newStatut);
        }

        return $timeline;
    }

    public static function extraireDatePourAffichage(array $row): string
    {
        foreach (['livraison_date', 'date_prevue', 'date_livraison', 'date'] as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return (string) $row[$k];
            }
        }
        foreach ($row as $k => $v) {
            if (is_string($k) && stripos($k, 'date') !== false && $v !== null && $v !== '') {
                return (string) $v;
            }
        }

        return '';
    }

    /**
     * @return array{
     *   phase: string,
     *   statut: string,
     *   progress_percent: int,
     *   route_progress: float,
     *   created_at: string,
     *   en_cours_at: string,
     *   arrival_at: string,
     *   created_ms: int,
     *   en_cours_ms: int,
     *   arrival_ms: int,
     *   eta_line: string,
     *   sub_line: string,
     *   show_car_motion: bool,
     *   needs_route_calc: bool
     * }
     */
    public function buildTimelineState(array $row, ?array $commande = null): array
    {
        $this->ensureTransitColumns();
        $created = $this->resolveCreatedAt($row, $commande);
        $prepEnd = $created->modify('+' . self::PREPARATION_JOURS . ' days');
        $arrival = $this->resolveArrivalAt($row);
        $now = new DateTimeImmutable('now');

        $base = [
            'created_at' => $created->format('c'),
            'en_cours_at' => $prepEnd->format('c'),
            'arrival_at' => $arrival !== null ? $arrival->format('c') : '',
            'created_ms' => $created->getTimestamp() * 1000,
            'en_cours_ms' => $prepEnd->getTimestamp() * 1000,
            'arrival_ms' => $arrival !== null ? $arrival->getTimestamp() * 1000 : 0,
            'needs_route_calc' => false,
        ];

        $statutActuel = trim((string) ($row['statut'] ?? ''));
        if ($statutActuel === self::STATUT_ANNULEE) {
            return array_merge($base, [
                'phase' => 'annulee',
                'statut' => self::STATUT_ANNULEE,
                'progress_percent' => 0,
                'route_progress' => 0.0,
                'eta_line' => self::STATUT_ANNULEE,
                'sub_line' => 'Cette livraison a été annulée.',
                'show_car_motion' => false,
            ]);
        }

        if ($arrival !== null && $now >= $arrival) {
            return array_merge($base, [
                'phase' => 'livree',
                'statut' => self::STATUT_LIVREE,
                'progress_percent' => 100,
                'route_progress' => 1.0,
                'eta_line' => 'Commande livrée',
                'sub_line' => 'Livraison effectuée le ' . $arrival->format('d/m/Y à H:i'),
                'show_car_motion' => false,
            ]);
        }

        if ($now >= $prepEnd) {
            if ($arrival === null) {
                return array_merge($base, [
                    'phase' => 'encours',
                    'statut' => self::STATUT_EN_COURS,
                    'progress_percent' => 42,
                    'route_progress' => 0.0,
                    'eta_line' => 'En cours',
                    'sub_line' => 'Calcul de l\'heure d\'arrivée sur la carte…',
                    'show_car_motion' => false,
                    'needs_route_calc' => true,
                ]);
            }

            $totalCours = max(1, $arrival->getTimestamp() - $prepEnd->getTimestamp());
            $elapsedCours = max(0, $now->getTimestamp() - $prepEnd->getTimestamp());
            $routeProgress = min(1.0, $elapsedCours / $totalCours);
            $progressPercent = (int) round(40 + ($routeProgress * 60));

            return array_merge($base, [
                'phase' => 'encours',
                'statut' => self::STATUT_EN_COURS,
                'progress_percent' => min(99, $progressPercent),
                'route_progress' => $routeProgress,
                'eta_line' => 'Arrivée estimée : ' . $arrival->format('d/m/Y à H:i'),
                'sub_line' => 'Arrivée dans ' . self::formatRemainingLabel($arrival, $now),
                'show_car_motion' => true,
            ]);
        }

        $totalPrep = max(1, $prepEnd->getTimestamp() - $created->getTimestamp());
        $elapsedPrep = max(0, $now->getTimestamp() - $created->getTimestamp());
        $progressPercent = (int) round(min(39, ($elapsedPrep / $totalPrep) * 39));

        $subLine = 'Expédition dans ' . self::formatRemainingLabel($prepEnd, $now);
        if ($arrival !== null) {
            $subLine = 'Livraison estimée le ' . $arrival->format('d/m/Y à H:i') . ' — expédition dans '
                . self::formatRemainingLabel($prepEnd, $now);
        }

        return array_merge($base, [
            'phase' => 'preparation',
            'statut' => self::STATUT_PREPARATION,
            'progress_percent' => max(5, $progressPercent),
            'route_progress' => 0.0,
            'eta_line' => 'En préparation',
            'sub_line' => $subLine,
            'show_car_motion' => false,
            'needs_route_calc' => $arrival === null,
        ]);
    }

    private function resolveCreatedAt(array $row, ?array $commande): DateTimeImmutable
    {
        if (!empty($row['created_at'])) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $row['created_at']);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }
            try {
                return new DateTimeImmutable((string) $row['created_at']);
            } catch (Throwable $e) {
            }
        }

        if ($commande !== null) {
            foreach (['date', 'date_commande'] as $col) {
                if (!empty($commande[$col])) {
                    try {
                        return new DateTimeImmutable((string) $commande[$col]);
                    } catch (Throwable $e) {
                    }
                }
            }
        }

        $rawDate = self::extraireDatePourAffichage($row);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
            $dateLiv = DateTimeImmutable::createFromFormat('Y-m-d', $rawDate);
            if ($dateLiv instanceof DateTimeImmutable) {
                if (!empty($row['transit_seconds']) && !empty($row['arrival_at'])) {
                    try {
                        $arrival = new DateTimeImmutable((string) $row['arrival_at']);
                        $transit = (int) $row['transit_seconds'];

                        return $arrival->modify('-' . $transit . ' seconds')
                            ->modify('-' . self::PREPARATION_JOURS . ' days');
                    } catch (Throwable $e) {
                    }
                }

                return $dateLiv->modify('-' . self::PREPARATION_JOURS . ' days')->setTime(12, 0);
            }
        }

        return (new DateTimeImmutable('now'))->modify('-1 day');
    }

    private static function formatRemainingLabel(DateTimeImmutable $target, DateTimeImmutable $now): string
    {
        $seconds = max(0, $target->getTimestamp() - $now->getTimestamp());
        if ($seconds < 60) {
            return 'moins d\'une minute';
        }
        if ($seconds < 3600) {
            $m = (int) ceil($seconds / 60);

            return $m . ' min';
        }
        if ($seconds < 86400) {
            $h = (int) floor($seconds / 3600);
            $m = (int) floor(($seconds % 3600) / 60);
            if ($m > 0) {
                return $h . ' h ' . $m . ' min';
            }

            return $h . ' h';
        }
        $d = (int) floor($seconds / 86400);
        $h = (int) floor(($seconds % 86400) / 3600);

        return $h > 0 ? ($d . ' j ' . $h . ' h') : ($d . ' j');
    }

    public function creerEtLierCommande(int $idCommande, string $statut = 'En préparation'): int
    {
        $now = new DateTimeImmutable('now');
        $date = $now->modify('+' . self::PREPARATION_JOURS . ' days')->format('Y-m-d');
        $colDate = $this->nomColonneDateLivraison();
        $hasCreated = $this->hasCreatedAtColumn();
        $this->ensureTransitColumns();

        $this->pdo->beginTransaction();
        try {
            if ($hasCreated) {
                $sql = 'INSERT INTO livraison (`' . $colDate . '`, statut, created_at) VALUES (?, ?, ?)';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$date, $statut, $now->format('Y-m-d H:i:s')]);
            } else {
                $sql = 'INSERT INTO livraison (`' . $colDate . '`, statut) VALUES (?, ?)';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$date, $statut]);
            }
            $idLivraison = (int) $this->pdo->lastInsertId();

            $stmtU = $this->pdo->prepare(
                'UPDATE commande SET id_livraison = ? WHERE id_commande = ?'
            );
            $stmtU->execute([$idLivraison, $idCommande]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        require_once __DIR__ . '/UserNotificationService.php';
        user_notification_livraison_created($this->pdo, $idCommande, $idLivraison);

        return $idLivraison;
    }

    /** @return array<string, mixed>|null */
    public function getLivraisonById(int $id, ?array $commande = null): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM livraison WHERE id_livraison = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        if ($commande === null) {
            $stmtC = $this->pdo->prepare('SELECT * FROM commande WHERE id_livraison = ? ORDER BY id_commande DESC LIMIT 1');
            $stmtC->execute([$id]);
            $commande = $stmtC->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $timeline = $this->buildTimelineState($row, is_array($commande) ? $commande : null);
        $newStatut = (string) $timeline['statut'];
        if ((string) ($row['statut'] ?? '') !== $newStatut && $newStatut !== self::STATUT_ANNULEE) {
            $this->setStatutLivraison($id, $newStatut);
            $row['statut'] = $newStatut;
        } elseif ($timeline['phase'] === 'annulee') {
            // statut déjà Annulée
        }

        return $row;
    }

    public function supprimerLivraison(int $idLivraison): void
    {
        $stmt = $this->pdo->prepare('UPDATE commande SET id_livraison = NULL WHERE id_livraison = ?');
        $stmt->execute([$idLivraison]);

        $stmtD = $this->pdo->prepare('DELETE FROM livraison WHERE id_livraison = ?');
        $stmtD->execute([$idLivraison]);
    }

    public function annulerLivraison(int $idLivraison): void
    {
        $this->setStatutLivraison($idLivraison, self::STATUT_ANNULEE);
    }

    /** @return array<int, array<string, mixed>> */
    public function listLivraisons(): array
    {
        $rows = $this->pdo->query('SELECT * FROM livraison ORDER BY id_livraison DESC')->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            return [];
        }

        foreach ($rows as &$row) {
            $id = (int) ($row['id_livraison'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $timeline = $this->buildTimelineState($row);
            $newStatut = (string) $timeline['statut'];
            if ((string) ($row['statut'] ?? '') !== $newStatut) {
                $this->setStatutLivraison($id, $newStatut);
                $row['statut'] = $newStatut;
            }
        }
        unset($row);

        return $rows;
    }

    public function updateLivraison(int $idLivraison, string $dateYmd, string $statut): void
    {
        $col = $this->nomColonneDateLivraison();
        $sql = 'UPDATE livraison SET `' . $col . '` = ?, statut = ? WHERE id_livraison = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$dateYmd, $statut, $idLivraison]);
    }

    private function setStatutLivraison(int $idLivraison, string $statut): void
    {
        $stmt = $this->pdo->prepare('SELECT statut FROM livraison WHERE id_livraison = ?');
        $stmt->execute([$idLivraison]);
        $old = (string) ($stmt->fetchColumn() ?: '');

        $stmt = $this->pdo->prepare('UPDATE livraison SET statut = ? WHERE id_livraison = ?');
        $stmt->execute([$statut, $idLivraison]);

        if ($old !== $statut) {
            require_once __DIR__ . '/UserNotificationService.php';
            user_notification_livraison_status($this->pdo, $idLivraison, $statut);
        }
    }
}
