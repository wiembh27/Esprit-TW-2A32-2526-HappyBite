<?php

declare(strict_types=1);

function user_notification_ensure_table(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_notification (
            id_notification INT AUTO_INCREMENT PRIMARY KEY,
            id_utilisateur INT NOT NULL,
            type_notif VARCHAR(32) NOT NULL DEFAULT \'info\',
            titre VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            lu TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_lu (id_utilisateur, lu),
            INDEX idx_user_created (id_utilisateur, created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
    user_notification_ensure_ref_key($pdo);
}

function user_notification_ensure_ref_key(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'user_notification', 'c' => 'ref_key']);
    if ((int) $stmt->fetchColumn() === 0) {
        try {
            $pdo->exec('ALTER TABLE user_notification ADD COLUMN ref_key VARCHAR(96) NULL AFTER type_notif');
            $pdo->exec('CREATE UNIQUE INDEX idx_user_notification_ref ON user_notification (id_utilisateur, ref_key)');
        } catch (Throwable $e) {
            // index peut déjà exister
        }
    }
    $done = true;
}

function user_notification_has_ref_key(PDO $pdo): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'user_notification', 'c' => 'ref_key']);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Envoie une notification si la ref_key n'existe pas encore pour cet utilisateur.
 */
function user_notification_send(
    PDO $pdo,
    int $userId,
    string $type,
    string $refKey,
    string $titre,
    string $message
): bool {
    if ($userId <= 0 || trim($refKey) === '') {
        return false;
    }

    if (user_notification_blocks_type($pdo, $userId, $type)) {
        return false;
    }

    user_notification_ensure_table($pdo);

    $hasRef = user_notification_has_ref_key($pdo);
    if ($hasRef) {
        $check = $pdo->prepare(
            'SELECT id_notification FROM user_notification WHERE id_utilisateur = :uid AND ref_key = :ref LIMIT 1'
        );
        $check->execute(['uid' => $userId, 'ref' => $refKey]);
        if ($check->fetch()) {
            return false;
        }
    }
    if ($hasRef) {
        $stmt = $pdo->prepare(
            'INSERT INTO user_notification (id_utilisateur, type_notif, ref_key, titre, message, lu)
             VALUES (:uid, :type, :ref, :titre, :message, 0)'
        );
        $stmt->execute([
            'uid' => $userId,
            'type' => $type,
            'ref' => $refKey,
            'titre' => $titre,
            'message' => $message,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO user_notification (id_utilisateur, type_notif, titre, message, lu)
             VALUES (:uid, :type, :titre, :message, 0)'
        );
        $stmt->execute([
            'uid' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
        ]);
    }

    return true;
}

function user_notification_display_name(PDO $pdo, int $userId): string
{
    if ($userId <= 0) {
        return 'Un utilisateur';
    }
    $pk = user_notification_utilisateur_pk($pdo);
    $stmt = $pdo->prepare("SELECT prenom, nom FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return 'Un utilisateur';
    }
    $name = trim((string) ($row['prenom'] ?? '') . ' ' . (string) ($row['nom'] ?? ''));

    return $name !== '' ? $name : 'Un utilisateur';
}

function user_notification_utilisateur_pk(PDO $pdo): string
{
    static $col = null;
    if ($col !== null) {
        return $col;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'utilisateur', 'c' => 'id']);
    $col = (int) $stmt->fetchColumn() > 0 ? 'id' : 'id_utilisateur';

    return $col;
}

