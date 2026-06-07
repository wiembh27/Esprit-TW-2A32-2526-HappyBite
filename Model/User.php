<?php

declare(strict_types=1);

namespace Model;

use PDO;
use Throwable;

/**
 * Modèle BackOffice utilisateurs (remplace l’ancien chemin Model/User.php du projet PR).
 * S’appuie sur la table `utilisateur` du schéma HappyBite et optionnellement `commande`, `Post`, `profil_sante`.
 */
final class User
{
    private PDO $pdo;

    private string $pkColumn = 'id';

    public function __construct()
    {
        require_once dirname(__DIR__) . '/config/Database.php';
        $this->pdo = \Database::getConnection();
        if ($this->columnExists('utilisateur', 'id')) {
            $this->pkColumn = 'id';
        } elseif ($this->columnExists('utilisateur', 'id_utilisateur')) {
            $this->pkColumn = 'id_utilisateur';
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $st = $this->pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $st->execute([$table, $column]);

            return (int) $st->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $st = $this->pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $st->execute([$table]);

            return (int) $st->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function userIdExpr(string $alias = 'u'): string
    {
        return '`' . $alias . '`.`' . $this->pkColumn . '`';
    }

    /** @return array<string, int|string> */
    public function getKPIs(): array
    {
        $out = [
            'total' => 0,
            'new_today' => 0,
            'new_week' => 0,
            'active' => 0,
            'retention_rate' => 0,
        ];
        try {
            $out['total'] = (int) $this->pdo->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn();
            if ($this->columnExists('utilisateur', 'created_at')) {
                $out['new_today'] = (int) $this->pdo->query(
                    'SELECT COUNT(*) FROM utilisateur WHERE DATE(created_at) = CURDATE()'
                )->fetchColumn();
                $out['new_week'] = (int) $this->pdo->query(
                    'SELECT COUNT(*) FROM utilisateur WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
                )->fetchColumn();
            }
            if ($this->columnExists('utilisateur', 'statut')) {
                $out['active'] = (int) $this->pdo->query(
                    "SELECT COUNT(*) FROM utilisateur WHERE LOWER(TRIM(statut)) IN ('actif','active')"
                )->fetchColumn();
            } else {
                $out['active'] = $out['total'];
            }
            $out['retention_rate'] = $out['total'] > 0
                ? (int) round(100 * $out['active'] / $out['total'])
                : 0;
        } catch (Throwable) {
            // garder zéros
        }

        return $out;
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function getGrowth(string $period): array
    {
        $period = strtolower(trim($period));
        if (!$this->columnExists('utilisateur', 'created_at')) {
            return ['labels' => [], 'data' => []];
        }
        try {
            if ($period === 'day') {
                return $this->growthDaily(30);
            }
            if ($period === 'week') {
                return $this->growthWeeklyBuckets();
            }
            if ($period === 'month') {
                return $this->growthMonthlyBuckets();
            }
        } catch (Throwable) {
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => [], 'data' => []];
    }

    /** @return array{labels: list<string>, data: list<int>} */
    private function growthDaily(int $days): array
    {
        $labels = [];
        $data = array_fill(0, $days, 0);
        $map = [];
        $st = $this->pdo->query(
            'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM utilisateur
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . (int) $days . ' DAY)
             GROUP BY DATE(created_at)'
        );
        if ($st) {
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[(string) ($row['d'] ?? '')] = (int) ($row['c'] ?? 0);
            }
        }
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = (new \DateTimeImmutable('today'))->modify('-' . $i . ' days');
            $labels[] = $d->format('d/m');
            $data[$days - 1 - $i] = $map[$d->format('Y-m-d')] ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** @return array{labels: list<string>, data: list<int>} */
    private function growthWeeklyBuckets(): array
    {
        $labels = [];
        $data = [];
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM utilisateur WHERE DATE(created_at) BETWEEN ? AND ?'
        );
        for ($w = 11; $w >= 0; $w--) {
            $end = (new \DateTimeImmutable('today'))->modify('-' . ($w * 7) . ' days');
            $start = $end->modify('-6 days');
            $labels[] = $end->format('d/m');
            $st->execute([$start->format('Y-m-d'), $end->format('Y-m-d')]);
            $data[] = (int) $st->fetchColumn();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** @return array{labels: list<string>, data: list<int>} */
    private function growthMonthlyBuckets(): array
    {
        $labels = [];
        $data = [];
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM utilisateur WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?'
        );
        for ($m = 11; $m >= 0; $m--) {
            $dt = (new \DateTimeImmutable('first day of this month'))->modify('-' . $m . ' months');
            $labels[] = $dt->format('m/Y');
            $st->execute([(int) $dt->format('Y'), (int) $dt->format('n')]);
            $data[] = (int) $st->fetchColumn();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** @return list<array<string, mixed>> */
    public function getTopActiveUsers(int $limit): array
    {
        $limit = max(1, min(50, $limit));
        $hasCmdUser = $this->columnExists('commande', 'id_utilisateur');
        $hasPost = $this->tableExists('Post')
            && $this->columnExists('Post', 'id_utilisateur');

        try {
            $ordersSql = $hasCmdUser
                ? '(SELECT COUNT(*) FROM commande c WHERE c.id_utilisateur = ' . $this->userIdExpr('u') . ')'
                : '0';
            $postsSql = $hasPost
                ? '(SELECT COUNT(*) FROM Post p WHERE p.id_utilisateur = ' . $this->userIdExpr('u') . ')'
                : '0';

            $sql = 'SELECT u.*, ' . $ordersSql . ' AS orders, ' . $postsSql . ' AS posts
                    FROM utilisateur u
                    ORDER BY orders DESC, posts DESC, ' . $this->userIdExpr('u') . ' DESC
                    LIMIT ' . $limit;

            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $row['id'] = (int) ($row[$this->pkColumn] ?? $row['id'] ?? 0);
                $orders = (int) ($row['orders'] ?? 0);
                $posts = (int) ($row['posts'] ?? 0);
                $row['score'] = $orders * 2 + $posts;
            }
            unset($row);

            return $rows;
        } catch (Throwable) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public function getInactiveUsers(int $days): array
    {
        $days = max(1, min(365, $days));
        if (!$this->columnExists('utilisateur', 'created_at')) {
            return [];
        }
        $hasUpdated = $this->columnExists('utilisateur', 'updated_at');
        try {
            if ($hasUpdated) {
                $sql = 'SELECT `' . $this->pkColumn . '` AS id, prenom, nom, email, updated_at AS last_login
                        FROM utilisateur
                        WHERE updated_at IS NULL OR updated_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)
                        ORDER BY COALESCE(updated_at, created_at) ASC
                        LIMIT 50';
            } else {
                $sql = 'SELECT `' . $this->pkColumn . '` AS id, prenom, nom, email, created_at AS last_login
                        FROM utilisateur
                        WHERE created_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)
                        ORDER BY created_at ASC
                        LIMIT 50';
            }

            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public function getActivityStats(int $limit): array
    {
        $limit = max(1, min(100, $limit));
        $hasCmdUser = $this->columnExists('commande', 'id_utilisateur');
        $hasStatut = $this->columnExists('utilisateur', 'statut');
        $statutExprU = $hasStatut ? 'u.statut' : "'actif'";
        $statutExprPlain = $hasStatut ? 'statut' : "'actif'";
        try {
            if ($hasCmdUser) {
                $sql = 'SELECT u.`' . $this->pkColumn . '` AS id, u.prenom, u.nom, u.email, ' . $statutExprU . ' AS statut,
                        (SELECT COUNT(*) FROM commande c WHERE c.id_utilisateur = u.`' . $this->pkColumn . '`) AS logins
                        FROM utilisateur u
                        ORDER BY logins DESC, u.`' . $this->pkColumn . '` DESC
                        LIMIT ' . $limit;
            } else {
                $sql = 'SELECT `' . $this->pkColumn . '` AS id, prenom, nom, email, ' . $statutExprPlain . ' AS statut, 0 AS logins
                        FROM utilisateur
                        ORDER BY `' . $this->pkColumn . '` DESC
                        LIMIT ' . $limit;
            }

            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed> */
    public function getUserDetail(int $id): array
    {
        $row = $this->findById($id);
        if ($row === null) {
            return [];
        }
        $detail = $row;
        $hasCmdUser = $this->columnExists('commande', 'id_utilisateur');
        $hasPost = $this->tableExists('Post') && $this->columnExists('Post', 'id_utilisateur');
        try {
            if ($hasCmdUser) {
                $st = $this->pdo->prepare('SELECT COUNT(*) FROM commande WHERE id_utilisateur = ?');
                $st->execute([$id]);
                $detail['orders_count'] = (int) $st->fetchColumn();
            }
            if ($hasPost) {
                $st = $this->pdo->prepare('SELECT COUNT(*) FROM Post WHERE id_utilisateur = ?');
                $st->execute([$id]);
                $detail['posts_count'] = (int) $st->fetchColumn();
            }
        } catch (Throwable) {
            // ignore
        }

        return $detail;
    }

    /** @return list<array<string, mixed>> */
    public function findAll(): array
    {
        try {
            return $this->pdo->query(
                'SELECT * FROM utilisateur ORDER BY `' . $this->pkColumn . '` DESC'
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        $out = ['total' => 0, 'admins' => 0, 'clients' => 0, 'nutritionnistes' => 0];
        try {
            $out['total'] = (int) $this->pdo->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn();
            $out['admins'] = (int) $this->pdo->query(
                "SELECT COUNT(*) FROM utilisateur WHERE LOWER(TRIM(role)) = 'admin'"
            )->fetchColumn();
            $out['clients'] = (int) $this->pdo->query(
                "SELECT COUNT(*) FROM utilisateur WHERE LOWER(TRIM(role)) = 'client'"
            )->fetchColumn();
            $out['nutritionnistes'] = (int) $this->pdo->query(
                "SELECT COUNT(*) FROM utilisateur WHERE LOWER(TRIM(role)) = 'nutritionniste'"
            )->fetchColumn();
        } catch (Throwable) {
            // zeros
        }

        return $out;
    }

    /** @return array<string, mixed>|null */
    public function findById(int|string $id): ?array
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }
        try {
            $st = $this->pdo->prepare('SELECT * FROM utilisateur WHERE `' . $this->pkColumn . '` = ? LIMIT 1');
            $st->execute([$id]);
            $u = $st->fetch(PDO::FETCH_ASSOC);
            if (!$u) {
                return null;
            }
            $uid = (int) ($u[$this->pkColumn] ?? $u['id'] ?? 0);
            $u['id'] = $uid;

            $profil = null;
            if ($this->tableExists('profil_sante') && $this->columnExists('profil_sante', 'id_utilisateur')) {
                $ps = $this->pdo->prepare(
                    'SELECT * FROM profil_sante WHERE id_utilisateur = ? ORDER BY id DESC LIMIT 1'
                );
                $ps->execute([$uid]);
                $profil = $ps->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            $u['poid'] = $profil['poids_actuel'] ?? $u['poid'] ?? '';
            $u['objectif'] = $profil['objectif'] ?? $u['objectif'] ?? 'maintenir';
            $allerg = $profil['allergenes'] ?? null;
            if (is_string($allerg) && $allerg !== '') {
                $dec = json_decode($allerg, true);
                $u['allergie'] = is_array($dec) ? implode(', ', $dec) : $allerg;
            } else {
                $u['allergie'] = $u['allergie'] ?? '';
            }
            $carences = $profil['carences'] ?? null;
            if (is_string($carences) && $carences !== '') {
                $dec = json_decode($carences, true);
                $u['carence'] = is_array($dec) ? implode(', ', $dec) : $carences;
            } else {
                $u['carence'] = $u['carence'] ?? '';
            }
            $u['budget'] = $u['budget'] ?? 0;
            $u['description'] = $u['description'] ?? '';

            return $u;
        } catch (Throwable) {
            return null;
        }
    }

    public function toggleStatut(int $id): bool
    {
        if (!$this->columnExists('utilisateur', 'statut')) {
            return false;
        }
        $id = max(1, $id);
        $row = $this->findById($id);
        if ($row === null) {
            return false;
        }
        if (strtolower(trim((string) ($row['role'] ?? ''))) === 'admin') {
            return false;
        }
        $sk = str_replace(['é', 'è'], 'e', strtolower(trim((string) ($row['statut'] ?? ''))));
        $blocked = str_contains($sk, 'bloque');
        $new = $blocked ? 'actif' : 'bloqué';
        try {
            $st = $this->pdo->prepare(
                'UPDATE utilisateur SET `statut` = ? WHERE `' . $this->pkColumn . '` = ? LIMIT 1'
            );

            return $st->execute([$new, $id]);
        } catch (Throwable) {
            return false;
        }
    }

    public function deleteById(int $id, ?int $actingBackofficeUserId = null): bool
    {
        $id = max(1, $id);
        $row = $this->findById($id);
        if ($row === null) {
            return false;
        }
        if (strtolower(trim((string) ($row['role'] ?? ''))) === 'admin') {
            return false;
        }
        if ($actingBackofficeUserId !== null && $actingBackofficeUserId > 0 && $id === $actingBackofficeUserId) {
            return false;
        }
        try {
            $st = $this->pdo->prepare('DELETE FROM utilisateur WHERE `' . $this->pkColumn . '` = ? LIMIT 1');

            return $st->execute([$id]);
        } catch (Throwable) {
            return false;
        }
    }

    public function updateProfileImagePath(int $id, string $relativePath): bool
    {
        $id = max(1, $id);
        $trim = trim($relativePath);
        if ($trim === '') {
            return false;
        }
        try {
            if ($this->columnExists('utilisateur', 'profil-image')) {
                $st = $this->pdo->prepare(
                    'UPDATE utilisateur SET `profil-image` = ? WHERE `' . $this->pkColumn . '` = ? LIMIT 1'
                );

                return $st->execute([$trim, $id]);
            }
            if ($this->columnExists('utilisateur', 'profile_photo')) {
                $st = $this->pdo->prepare(
                    'UPDATE utilisateur SET `profile_photo` = ? WHERE `' . $this->pkColumn . '` = ? LIMIT 1'
                );

                return $st->execute([$trim, $id]);
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    public function update(int|string $id, array $data): bool
    {
        $id = (int) $id;
        if ($id < 1) {
            return false;
        }

        try {
            $sets = [];
            $params = ['id' => $id];
            foreach (['prenom' => 'prenom', 'nom' => 'nom', 'email' => 'email', 'description' => 'description'] as $key => $col) {
                if ($this->columnExists('utilisateur', $col) && array_key_exists($key, $data)) {
                    $sets[] = '`' . $col . '` = :' . $col;
                    $params[$col] = $data[$key];
                }
            }
            if ($this->columnExists('utilisateur', 'budget') && array_key_exists('budget', $data)) {
                $sets[] = '`budget` = :budget';
                $params['budget'] = $data['budget'];
            }
            if ($sets !== []) {
                $sql = 'UPDATE utilisateur SET ' . implode(', ', $sets)
                    . ' WHERE `' . $this->pkColumn . '` = :id';
                $st = $this->pdo->prepare($sql);
                $st->execute($params);
            }

            if ($this->tableExists('profil_sante') && $this->columnExists('profil_sante', 'id_utilisateur')) {
                $this->upsertProfilSante($id, $data);
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array<string, mixed> $data */
    private function upsertProfilSante(int $userId, array $data): void
    {
        $st = $this->pdo->prepare('SELECT id FROM profil_sante WHERE id_utilisateur = ? LIMIT 1');
        $st->execute([$userId]);
        $pid = $st->fetchColumn();

        $patch = [];
        if ($this->columnExists('profil_sante', 'poids_actuel') && array_key_exists('poid', $data)) {
            $patch['poids_actuel'] = $data['poid'];
        }
        if ($this->columnExists('profil_sante', 'objectif') && array_key_exists('objectif', $data)) {
            $patch['objectif'] = $data['objectif'];
        }
        if ($this->columnExists('profil_sante', 'allergenes') && array_key_exists('allergie', $data)) {
            $parts = array_filter(array_map('trim', explode(',', (string) $data['allergie'])));
            $patch['allergenes'] = json_encode(array_values($parts), JSON_UNESCAPED_UNICODE);
        }
        if ($this->columnExists('profil_sante', 'carences') && array_key_exists('carence', $data)) {
            $parts = array_filter(array_map('trim', explode(',', (string) $data['carence'])));
            $patch['carences'] = json_encode(array_values($parts), JSON_UNESCAPED_UNICODE);
        }

        if ($patch === []) {
            return;
        }

        if ($pid) {
            $sets = [];
            $params = ['pid' => (int) $pid];
            foreach ($patch as $k => $v) {
                $sets[] = '`' . $k . '` = :' . $k;
                $params[$k] = $v;
            }
            $sql = 'UPDATE profil_sante SET ' . implode(', ', $sets) . ' WHERE id = :pid';
            $this->pdo->prepare($sql)->execute($params);

            return;
        }

        $cols = ['`id_utilisateur`'];
        $vals = [':uid'];
        $params = ['uid' => $userId];
        foreach (['taille', 'poids_actuel', 'objectif', 'allergenes', 'carences', 'maladies'] as $c) {
            if (!$this->columnExists('profil_sante', $c)) {
                continue;
            }
            $cols[] = '`' . $c . '`';
            $vals[] = ':' . $c;
            $params[$c] = in_array($c, ['allergenes', 'carences', 'maladies'], true) ? '[]' : null;
        }
        foreach ($patch as $k => $v) {
            if (!$this->columnExists('profil_sante', $k)) {
                continue;
            }
            $params[$k] = $v;
        }

        $sql = 'INSERT INTO profil_sante (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        $this->pdo->prepare($sql)->execute($params);
    }
}
