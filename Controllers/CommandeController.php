<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

class CommandeController
{
    private PDO $pdo;

    /**
     * Cached per request: which datetime column stores order creation (`date` or `date_commande`), or null.
     *
     * @var string|null
     */
    private static ?string $commandeDateColumnName = null;

    private static bool $commandeDateColumnNameResolved = false;

    /** @var bool|null */
    private static ?bool $commandeHasIdUtilisateurColumn = null;

    public const FRAIS_LIVRAISON = 2.00;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function commandeTableHasIdUtilisateur(): bool
    {
        if (self::$commandeHasIdUtilisateurColumn !== null) {
            return self::$commandeHasIdUtilisateurColumn;
        }
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute(['commande', 'id_utilisateur']);
        self::$commandeHasIdUtilisateurColumn = (int) $stmt->fetchColumn() > 0;

        return self::$commandeHasIdUtilisateurColumn;
    }

    /**
     * @param array<int, array{id_produit: int, quantite: int, prix_unitaire: float, nom: string}> $lignes
     * @return array{id_commande: int, noms_produits: string, total: float}
     */
    public function creerCommandeDepuisPanier(array $lignes, ?int $idUtilisateur = null): array
    {
        if ($lignes === []) {
            throw new InvalidArgumentException('Panier vide');
        }

        $sousTotal = 0.0;
        foreach ($lignes as $l) {
            $sousTotal += (float) $l['prix_unitaire'] * (int) $l['quantite'];
        }
        $total = round($sousTotal + self::FRAIS_LIVRAISON, 2);

        $noms = array_map(static fn (array $l): string => (string) $l['nom'], $lignes);
        $nomsProduits = implode(', ', $noms);

        $attachUser = $this->commandeTableHasIdUtilisateur()
            && $idUtilisateur !== null
            && $idUtilisateur > 0;

        $this->pdo->beginTransaction();
        try {
            $dateCol = $this->resolveCommandeDateColumnName();
            if ($dateCol !== null) {
                $colSql = $dateCol === 'date' ? '`date`' : 'date_commande';
                if ($attachUser) {
                    $stmt = $this->pdo->prepare(
                        "INSERT INTO commande ({$colSql}, total, modePaiement, reduction, id_livraison, id_utilisateur)
                         VALUES (NOW(), ?, NULL, 0, NULL, ?)"
                    );
                    $stmt->execute([$total, $idUtilisateur]);
                } else {
                    $stmt = $this->pdo->prepare(
                        "INSERT INTO commande ({$colSql}, total, modePaiement, reduction, id_livraison)
                         VALUES (NOW(), ?, NULL, 0, NULL)"
                    );
                    $stmt->execute([$total]);
                }
            } elseif ($attachUser) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO commande (total, modePaiement, reduction, id_livraison, id_utilisateur)
                     VALUES (?, NULL, 0, NULL, ?)'
                );
                $stmt->execute([$total, $idUtilisateur]);
            } else {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO commande (total, modePaiement, reduction, id_livraison)
                     VALUES (?, NULL, 0, NULL)'
                );
                $stmt->execute([$total]);
            }
            $idCommande = (int) $this->pdo->lastInsertId();

            $stmtL = $this->pdo->prepare(
                'INSERT INTO commande_produit (id_commande, id_produit, quantite, prix_unitaire)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($lignes as $l) {
                $stmtL->execute([
                    $idCommande,
                    (int) $l['id_produit'],
                    (int) $l['quantite'],
                    round((float) $l['prix_unitaire'], 2),
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return [
            'id_commande' => $idCommande,
            'noms_produits' => $nomsProduits,
            'total' => $total,
        ];
    }

    public function finaliserCommande(int $idCommande, string $modePaiement, float $reduction): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE commande SET modePaiement = ?, reduction = ? WHERE id_commande = ?'
        );
        $stmt->execute([$modePaiement, $reduction, $idCommande]);
    }

    /** @return array<string, mixed>|null */
    public function getCommandeById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM commande WHERE id_commande = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function supprimerCommande(int $idCommande): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM commande WHERE id_commande = ?');
        $stmt->execute([$idCommande]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listCommandes(): array
    {
        $sql = 'SELECT * FROM commande ORDER BY id_commande DESC';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function getLatestCommande(): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM commande ORDER BY id_commande DESC LIMIT 1');
        if ($stmt === false) {
            return null;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Commandes de l’utilisateur ayant une livraison liée (suivi). */
    public function listCommandesAvecLivraisonPourUtilisateur(int $idUtilisateur): array
    {
        if ($idUtilisateur < 1 || !$this->commandeTableHasIdUtilisateur()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT * FROM commande
             WHERE id_utilisateur = ? AND id_livraison IS NOT NULL
             ORDER BY id_commande DESC'
        );
        $stmt->execute([$idUtilisateur]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Dernière commande avec livraison pour l’utilisateur (suivi par défaut). */
    public function getDerniereCommandeAvecLivraisonPourUtilisateur(int $idUtilisateur): ?array
    {
        $rows = $this->listCommandesAvecLivraisonPourUtilisateur($idUtilisateur);

        return $rows[0] ?? null;
    }

    /**
     * Vérifie que la commande appartient à l’utilisateur (si la colonne existe).
     * Sans colonne `id_utilisateur`, comportement legacy : toujours true si la commande existe.
     */
    public function commandeAppartientAUtilisateur(int $idCommande, int $idUtilisateur): bool
    {
        if ($idCommande < 1 || $idUtilisateur < 1) {
            return false;
        }
        $cmd = $this->getCommandeById($idCommande);
        if ($cmd === null) {
            return false;
        }
        if (!$this->commandeTableHasIdUtilisateur()) {
            return true;
        }

        return (int) ($cmd['id_utilisateur'] ?? 0) === $idUtilisateur;
    }

    /**
     * Reconstruit la liste des noms depuis commande_produit (pour affichage).
     */
    public function getNomsProduitsCommande(int $idCommande): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.nom FROM commande_produit cp
             INNER JOIN produit p ON p.id_produit = cp.id_produit
             WHERE cp.id_commande = ?
             ORDER BY cp.id_commande_produit ASC'
        );
        $stmt->execute([$idCommande]);
        $noms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return implode(', ', $noms);
    }

    /**
     * @return 'date'|'date_commande'|null
     */
    private function resolveCommandeDateColumnName(): ?string
    {
        if (self::$commandeDateColumnNameResolved) {
            return self::$commandeDateColumnName;
        }
        self::$commandeDateColumnNameResolved = true;
        $stmt = $this->pdo->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'commande'
               AND COLUMN_NAME IN ('date', 'date_commande')"
        );
        if ($stmt === false) {
            self::$commandeDateColumnName = null;
            return null;
        }
        $names = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('date', $names, true)) {
            self::$commandeDateColumnName = 'date';
            return 'date';
        }
        if (in_array('date_commande', $names, true)) {
            self::$commandeDateColumnName = 'date_commande';
            return 'date_commande';
        }
        self::$commandeDateColumnName = null;
        return null;
    }
}