function user_notification_get_user_role(PDO $pdo, int $userId): string
{
    if ($userId <= 0) {
        return '';
    }

    static $cache = [];

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $pk = user_notification_utilisateur_pk($pdo);
    $stmt = $pdo->prepare("SELECT role FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $role = strtolower(trim((string) ($stmt->fetchColumn() ?: '')));
    $cache[$userId] = $role;

    return $role;
}

/** Fournisseur / nutritionniste : pas de notifs commande, carte livraison ni santé. */
function user_notification_skips_order_health_notifs(PDO $pdo, int $userId): bool
{
    return in_array(
        user_notification_get_user_role($pdo, $userId),
        ['fournisseur', 'nutritionniste'],
        true
    );
}

function user_notification_blocks_type(PDO $pdo, int $userId, string $type): bool
{
    if (!user_notification_skips_order_health_notifs($pdo, $userId)) {
        return false;
    }

    $type = strtolower(trim($type));

    return in_array($type, ['livraison', 'sante'], true);
}

function user_notification_user_id_for_commande(PDO $pdo, int $idCommande): int
{
    if ($idCommande <= 0) {
        return 0;
    }
    $stmt = $pdo->prepare('SELECT id_utilisateur FROM commande WHERE id_commande = ? LIMIT 1');
    $stmt->execute([$idCommande]);
    $uid = (int) ($stmt->fetchColumn() ?: 0);
    if ($uid > 0) {
        return $uid;
    }
    $stmt = $pdo->prepare('SELECT * FROM commande WHERE id_commande = ? LIMIT 1');
    $stmt->execute([$idCommande]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['id_utilisateur'] ?? 0);
}

function user_notification_user_id_for_livraison(PDO $pdo, int $idLivraison): int
{
    if ($idLivraison <= 0) {
        return 0;
    }
    $stmt = $pdo->prepare(
        'SELECT id_utilisateur FROM commande WHERE id_livraison = ? ORDER BY id_commande DESC LIMIT 1'
    );
    $stmt->execute([$idLivraison]);
    $uid = (int) ($stmt->fetchColumn() ?: 0);
    if ($uid > 0) {
        return $uid;
    }
    $stmt = $pdo->prepare('SELECT * FROM commande WHERE id_livraison = ? ORDER BY id_commande DESC LIMIT 1');
    $stmt->execute([$idLivraison]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['id_utilisateur'] ?? 0);
}

function user_notification_livraison_created(PDO $pdo, int $idCommande, int $idLivraison): void
{
    $userId = user_notification_user_id_for_commande($pdo, $idCommande);
    if ($userId < 1) {
        return;
    }
    user_notification_send(
        $pdo,
        $userId,
        'livraison',
        'livraison:' . $idLivraison . ':created',
        'Commande reçue',
        'Votre commande a bien été reçue. Nous commençons la préparation — vous pourrez suivre la livraison sur la carte dès l\'expédition.'
    );
}

function user_notification_livraison_status(PDO $pdo, int $idLivraison, string $statut): void
{
    $userId = user_notification_user_id_for_livraison($pdo, $idLivraison);
    if ($userId < 1) {
        return;
    }

    $norm = strtolower(trim($statut));
    if (str_contains($norm, 'cours')) {
        user_notification_send(
            $pdo,
            $userId,
            'livraison',
            'livraison:' . $idLivraison . ':encours',
            'Commande en route',
            'Votre commande est prête : elle est en cours de livraison et arrivera bientôt chez vous. Ouvrez le suivi sur la carte pour voir l\'heure d\'arrivée.'
        );
    } elseif (str_contains($norm, 'livr')) {
        user_notification_send(
            $pdo,
            $userId,
            'livraison',
            'livraison:' . $idLivraison . ':livree',
            'Commande arrivée',
            'Bonne nouvelle : votre commande est arrivée ! Merci d\'avoir choisi HappyBite.'
        );
    }
}

function user_notification_post_liked(PDO $pdo, int $postId, int $actorUserId): void
{
    if ($postId < 1 || $actorUserId < 1) {
        return;
    }
    $stmt = $pdo->prepare('SELECT id_utilisateur FROM Post WHERE id = ? LIMIT 1');
    $stmt->execute([$postId]);
    $ownerId = (int) ($stmt->fetchColumn() ?: 0);
    if ($ownerId < 1 || $ownerId === $actorUserId) {
        return;
    }
    $name = user_notification_display_name($pdo, $actorUserId);
    user_notification_send(
        $pdo,
        $ownerId,
        'communaute',
        'post_like:' . $postId . ':by:' . $actorUserId,
        'Nouveau like',
        $name . ' a aimé votre publication.'
    );
}

function user_notification_post_commented(PDO $pdo, int $postId, int $actorUserId, int $commentId): void
{
    if ($postId < 1 || $actorUserId < 1 || $commentId < 1) {
        return;
    }
    $stmt = $pdo->prepare('SELECT id_utilisateur FROM Post WHERE id = ? LIMIT 1');
    $stmt->execute([$postId]);
    $ownerId = (int) ($stmt->fetchColumn() ?: 0);
    if ($ownerId < 1 || $ownerId === $actorUserId) {
        return;
    }
    $name = user_notification_display_name($pdo, $actorUserId);
    user_notification_send(
        $pdo,
        $ownerId,
        'communaute',
        'post_comment:' . $commentId,
        'Nouveau commentaire',
        $name . ' a commenté votre publication.'
    );
}

/**
 * Rappels santé (suivi journalier) et promo espace santé — à appeler une fois par chargement de page (utilisateur connecté).
 */
function user_notification_run_scheduled_checks(PDO $pdo, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    if (user_notification_skips_order_health_notifs($pdo, $userId)) {
        return;
    }

    user_notification_ensure_table($pdo);

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t'
    );
    $stmt->execute(['t' => 'profil_sante']);
    if ((int) $stmt->fetchColumn() === 0) {
        return;
    }

    $pk = user_notification_utilisateur_pk($pdo);
    $stmt = $pdo->prepare("SELECT id FROM profil_sante WHERE id_utilisateur = :uid LIMIT 1");
    $stmt->execute(['uid' => $userId]);
    $profilId = (int) ($stmt->fetchColumn() ?: 0);

    if ($profilId > 0) {
        $stmt = $pdo->prepare(
            'SELECT MAX(date_jour) FROM suivi_journalier WHERE id_profil_sante = :pid'
        );
        $stmt->execute(['pid' => $profilId]);
        $lastDay = $stmt->fetchColumn();
        $threshold = time() - 86400;
        $lastTs = false;
        if ($lastDay !== false && $lastDay !== null && $lastDay !== '') {
            $lastTs = strtotime((string) $lastDay . ' 12:00:00');
        }
        if ($lastTs === false || $lastTs < $threshold) {
            $dayKey = date('Y-m-d');
            user_notification_send(
                $pdo,
                $userId,
                'sante',
                'sante_suivi_reminder:' . $dayKey,
                'Rappel suivi journalier',
                'Cela fait plus de 24 h sans suivi journalier. Enregistrez vos calories, sommeil, pas et hydratation pour garder un œil sur vos objectifs santé.'
            );
        }
    } else {
        $weekKey = date('o') . '-W' . date('W');
        user_notification_send(
            $pdo,
            $userId,
            'sante',
            'sante_promo:' . $weekKey,
            'Découvrez l\'espace Santé',
            'Créez votre profil santé sur HappyBite : fixez vos objectifs (poids, calories, hydratation), suivez votre quotidien et recevez des conseils adaptés à votre profil.'
        );
    }
}

function user_notification_send_welcome(PDO $pdo, int $userId, string $prenom): void
{
    if ($userId <= 0) {
        return;
    }
    user_notification_ensure_table($pdo);

    $name = trim($prenom) !== '' ? trim($prenom) : 'vous';
    user_notification_send(
        $pdo,
        $userId,
        'welcome',
        'welcome',
        'Bienvenue sur HappyBite',
        'Bonjour ' . $name . ' ! Nous sommes ravis de vous compter parmi nous. '
            . 'Explorez les produits, recettes et votre espace santé pour bien démarrer.'
    );
}

/** Notification unique à la création du profil santé (comme welcome à l'inscription). */
function user_notification_password_changed(PDO $pdo, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    user_notification_ensure_table($pdo);

    user_notification_send(
        $pdo,
        $userId,
        'securite',
        'password_changed_' . time(),
        'Mot de passe modifié',
        'Votre mot de passe a été changé avec succès. Si vous n\'êtes pas à l\'origine de cette action, sécurisez votre compte immédiatement.'
    );
}

function user_notification_send_sante_welcome(PDO $pdo, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    user_notification_ensure_table($pdo);

    $pk = user_notification_utilisateur_pk($pdo);
    $stmt = $pdo->prepare("SELECT prenom FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $prenom = trim((string) ($stmt->fetchColumn() ?: ''));
    $name = $prenom !== '' ? $prenom : 'vous';

    user_notification_send(
        $pdo,
        $userId,
        'sante',
        'sante_welcome',
        'Bienvenue dans l\'espace Santé',
        'Bonjour ' . $name . ' ! Votre profil santé est prêt. '
            . 'Définissez vos objectifs, enregistrez votre suivi journalier (calories, sommeil, pas, hydratation) '
            . 'et découvrez des conseils adaptés à votre profil sur HappyBite.'
    );
}

function user_notification_table_exists(PDO $pdo): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t'
    );
    $stmt->execute(['t' => 'user_notification']);

    return (int) $stmt->fetchColumn() > 0;
}

/** @return array<int, array<string, mixed>> */
function user_notification_list(PDO $pdo, int $userId, int $limit = 40): array
{
    if ($userId <= 0 || !user_notification_table_exists($pdo)) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $sql = 'SELECT id_notification, type_notif, titre, message, lu, created_at
         FROM user_notification
         WHERE id_utilisateur = :uid';

    if (user_notification_skips_order_health_notifs($pdo, $userId)) {
        $sql .= " AND type_notif NOT IN ('livraison', 'sante')";
    }

    $sql .= ' ORDER BY created_at DESC LIMIT ' . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function user_notification_count_unread(PDO $pdo, int $userId): int
{
    if ($userId <= 0 || !user_notification_table_exists($pdo)) {
        return 0;
    }
    $sql = 'SELECT COUNT(*) FROM user_notification WHERE id_utilisateur = :uid AND lu = 0';

    if (user_notification_skips_order_health_notifs($pdo, $userId)) {
        $sql .= " AND type_notif NOT IN ('livraison', 'sante')";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $userId]);

    return (int) $stmt->fetchColumn();
}

function user_notification_mark_one_read(PDO $pdo, int $userId, int $notificationId): bool
{
    if ($userId <= 0 || $notificationId <= 0 || !user_notification_table_exists($pdo)) {
        return false;
    }
    $stmt = $pdo->prepare(
        'UPDATE user_notification SET lu = 1
         WHERE id_notification = :nid AND id_utilisateur = :uid AND lu = 0'
    );
    $stmt->execute(['nid' => $notificationId, 'uid' => $userId]);

    return $stmt->rowCount() > 0;
}

function user_notification_mark_all_read(PDO $pdo, int $userId): void
{
    if ($userId <= 0 || !user_notification_table_exists($pdo)) {
        return;
    }
    $stmt = $pdo->prepare(
        'UPDATE user_notification SET lu = 1 WHERE id_utilisateur = :uid AND lu = 0'
    );
    $stmt->execute(['uid' => $userId]);
}

function user_notification_delete_for_user(PDO $pdo, int $userId): void
{
    if ($userId <= 0 || !user_notification_table_exists($pdo)) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM user_notification WHERE id_utilisateur = :uid');
    $stmt->execute(['uid' => $userId]);
}

function user_notification_format_date(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }

    return date('d/m/Y H:i', $ts);
}
